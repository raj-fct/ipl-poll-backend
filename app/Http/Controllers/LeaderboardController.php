<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Include pending bid amounts so other users cannot deduce bid amounts
        $leaders = User::select(
                'users.id', 'users.name', 'users.mobile', 'users.coin_balance',
                DB::raw('COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0) as pending_coins')
            )
            ->leftJoin('polls', 'users.id', '=', 'polls.user_id')
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.name', 'users.mobile', 'users.coin_balance')
            ->orderByRaw('(users.coin_balance + COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0)) DESC')
            ->orderBy('users.name', 'asc')
            ->limit(50)
            ->get()
            ->values()
            ->map(fn ($u, $i) => [
                'rank'          => $i + 1,
                'id'            => $u->id,
                'name'          => $u->name,
                'mobile_masked' => substr($u->mobile, 0, 3) . '****' . substr($u->mobile, -3),
                'coin_balance'  => $u->coin_balance + (int) $u->pending_coins,
            ]);

        return response()->json(['leaderboard' => $leaders]);
    }

    public function wins(): JsonResponse
    {
        $leaders = User::select(
                'users.id', 'users.name', 'users.mobile', 'users.coin_balance',
                DB::raw('COUNT(CASE WHEN polls.status = \'won\' THEN 1 END) as total_wins'),
                DB::raw('COUNT(CASE WHEN polls.status IN (\'won\', \'lost\') THEN 1 END) as total_polls'),
                DB::raw('COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0) as pending_coins')
            )
            ->leftJoin('polls', 'users.id', '=', 'polls.user_id')
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.name', 'users.mobile', 'users.coin_balance')
            ->orderByDesc('total_wins')
            ->orderByRaw('(users.coin_balance + COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0)) DESC')
            ->orderBy('users.name', 'asc')
            ->limit(50)
            ->get()
            ->values()
            ->map(fn ($u, $i) => [
                'rank'          => $i + 1,
                'id'            => $u->id,
                'name'          => $u->name,
                'mobile_masked' => substr($u->mobile, 0, 3) . '****' . substr($u->mobile, -3),
                'total_wins'    => (int) $u->total_wins,
                'total_polls'   => (int) $u->total_polls,
                'win_rate'      => $u->total_polls > 0 ? round((int) $u->total_wins / (int) $u->total_polls * 100, 1) : 0,
            ]);

        return response()->json(['leaderboard' => $leaders]);
    }

    public function myRank(Request $request): JsonResponse
    {
        $user = $request->user();

        // Include pending bids in effective balance
        $myPending = (int) $user->polls()->where('status', 'pending')->sum('bid_amount');
        $myEffective = $user->coin_balance + $myPending;

        // Rank by effective balance (coin_balance + pending bids)
        $coinRank = DB::table('users')
            ->leftJoin('polls', function ($join) {
                $join->on('users.id', '=', 'polls.user_id')
                     ->where('polls.status', '=', 'pending');
            })
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.coin_balance', 'users.name')
            ->havingRaw('(users.coin_balance + COALESCE(SUM(polls.bid_amount), 0)) > ? OR ((users.coin_balance + COALESCE(SUM(polls.bid_amount), 0)) = ? AND users.name < ?)', [$myEffective, $myEffective, $user->name])
            ->count() + 1;

        $myWins = $user->polls()->where('status', 'won')->count();

        $winsRank = DB::table('users')
            ->leftJoin('polls', 'users.id', '=', 'polls.user_id')
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.coin_balance', 'users.name')
            ->havingRaw(
                'COUNT(CASE WHEN polls.status = \'won\' THEN 1 END) > ? OR (COUNT(CASE WHEN polls.status = \'won\' THEN 1 END) = ? AND (users.coin_balance + COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0)) > ?) OR (COUNT(CASE WHEN polls.status = \'won\' THEN 1 END) = ? AND (users.coin_balance + COALESCE(SUM(CASE WHEN polls.status = \'pending\' THEN polls.bid_amount ELSE 0 END), 0)) = ? AND users.name < ?)',
                [$myWins, $myWins, $myEffective, $myWins, $myEffective, $user->name]
            )
            ->count() + 1;

        return response()->json([
            'rank'         => $coinRank,
            'coin_balance' => $myEffective,
            'name'         => $user->name,
            'wins_rank'    => $winsRank,
            'total_wins'   => $myWins,
        ]);
    }
}
