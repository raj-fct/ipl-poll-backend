<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use Illuminate\Console\Command;

class SetMatchWinner extends Command
{
    protected $signature = 'match:set-winner
                            {match : Match ID or match_number}
                            {team : Winning team short code (e.g. KKR, CSK)}
                            {--dry-run : Show what would happen without committing}';

    protected $description = 'Manually set the winner for a match and settle pending polls (covers ties, Super Over, DLS, abandoned).';

    public function handle(): int
    {
        $matchArg = $this->argument('match');
        $team     = strtoupper(trim($this->argument('team')));
        $dryRun   = $this->option('dry-run');

        // Resolve by id first, then by match_number
        $match = IplMatch::find($matchArg) ?? IplMatch::where('match_number', $matchArg)->first();

        if (!$match) {
            $this->error("Match not found by id or match_number: {$matchArg}");
            return self::FAILURE;
        }

        $teams = [$match->team_a_short, $match->team_b_short];
        if (!in_array($team, $teams, true)) {
            $this->error("Team '{$team}' is not in this match. Expected one of: " . implode(', ', $teams));
            return self::FAILURE;
        }

        $pending = $match->polls()->where('status', 'pending')->count();
        $alreadySettled = $match->polls()->whereIn('status', ['won', 'lost'])->count();

        $this->info("Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short}");
        $this->line("  Current status:       {$match->status}");
        $this->line("  Current winning_team: " . ($match->winning_team ?? 'NULL'));
        $this->line("  Polls pending:        {$pending}");
        $this->line("  Polls already settled: {$alreadySettled}");
        $this->line("  -> Will set winner to: {$team} and credit pending winners.");

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes committed.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Proceed?', true)) {
            $this->warn('Cancelled.');
            return self::FAILURE;
        }

        // Set winner up-front so the match record is correct even if no polls exist.
        $match->update(['winning_team' => $team]);

        // dispatchSync runs the job immediately on the current process — no queue worker needed.
        ProcessMatchResult::dispatchSync($match->id, $team);

        $this->info('Done. Pending polls have been settled (winners credited, losers marked lost).');

        return self::SUCCESS;
    }
}
