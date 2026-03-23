<?php

namespace App\Console\Commands;

use App\Models\IplMatch;
use App\Models\Setting;
use App\Services\CricketApiService;
use Illuminate\Console\Command;

class FetchIplMatches extends Command
{
    protected $signature = 'ipl:fetch-matches
                            {--season= : Season year e.g. 2026 (auto-detected from settings)}
                            {--with-toss : Fetch toss info for each completed match (slower, calls summary API per match)}';

    protected $description = 'Fetch all IPL matches from ESPNcricinfo and sync to database';

    public function handle(CricketApiService $api): int
    {
        $seasonLabel = $this->option('season')
            ?: Setting::get('season', 'IPL 2026');

        // Extract year from "IPL 2026" or just "2026"
        preg_match('/(\d{4})/', $seasonLabel, $m);
        $year = (int) ($m[1] ?? now()->year);

        $withToss = $this->option('with-toss');

        $this->info("Fetching IPL {$year} matches from ESPNcricinfo...");
        $this->newLine();

        $matches = $api->fetchMatches($year);

        if (empty($matches)) {
            $this->error('No matches returned from ESPN API.');
            return self::FAILURE;
        }

        $this->info(count($matches) . ' matches found. Syncing to database...');
        $this->newLine();

        // Create or find the season record
        $season = $api->findOrCreateSeason($year);
        $this->info("Season: {$season->name} (ID: {$season->id})");
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $tossCount = 0;

        foreach ($matches as $match) {
            // Skip if teams are missing
            if (empty($match['team_a_short']) || empty($match['team_b_short'])) {
                $skipped++;
                continue;
            }

            // Find or create team records
            $teamA = $api->findOrCreateTeam($match['team_a_raw']);
            $teamB = $api->findOrCreateTeam($match['team_b_raw']);

            // Find existing by espn_id or by teams + date
            $existing = IplMatch::where('espn_id', $match['espn_id'])->first();

            if (!$existing && $match['match_date']) {
                $existing = IplMatch::where('team_a_short', $match['team_a_short'])
                    ->where('team_b_short', $match['team_b_short'])
                    ->whereDate('match_date', $match['match_date']->toDateString())
                    ->first();
            }

            // Fetch toss for completed/live matches
            $tossWinner = null;
            $tossDecision = null;
            if ($withToss && in_array($match['status'], ['completed', 'live']) && $match['espn_id']) {
                $toss = $api->fetchToss($match['espn_id']);
                if ($toss && $toss['toss_winner']) {
                    // Convert full team name to short code
                    $tossWinner = $this->resolveTeamShortCode($toss['toss_winner'], $match);
                    $tossDecision = $toss['toss_decision'];
                    $tossCount++;
                }
            }

            if ($existing) {
                $updateData = [
                    'espn_id'      => $match['espn_id'],
                    'season_id'    => $season->id,
                    'team_a_id'    => $teamA->id,
                    'team_b_id'    => $teamB->id,
                    'team_a_logo'  => $match['team_a_logo'],
                    'team_b_logo'  => $match['team_b_logo'],
                    'venue'        => $match['venue'] ?? $existing->venue,
                ];

                // Always update scores
                if ($match['score_a']) {
                    $updateData['score_a'] = $match['score_a'];
                }
                if ($match['score_b']) {
                    $updateData['score_b'] = $match['score_b'];
                }

                // Update toss if fetched
                if ($tossWinner) {
                    $updateData['toss_winner'] = $tossWinner;
                    $updateData['toss_decision'] = $tossDecision;
                }

                if ($existing->status === 'upcoming') {
                    $updateData['match_date'] = $match['match_date'];
                }

                // Update results for completed matches
                if ($match['status'] === 'completed' && $existing->status !== 'completed') {
                    $updateData['status'] = 'completed';
                    $updateData['winning_team'] = $match['winning_team'];
                    $updateData['notes'] = $match['summary'];
                }

                // Also update notes/summary for already-completed matches if missing
                if ($match['status'] === 'completed' && !$existing->notes && $match['summary']) {
                    $updateData['notes'] = $match['summary'];
                }

                // Update live status
                if ($match['status'] === 'live' && $existing->status === 'upcoming') {
                    $updateData['status'] = 'live';
                }

                $existing->update($updateData);

                $statusIcon = match ($existing->fresh()->status) {
                    'completed' => '<fg=green>DONE</>',
                    'live'      => '<fg=red>LIVE</>',
                    'cancelled' => '<fg=gray>CNCL</>',
                    default     => '<fg=yellow>UPDT</>',
                };
                $scoreInfo = $match['score_a'] ? " | {$match['score_a']} vs {$match['score_b']}" : '';
                $tossInfo = $tossWinner ? " | Toss: {$tossWinner} ({$tossDecision})" : '';
                $this->line("  {$statusIcon} #{$match['match_number']} {$match['team_a_short']} vs {$match['team_b_short']}{$scoreInfo}{$tossInfo}");
                $updated++;
            } else {
                $status = $match['status'];
                $winningTeam = ($match['status'] === 'completed') ? $match['winning_team'] : null;

                IplMatch::create([
                    'espn_id'        => $match['espn_id'],
                    'season_id'      => $season->id,
                    'team_a_id'      => $teamA->id,
                    'team_b_id'      => $teamB->id,
                    'match_number'   => $match['match_number'],
                    'team_a'         => $match['team_a'],
                    'team_b'         => $match['team_b'],
                    'team_a_short'   => $match['team_a_short'],
                    'team_b_short'   => $match['team_b_short'],
                    'team_a_logo'    => $match['team_a_logo'],
                    'team_b_logo'    => $match['team_b_logo'],
                    'score_a'        => $match['score_a'],
                    'score_b'        => $match['score_b'],
                    'match_date'     => $match['match_date'],
                    'venue'          => $match['venue'],
                    'season'         => "IPL {$year}",
                    'status'         => $status,
                    'winning_team'   => $winningTeam,
                    'toss_winner'    => $tossWinner,
                    'toss_decision'  => $tossDecision,
                    'win_multiplier' => 2.00,
                    'notes'          => $match['summary'],
                ]);

                $statusIcon = $status === 'completed' ? '<fg=green>DONE</>' : '<fg=cyan>NEW</>';
                $scoreInfo = $match['score_a'] ? " | {$match['score_a']} vs {$match['score_b']}" : '';
                $this->line("  {$statusIcon} #{$match['match_number']} {$match['team_a_short']} vs {$match['team_b_short']}{$scoreInfo}");
                $created++;
            }
        }

        $this->newLine();
        $this->table(
            ['Created', 'Updated', 'Skipped', 'Toss Fetched', 'Total'],
            [[$created, $updated, $skipped, $tossCount, count($matches)]]
        );

        $this->newLine();
        $this->info("Teams in database: " . \App\Models\Team::count());
        $this->info("Seasons in database: " . \App\Models\Season::count());

        return self::SUCCESS;
    }

    /**
     * Convert full team name from toss text to short code.
     */
    protected function resolveTeamShortCode(string $fullName, array $match): string
    {
        $fullNameLower = strtolower(trim($fullName));

        // Check against team_a and team_b full names
        if (str_contains(strtolower($match['team_a']), $fullNameLower) ||
            str_contains($fullNameLower, strtolower($match['team_a']))) {
            return $match['team_a_short'];
        }

        if (str_contains(strtolower($match['team_b']), $fullNameLower) ||
            str_contains($fullNameLower, strtolower($match['team_b']))) {
            return $match['team_b_short'];
        }

        // Try partial match on key words
        $teamAWords = explode(' ', strtolower($match['team_a']));
        $teamBWords = explode(' ', strtolower($match['team_b']));

        foreach ($teamAWords as $word) {
            if (strlen($word) > 3 && str_contains($fullNameLower, $word)) {
                return $match['team_a_short'];
            }
        }

        foreach ($teamBWords as $word) {
            if (strlen($word) > 3 && str_contains($fullNameLower, $word)) {
                return $match['team_b_short'];
            }
        }

        // Fallback: return the full name as-is
        return $fullName;
    }
}
