<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMatchResult;
use App\Models\Match;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $matches = Match::withCount('polls')->orderBy('match_date')->get();
        return response()->json(['matches' => $matches]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'team_a'         => 'required|string|max:100',
            'team_b'         => 'required|string|max:100',
            'team_a_short'   => 'required|string|max:5',
            'team_b_short'   => 'required|string|max:5',
            'team_a_logo'    => 'nullable|url',
            'team_b_logo'    => 'nullable|url',
            'match_date'     => 'required|date',
            'venue'          => 'nullable|string|max:200',
            'match_number'   => 'required|integer|min:1',
            'season'         => 'required|string|max:50',
            'win_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'notes'          => 'nullable|string',
        ]);

        $match = Match::create($data);

        return response()->json(['match' => $match, 'message' => 'Match created.'], 201);
    }

    public function update(Request $request, Match $match): JsonResponse
    {
        $data = $request->validate([
            'team_a'         => 'sometimes|string|max:100',
            'team_b'         => 'sometimes|string|max:100',
            'team_a_short'   => 'sometimes|string|max:5',
            'team_b_short'   => 'sometimes|string|max:5',
            'team_a_logo'    => 'nullable|url',
            'team_b_logo'    => 'nullable|url',
            'match_date'     => 'sometimes|date',
            'venue'          => 'nullable|string|max:200',
            'win_multiplier' => 'nullable|numeric|min:1.0',
            'notes'          => 'nullable|string',
        ]);

        $match->update($data);

        return response()->json(['match' => $match, 'message' => 'Match updated.']);
    }

    public function updateStatus(Request $request, Match $match): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:upcoming,live,completed',
        ]);

        if ($match->status === 'completed') {
            return response()->json(['message' => 'Cannot change status of a completed match.'], 422);
        }

        $match->update(['status' => $data['status']]);

        return response()->json(['match' => $match, 'message' => 'Match status updated.']);
    }

    /**
     * Declare result and dispatch async settlement job.
     */
    public function setResult(Request $request, Match $match): JsonResponse
    {
        $teams = $match->getTeams();

        $data = $request->validate([
            'winning_team' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($teams) {
                    if (! in_array($value, $teams)) {
                        $fail("winning_team must be one of: " . implode(', ', $teams));
                    }
                },
            ],
        ]);

        if ($match->status === 'completed') {
            return response()->json(['message' => 'Result already declared for this match.'], 422);
        }

        ProcessMatchResult::dispatch($match->id, $data['winning_team']);

        return response()->json([
            'message'      => 'Result submitted. Coin settlement is processing in background.',
            'match_id'     => $match->id,
            'winning_team' => $data['winning_team'],
        ]);
    }
}
