<?php

namespace App\Http\Controllers;

use App\Models\IplMatch;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $matches = IplMatch::with(['polls' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('match_date')
            ->get()
            ->map(fn ($m) => $this->matchResource($m));

        return response()->json(['matches' => $matches]);
    }

    public function show(Request $request, IplMatch $match): JsonResponse
    {
        $userId = $request->user()->id;
        $match->load(['polls' => fn ($q) => $q->where('user_id', $userId)]);

        $total      = $match->polls()->where('status', '!=', 'refunded')->count();
        $teamACount = $match->polls()->where('status', '!=', 'refunded')->where('selected_team', $match->team_a_short)->count();

        return response()->json([
            'match' => $this->matchResource($match),
            'stats' => [
                'total_polls'       => $total,
                'team_a_polls'      => $teamACount,
                'team_b_polls'      => $total - $teamACount,
                'team_a_percentage' => $total ? round($teamACount / $total * 100) : 0,
            ],
            'max_bid_percent' => (int) Setting::get('max_bid_percent', 50),
        ]);
    }

    public function polls(IplMatch $match): JsonResponse
    {
        $pollsClosed = $match->isPollsClosed();

        $isCompleted = in_array($match->status, ['completed', 'cancelled']);

        $query = $match->polls()
            ->where('status', '!=', 'refunded')
            ->with('user:id,name');

        if ($isCompleted) {
            // Completed: winners first (highest earned), then losers (lowest bid first)
            $query->orderByRaw("FIELD(status, 'won', 'lost', 'pending')")
                  ->orderByRaw("CASE WHEN status = 'won' THEN coins_earned END DESC")
                  ->orderByRaw("CASE WHEN status = 'lost' THEN bid_amount END ASC");
        } else {
            // Pending: highest bid to lowest
            $query->orderByDesc('bid_amount');
        }

        $polls = $query->get()
            ->map(fn ($poll) => [
                'user_name'     => $poll->user->name ?? 'User',
                'selected_team' => $poll->selected_team,
                'bid_amount'    => $poll->bid_amount,
                'status'        => $poll->status,
                'coins_earned'  => $poll->coins_earned,
            ]);

        return response()->json([
            'polls'        => $polls,
            'polls_closed' => $pollsClosed,
            'match'        => [
                'team_a_short' => $match->team_a_short,
                'team_b_short' => $match->team_b_short,
                'team_a_logo'  => $match->team_a_logo,
                'team_b_logo'  => $match->team_b_logo,
            ],
        ]);
    }

    private function matchResource(IplMatch $match): array
    {
        $userPoll = $match->polls->first();

        return [
            'id'             => $match->id,
            'match_number'   => $match->match_number,
            'team_a'         => $match->team_a,
            'team_b'         => $match->team_b,
            'team_a_short'   => $match->team_a_short,
            'team_b_short'   => $match->team_b_short,
            'team_a_logo'    => $match->team_a_logo,
            'team_b_logo'    => $match->team_b_logo,
            'match_date'     => $match->match_date->toIso8601String(),
            'venue'          => $match->venue,
            'season'         => $match->season,
            'status'         => $match->status,
            'winning_team'   => $match->winning_team,
            'score_a'        => $match->score_a,
            'score_b'        => $match->score_b,
            'toss_winner'    => $match->toss_winner,
            'toss_decision'  => $match->toss_decision,
            'notes'          => $match->notes,
            'win_multiplier' => $match->win_multiplier,
            'is_locked'      => $match->isLocked(),
            'user_poll'      => $userPoll ? [
                'id'            => $userPoll->id,
                'selected_team' => $userPoll->selected_team,
                'bid_amount'    => $userPoll->bid_amount,
                'status'        => $userPoll->status,
                'coins_earned'  => $userPoll->coins_earned,
            ] : null,
        ];
    }
}
