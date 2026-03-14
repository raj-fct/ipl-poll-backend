<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use App\Services\CricketApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyMatchResults extends Command
{
    protected $signature = 'ipl:verify-results
                            {--dry-run : Show results without updating}
                            {--force : Process even if match is already completed}';

    protected $description = 'Check CricAPI for completed match results and auto-settle coins';

    public function handle(CricketApiService $api): int
    {
        $this->info('Checking for match results...');

        // Get all non-completed, non-cancelled matches that have a cricapi_id
        $matches = IplMatch::whereIn('status', ['upcoming', 'live'])
            ->whereNotNull('cricapi_id')
            ->where('match_date', '<=', now())
            ->orderBy('match_date')
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No pending matches to verify.');
            return self::SUCCESS;
        }

        $this->info("{$matches->count()} match(es) to check.");

        $settled = 0;
        $live = 0;
        $noResult = 0;
        $errors = 0;

        foreach ($matches as $match) {
            $this->newLine();
            $this->line("Checking Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short}...");

            try {
                $apiMatch = $api->getMatchInfo($match->cricapi_id);

                if (!$apiMatch) {
                    $this->warn("  Could not fetch data from API. Skipping.");
                    $errors++;
                    continue;
                }

                $status = $api->determineMatchStatus($apiMatch);
                $winner = $api->parseWinnerFromStatus($apiMatch);

                $this->line("  API Status: {$apiMatch['status'] ?? 'unknown'}");
                $this->line("  Detected: status={$status}, winner=" . ($winner ?? 'none'));

                // Update to live if currently upcoming
                if ($status === 'live' && $match->status === 'upcoming') {
                    if (!$this->option('dry-run')) {
                        $match->update(['status' => 'live']);
                        $this->info("  -> Marked as LIVE");
                    } else {
                        $this->info("  -> [DRY RUN] Would mark as LIVE");
                    }
                    $live++;
                    continue;
                }

                // Process completed match
                if ($status === 'completed' && $winner) {
                    // Verify winner is a valid team for this match
                    if (!in_array($winner, $match->getTeams())) {
                        $this->error("  Winner '{$winner}' is not a valid team for this match ({$match->team_a_short} vs {$match->team_b_short}). Skipping.");
                        $errors++;
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->info("  -> [DRY RUN] Would settle: Winner = {$winner}");
                    } else {
                        // Dispatch settlement job
                        ProcessMatchResult::dispatch($match->id, $winner);
                        $this->info("  -> Result: {$winner} won! Settlement job dispatched.");

                        Log::info("Auto-settled Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short} — Winner: {$winner}");
                    }
                    $settled++;
                    continue;
                }

                // No result / match tied / abandoned
                if ($status === 'completed' && !$winner) {
                    $apiStatus = $apiMatch['status'] ?? '';
                    $this->warn("  Match completed but no winner (status: {$apiStatus}). May need manual review.");
                    $noResult++;
                    continue;
                }

                $this->line("  No update needed (still upcoming/in progress).");

            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error("VerifyMatchResults error for Match #{$match->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->table(
            ['Settled', 'Went Live', 'No Result', 'Errors'],
            [[$settled, $live, $noResult, $errors]]
        );

        if ($settled > 0 && !$this->option('dry-run')) {
            $this->info("Remember to run the queue worker: php artisan queue:work");
        }

        return self::SUCCESS;
    }
}
