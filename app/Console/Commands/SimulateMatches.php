<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use App\Models\CoinTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateMatches extends Command
{
    protected $signature = 'matches:simulate {--reset : Reset matches before simulating}';
    protected $description = 'Simulate first 4 matches for testing';

    public function handle(): int
    {
        $matches = IplMatch::orderBy('match_number')->limit(4)->get();

        if ($matches->count() < 4) {
            $this->error('Need at least 4 matches in the database.');
            return 1;
        }

        // Always reset first 4 matches to undo any previous simulation
        $this->info('Resetting first 4 matches...');
        foreach ($matches as $match) {
            DB::transaction(function () use ($match) {
                // Reverse all win credits and loss markings for this match
                foreach ($match->polls()->with('user')->get() as $poll) {
                    if ($poll->status === 'won' && $poll->coins_earned > 0) {
                        // Reverse the win credit
                        $poll->user->debitCoins(
                            $poll->coins_earned,
                            'reversal',
                            "Reversed win for Match #{$match->match_number} (simulation reset)"
                        );
                    }

                    // Reset poll back to pending (only won/lost, not refunded)
                    if (in_array($poll->status, ['won', 'lost'])) {
                        $poll->update(['status' => 'pending', 'coins_earned' => 0]);
                    }
                }

                // Reset match
                $match->update([
                    'status'       => 'upcoming',
                    'winning_team' => null,
                ]);
            });
            $this->warn("  Reset Match #{$match->match_number}");
        }

        $this->newLine();

        // Match 1 (SRH vs RCB): SRH wins
        $m1 = $matches[0];
        $m1->update(['status' => 'live']);
        ProcessMatchResult::dispatchSync($m1->id, 'SRH');
        $this->info("Match #{$m1->match_number} ({$m1->team_a_short} vs {$m1->team_b_short}): SRH wins");

        // Match 2 (MI vs KKR): KKR wins
        $m2 = $matches[1];
        $m2->update(['status' => 'live']);
        ProcessMatchResult::dispatchSync($m2->id, 'KKR');
        $this->info("Match #{$m2->match_number} ({$m2->team_a_short} vs {$m2->team_b_short}): KKR wins");

        // Match 3: In progress (live)
        $m3 = $matches[2];
        $m3->update(['status' => 'live']);
        $this->info("Match #{$m3->match_number} ({$m3->team_a_short} vs {$m3->team_b_short}): set to LIVE");

        // Match 4: Bids closed (upcoming, match in 10 min — within 30 min cutoff)
        $m4 = $matches[3];
        $m4->update([
            'status'     => 'upcoming',
            'match_date' => now()->addMinutes(10),
        ]);
        $this->info("Match #{$m4->match_number} ({$m4->team_a_short} vs {$m4->team_b_short}): polls closed (match in 10 min)");

        $this->newLine();
        $this->info('Simulation complete!');

        return 0;
    }
}