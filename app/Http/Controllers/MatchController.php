<?php

namespace App\Http\Controllers;

use App\Models\Match;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $matches = Match::with(['polls' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('match_date')
            ->get()
            ->map(fn ($m) => $this->matchResource($m));

        return response()->json(['matches' => $matches]);
    }

    public function show(Request $request, Match $match): JsonResponse
    {
        $userId = $request->user()->id;
        $match->load(['polls' => fn ($q) => $q->where('user_id', $userId)]);

        $total      = $match->polls()->count();
        $teamACount = $match->polls()->where('selected_team', $match->team_a_short)->count();

        return response()->json([
            'match' => $this->matchResource($match),
            'stats' => [
                'total_polls'       => $total,
                'team_a_polls'      => $teamACount,
                'team_b_polls'      => $total - $teamACount,
                'team_a_percentage' => $total ? round($teamACount / $total * 100) : 0,
            ],
        ]);
    }

    private function matchResource(Match $match): array
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
