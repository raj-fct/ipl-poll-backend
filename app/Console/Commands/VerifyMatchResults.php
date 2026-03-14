<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use App\Models\Setting;
use App\Services\CricketApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyMatchResults extends Command
{
    protected $signature = 'ipl:verify-results
                            {--dry-run : Preview results without updating}
                            {--all : Check all matches, not just today\'s}';

    protected $description = 'Check ESPNcricinfo for completed match results and auto-settle coins';

    public function handle(CricketApiService $api): int
    {
        $this->info('Fetching latest match data from ESPNcricinfo...');

        $seasonLabel = Setting::get('season', 'IPL 2026');
        preg_match('/(\d{4})/', $seasonLabel, $m);
        $year = (int) ($m[1] ?? now()->year);

        // Fetch all matches from ESPN
        $espnMatches = $api->fetchMatches($year);

        if (empty($espnMatches)) {
            $this->error('Could not fetch data from ESPN API.');
            return self::FAILURE;
        }

        $this->info(count($espnMatches) . ' matches fetched from ESPN.');
        $this->newLine();

        // Get our pending matches
        $query = IplMatch::whereIn('status', ['upcoming', 'live']);
        if (!$this->option('all')) {
            $query->where('match_date', '<=', now()->addHours(6));
        }
        $pendingMatches = $query->orderBy('match_date')->get();

        if ($pendingMatches->isEmpty()) {
            $this->info('No pending matches to verify.');
            return self::SUCCESS;
        }

        $this->info("{$pendingMatches->count()} pending match(es) in database to check.");
        $this->newLine();

        // Index ESPN matches by espn_id and by team pair
        $espnById = collect($espnMatches)->keyBy('espn_id');
        $espnByTeams = collect($espnMatches)->keyBy(fn ($m) => $m['team_a_short'] . '_' . $m['team_b_short']);

        $settled = 0;
        $wentLive = 0;
        $noResult = 0;
        $notFound = 0;

        foreach ($pendingMatches as $match) {
            $this->line("Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short}");

            // Find matching ESPN data
            $espn = null;
            if ($match->espn_id) {
                $espn = $espnById[$match->espn_id] ?? null;
            }
            if (!$espn) {
                $espn = $espnByTeams[$match->team_a_short . '_' . $match->team_b_short] ?? null;
            }
            if (!$espn) {
                $espn = $espnByTeams[$match->team_b_short . '_' . $match->team_a_short] ?? null;
            }

            if (!$espn) {
                $this->warn("  Not found in ESPN data. Skipping.");
                $notFound++;
                continue;
            }

            // Update espn_id if missing
            if (!$match->espn_id && $espn['espn_id']) {
                $match->update(['espn_id' => $espn['espn_id']]);
            }

            $this->line("  ESPN Status: {$espn['status']} | Score: {$espn['score_a']} vs {$espn['score_b']}");

            if ($espn['summary']) {
                $this->line("  Summary: {$espn['summary']}");
            }

            // Match went live
            if ($espn['status'] === 'live' && $match->status === 'upcoming') {
                if ($this->option('dry-run')) {
                    $this->info("  [DRY RUN] Would mark as LIVE");
                } else {
                    $match->update(['status' => 'live']);
                    $this->info("  -> Marked as LIVE");
                }
                $wentLive++;
                continue;
            }

            // Match completed with a winner
            if ($espn['status'] === 'completed' && $espn['winning_team']) {
                $winner = $espn['winning_team'];

                // Verify winner is valid for this match
                if (!in_array($winner, $match->getTeams())) {
                    // Try swapped order
                    $this->warn("  Winner '{$winner}' not in [{$match->team_a_short}, {$match->team_b_short}]. Skipping.");
                    $noResult++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->info("  [DRY RUN] Would settle — Winner: {$winner}");
                } else {
                    ProcessMatchResult::dispatch($match->id, $winner);
                    $this->info("  -> Winner: {$winner} — Settlement job dispatched!");
                    Log::info("Auto-settled Match #{$match->match_number} ({$match->team_a_short} vs {$match->team_b_short}) — Winner: {$winner}");
                }
                $settled++;
                continue;
            }

            // Completed but no winner (abandoned, no result, tied)
            if ($espn['status'] === 'completed' && !$espn['winning_team']) {
                $this->warn("  Completed but NO winner (no result / abandoned). Needs manual review.");
                $noResult++;
                continue;
            }

            $this->line("  No update needed.");
        }

        $this->newLine();
        $this->table(
            ['Settled', 'Went Live', 'No Result', 'Not Found'],
            [[$settled, $wentLive, $noResult, $notFound]]
        );

        if ($settled > 0 && !$this->option('dry-run')) {
            $this->newLine();
            $this->info("Run 'php artisan queue:work' to process settlement jobs.");
        }

        return self::SUCCESS;
    }
}
