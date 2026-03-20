<?php

namespace App\Http\Controllers;

use App\Models\IplMatch;
use App\Models\Poll;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'match_id'      => 'required|exists:matches,id',
            'selected_team' => 'required|string',
            'bid_amount'    => 'required|integer|min:1',
        ]);

        $match = IplMatch::findOrFail($data['match_id']);
        $user  = $request->user();

        if ($match->isPollsClosed()) {
            return response()->json(['message' => 'Time up! Polls close 30 minutes before the match.'], 422);
        }

        if (! in_array($data['selected_team'], $match->getTeams())) {
            return response()->json(['message' => 'Invalid team selection.'], 422);
        }

        $existingPoll = Poll::where('user_id', $user->id)->where('match_id', $match->id)->first();

        if ($existingPoll && $existingPoll->status !== 'refunded') {
            return response()->json(['message' => 'You already have a poll for this match. Use the update endpoint.'], 422);
        }

        $minBid = (int) Setting::get('min_bid', 10);
        $maxBid = (int) Setting::get('max_bid', 5000);

        if ($data['bid_amount'] < $minBid || $data['bid_amount'] > $maxBid) {
            return response()->json(['message' => "Bid must be between {$minBid} and {$maxBid} coins."], 422);
        }

        if ($user->coin_balance < $data['bid_amount']) {
            return response()->json(['message' => 'Insufficient coin balance.'], 422);
        }

        $poll = DB::transaction(function () use ($user, $match, $data, $existingPoll) {
            if ($existingPoll) {
                $existingPoll->update([
                    'selected_team' => $data['selected_team'],
                    'bid_amount'    => $data['bid_amount'],
                    'status'        => 'pending',
                    'coins_earned'  => 0,
                ]);
                $poll = $existingPoll;
            } else {
                $poll = Poll::create([
                    'user_id'       => $user->id,
                    'match_id'      => $match->id,
                    'selected_team' => $data['selected_team'],
                    'bid_amount'    => $data['bid_amount'],
                    'status'        => 'pending',
                ]);
            }

            $user->debitCoins(
                $data['bid_amount'],
                'bid_debit',
                "Bid on Match #{$match->match_number}: {$match->team_a_short} vs {$match->team_b_short}",
                $poll
            );

            return $poll;
        });

        return response()->json([
            'poll'    => $this->pollResource($poll),
            'message' => 'Poll placed successfully.',
        ], 201);
    }

    public function update(Request $request, Poll $poll): JsonResponse
    {
        $this->authorize('update', $poll);

        $data = $request->validate([
            'selected_team' => 'required|string',
            'bid_amount'    => 'nullable|integer|min:1',
        ]);

        $match = $poll->match;
        $user  = $request->user();

        if ($match->isPollsClosed()) {
            return response()->json(['message' => 'Time up! Polls close 30 minutes before the match.'], 422);
        }

        if (! in_array($data['selected_team'], $match->getTeams())) {
            return response()->json(['message' => 'Invalid team selection.'], 422);
        }

        DB::transaction(function () use ($poll, $user, $data) {
            $oldBid = $poll->bid_amount;
            $newBid = $data['bid_amount'] ?? $oldBid;

            if ($newBid !== $oldBid) {
                $user->creditCoins($oldBid, 'refund', "Refund for poll update – Match #{$poll->match->match_number}");
                $fresh = $user->fresh();
                if ($fresh->coin_balance < $newBid) {
                    throw new \Exception('Insufficient balance for new bid amount.');
                }
                $fresh->debitCoins($newBid, 'bid_debit', "Updated bid – Match #{$poll->match->match_number}", $poll);
            }

            $poll->update([
                'selected_team' => $data['selected_team'],
                'bid_amount'    => $newBid,
            ]);
        });

        return response()->json([
            'poll'    => $this->pollResource($poll->fresh()),
            'message' => 'Poll updated.',
        ]);
    }

    public function destroy(Request $request, Poll $poll): JsonResponse
    {
        $this->authorize('delete', $poll);

        $match = $poll->match;
        $user  = $request->user();

        if ($match->isPollsClosed()) {
            return response()->json(['message' => 'Time up! Polls close 30 minutes before the match.'], 422);
        }

        if ($poll->status !== 'pending') {
            return response()->json(['message' => 'Only pending polls can be cancelled.'], 422);
        }

        DB::transaction(function () use ($poll, $user) {
            $user->creditCoins(
                $poll->bid_amount,
                'refund',
                "Poll cancelled – Match #{$poll->match->match_number}: {$poll->match->team_a_short} vs {$poll->match->team_b_short}",
                $poll
            );

            $poll->update(['status' => 'refunded']);
        });

        return response()->json([
            'poll'    => $this->pollResource($poll->fresh()),
            'message' => 'Poll cancelled and coins refunded.',
        ]);
    }

    public function myPolls(Request $request): JsonResponse
    {
        $polls = $request->user()
            ->polls()
            ->with('match')
            ->latest()
            ->paginate(20);

        return response()->json([
            'polls' => $polls->map(fn ($p) => $this->pollResource($p)),
            'meta'  => ['total' => $polls->total(), 'last_page' => $polls->lastPage()],
        ]);
    }

    private function pollResource(Poll $poll): array
    {
        return [
            'id'            => $poll->id,
            'match_id'      => $poll->match_id,
            'selected_team' => $poll->selected_team,
            'bid_amount'    => $poll->bid_amount,
            'status'        => $poll->status,
            'coins_earned'  => $poll->coins_earned,
            'match'         => $poll->relationLoaded('match') ? [
                'match_number' => $poll->match->match_number,
                'team_a_short' => $poll->match->team_a_short,
                'team_b_short' => $poll->match->team_b_short,
                'team_a_logo'  => $poll->match->team_a_logo,
                'team_b_logo'  => $poll->match->team_b_logo,
                'match_date'   => $poll->match->match_date->toIso8601String(),
                'status'       => $poll->match->status,
                'winning_team' => $poll->match->winning_team,
            ] : null,
            'created_at'    => $poll->created_at->toIso8601String(),
            'updated_at'    => $poll->updated_at->toIso8601String(),
        ];
    }
}
