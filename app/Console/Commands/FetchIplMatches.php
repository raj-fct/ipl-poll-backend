<?php

namespace App\Console\Commands;

use App\Models\IplMatch;
use App\Models\Setting;
use App\Services\CricketApiService;
use Illuminate\Console\Command;

class FetchIplMatches extends Command
{
    protected $signature = 'ipl:fetch-matches
                            {--season= : Season year e.g. 2026 (auto-detected from settings)}';

    protected $description = 'Fetch all IPL matches from ESPNcricinfo and sync to database';

    public function handle(CricketApiService $api): int
    {
        $seasonLabel = $this->option('season')
            ?: Setting::get('season', 'IPL 2026');

        // Extract year from "IPL 2026" or just "2026"
        preg_match('/(\d{4})/', $seasonLabel, $m);
        $year = (int) ($m[1] ?? now()->year);

        $this->info("Fetching IPL {$year} matches from ESPNcricinfo...");
        $this->newLine();

        $matches = $api->fetchMatches($year);

        if (empty($matches)) {
            $this->error('No matches returned from ESPN API.');
            return self::FAILURE;
        }

        $this->info(count($matches) . ' matches found. Syncing to database...');
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            // Skip if teams are missing
            if (empty($match['team_a_short']) || empty($match['team_b_short'])) {
                $skipped++;
                continue;
            }

            // Find existing by espn_id or by teams + date
            $existing = IplMatch::where('espn_id', $match['espn_id'])->first();

            if (!$existing && $match['match_date']) {
                $existing = IplMatch::where('team_a_short', $match['team_a_short'])
                    ->where('team_b_short', $match['team_b_short'])
                    ->whereDate('match_date', $match['match_date']->toDateString())
                    ->first();
            }

            if ($existing) {
                // Update venue, logos, date if match not manually settled
                $updateData = [
                    'espn_id'      => $match['espn_id'],
                    'team_a_logo'  => $match['team_a_logo'],
                    'team_b_logo'  => $match['team_b_logo'],
                    'venue'        => $match['venue'] ?? $existing->venue,
                ];

                if ($existing->status === 'upcoming') {
                    $updateData['match_date'] = $match['match_date'];
                }

                $existing->update($updateData);

                $statusIcon = match ($existing->status) {
                    'completed' => '<fg=green>DONE</>',
                    'live'      => '<fg=red>LIVE</>',
                    'cancelled' => '<fg=gray>CNCL</>',
                    default     => '<fg=yellow>UPDT</>',
                };
                $this->line("  {$statusIcon} #{$match['match_number']} {$match['team_a_short']} vs {$match['team_b_short']} — updated");
                $updated++;
            } else {
                IplMatch::create([
                    'espn_id'        => $match['espn_id'],
                    'match_number'   => $match['match_number'],
                    'team_a'         => $match['team_a'],
                    'team_b'         => $match['team_b'],
                    'team_a_short'   => $match['team_a_short'],
                    'team_b_short'   => $match['team_b_short'],
                    'team_a_logo'    => $match['team_a_logo'],
                    'team_b_logo'    => $match['team_b_logo'],
                    'match_date'     => $match['match_date'],
                    'venue'          => $match['venue'],
                    'season'         => "IPL {$year}",
                    'status'         => 'upcoming',
                    'win_multiplier' => 1.90,
                ]);

                $this->line("  <fg=green>NEW</> #{$match['match_number']} {$match['team_a_short']} vs {$match['team_b_short']} — {$match['match_date']->format('d M Y, h:i A')}");
                $created++;
            }
        }

        $this->newLine();
        $this->table(
            ['Created', 'Updated', 'Skipped', 'Total'],
            [[$created, $updated, $skipped, count($matches)]]
        );

        return self::SUCCESS;
    }
}
