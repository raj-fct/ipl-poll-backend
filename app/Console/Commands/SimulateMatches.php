<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use Illuminate\Console\Command;

class SimulateMatches extends Command
{
    protected $signature = 'matches:simulate';
    protected $description = 'Simulate first 4 matches for testing';

    public function handle(): int
    {
        $matches = IplMatch::orderBy('match_number')->limit(4)->get();

        if ($matches->count() < 4) {
            $this->error('Need at least 4 matches in the database.');
            return 1;
        }

        // Match 1: SRH wins — settle via job (sync)
        $m1 = $matches[0];
        if ($m1->status !== 'completed') {
            $m1->update(['status' => 'live']); // ensure it's not "upcoming"
            ProcessMatchResult::dispatchSync($m1->id, 'SRH');
            $this->info("Match #{$m1->match_number} ({$m1->team_a_short} vs {$m1->team_b_short}): SRH wins ✓");
        } else {
            $this->warn("Match #{$m1->match_number} already completed, skipping.");
        }

        // Match 2: KKR wins — settle via job (sync)
        $m2 = $matches[1];
        if ($m2->status !== 'completed') {
            $m2->update(['status' => 'live']);
            ProcessMatchResult::dispatchSync($m2->id, 'KKR');
            $this->info("Match #{$m2->match_number} ({$m2->team_a_short} vs {$m2->team_b_short}): KKR wins ✓");
        } else {
            $this->warn("Match #{$m2->match_number} already completed, skipping.");
        }

        // Match 3: In progress (live)
        $m3 = $matches[2];
        $m3->update(['status' => 'live']);
        $this->info("Match #{$m3->match_number} ({$m3->team_a_short} vs {$m3->team_b_short}): set to LIVE ✓");

        // Match 4: Bids closed (upcoming but within 30 min — set match_date to now + 10 min)
        $m4 = $matches[3];
        $m4->update([
            'status'     => 'upcoming',
            'match_date' => now()->addMinutes(10),
        ]);
        $this->info("Match #{$m4->match_number} ({$m4->team_a_short} vs {$m4->team_b_short}): polls closed (match in 10 min) ✓");

        $this->newLine();
        $this->info('Simulation complete! Coins settled for matches 1 & 2.');

        return 0;
    }
}