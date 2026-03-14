<?php

namespace App\Console\Commands;

use App\Models\IplMatch;
use App\Models\Setting;
use App\Services\CricketApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchIplMatches extends Command
{
    protected $signature = 'ipl:fetch-matches
                            {--season= : Season label e.g. "IPL 2025" (defaults to settings)}
                            {--series-id= : CricAPI series ID (auto-detected if not provided)}';

    protected $description = 'Fetch all IPL matches from CricAPI and sync to database';

    public function handle(CricketApiService $api): int
    {
        $season = $this->option('season') ?: Setting::get('season', 'IPL 2025');
        $this->info("Fetching matches for: {$season}");

        // Step 1: Find the series
        $seriesId = $this->option('series-id');

        if (!$seriesId) {
            $this->info('Searching for series...');
            $series = $api->findIplSeries($season);

            if (!$series) {
                $this->error("Could not find series for '{$season}'. Try passing --series-id manually.");
                return self::FAILURE;
            }

            $seriesId = $series['id'];
            $this->info("Found series: {$series['name']} (ID: {$seriesId})");
        }

        // Step 2: Fetch all matches
        $this->info('Fetching match list...');
        $matches = $api->getSeriesMatches($seriesId);

        if (empty($matches)) {
            $this->error('No matches found for this series.');
            return self::FAILURE;
        }

        $this->info(count($matches) . ' matches found. Syncing...');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($matches));
        $bar->start();

        $matchNumber = 0;

        foreach ($matches as $match) {
            $bar->advance();

            // Skip non-T20 or non-standard matches
            if (($match['matchType'] ?? '') !== 't20') {
                $skipped++;
                continue;
            }

            $teams = $match['teams'] ?? [];
            if (count($teams) < 2) {
                $skipped++;
                continue;
            }

            $teamACode = $api->getTeamShortCode($teams[0]);
            $teamBCode = $api->getTeamShortCode($teams[1]);

            if (!$teamACode || !$teamBCode) {
                $this->newLine();
                $this->warn("Unknown team(s): {$teams[0]} vs {$teams[1]} — skipping");
                $skipped++;
                continue;
            }

            $matchNumber++;

            $matchDate = null;
            if (!empty($match['dateTimeGMT'])) {
                try {
                    $matchDate = Carbon::parse($match['dateTimeGMT'])->setTimezone('Asia/Kolkata');
                } catch (\Exception $e) {
                    $matchDate = !empty($match['date']) ? Carbon::parse($match['date']) : now();
                }
            } elseif (!empty($match['date'])) {
                $matchDate = Carbon::parse($match['date']);
            }

            $status = $api->determineMatchStatus($match);
            $winner = $api->parseWinnerFromStatus($match);

            // Check if match already exists by cricapi_id or by teams + date
            $existing = IplMatch::where('cricapi_id', $match['id'])->first();

            if (!$existing && $matchDate) {
                $existing = IplMatch::where('team_a_short', $teamACode)
                    ->where('team_b_short', $teamBCode)
                    ->whereDate('match_date', $matchDate->toDateString())
                    ->first();
            }

            $data = [
                'cricapi_id'     => $match['id'],
                'team_a'         => $api->getTeamFullName($teamACode),
                'team_b'         => $api->getTeamFullName($teamBCode),
                'team_a_short'   => $teamACode,
                'team_b_short'   => $teamBCode,
                'match_date'     => $matchDate,
                'venue'          => $match['venue'] ?? null,
                'season'         => $season,
            ];

            if ($existing) {
                // Only update if not already manually completed
                if ($existing->status !== 'completed' && $existing->status !== 'cancelled') {
                    $updateData = ['venue' => $data['venue']];
                    if ($matchDate) {
                        $updateData['match_date'] = $matchDate;
                    }
                    $existing->update($updateData);
                }
                $updated++;
            } else {
                $data['match_number']   = $matchNumber;
                $data['status']         = 'upcoming';
                $data['win_multiplier'] = 1.90;
                IplMatch::create($data);
                $created++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync complete: {$created} created, {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
