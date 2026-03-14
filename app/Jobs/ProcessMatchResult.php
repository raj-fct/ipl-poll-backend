<?php

namespace App\Jobs;

use App\Models\Match;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMatchResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int    $matchId,
        public readonly string $winningTeam
    ) {}

    public function handle(): void
    {
        $match = Match::with('polls.user')->findOrFail($this->matchId);

        if ($match->status === 'completed') {
            Log::warning("ProcessMatchResult: Match #{$this->matchId} already completed. Skipping.");
            return;
        }

        Log::info("Settling Match #{$match->match_number}. Winner: {$this->winningTeam}");

        $stats = ['winners' => 0, 'losers' => 0, 'coins_distributed' => 0, 'errors' => 0];

        foreach ($match->polls as $poll) {
            try {
                DB::transaction(function () use ($poll, $match, &$stats) {
                    if ($poll->isWinner($this->winningTeam)) {
                        $winnings = $poll->calculateWinnings($match->win_multiplier);

                        $poll->user->creditCoins(
                            $winnings,
                            'win_credit',
                            "Won Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short}",
                            $poll
                        );

                        $poll->update(['status' => 'won', 'coins_earned' => $winnings]);
                        $stats['winners']++;
                        $stats['coins_distributed'] += $winnings;
                    } else {
                        $poll->update(['status' => 'lost', 'coins_earned' => 0]);
                        $stats['losers']++;
                    }
                });
            } catch (\Throwable $e) {
                Log::error("Error settling poll #{$poll->id}: {$e->getMessage()}");
                $stats['errors']++;
            }
        }

        $match->update(['status' => 'completed', 'winning_team' => $this->winningTeam]);

        Log::info("Match #{$match->match_number} settled.", $stats);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessMatchResult FAILED for match #{$this->matchId}: {$e->getMessage()}");
    }
}
