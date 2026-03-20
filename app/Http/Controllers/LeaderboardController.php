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
        $leaders = User::select('id', 'name', 'mobile', 'coin_balance')
            ->where('is_admin', false)
            ->where('is_active', true)
            ->orderByDesc('coin_balance')
            ->limit(50)
            ->get()
            ->values()
            ->map(fn ($u, $i) => [
                'rank'          => $i + 1,
                'id'            => $u->id,
                'name'          => $u->name,
                'mobile_masked' => substr($u->mobile, 0, 3) . '****' . substr($u->mobile, -3),
                'coin_balance'  => $u->coin_balance,
            ]);

        return response()->json(['leaderboard' => $leaders]);
    }

    public function wins(): JsonResponse
    {
        $leaders = User::select('users.id', 'users.name', 'users.mobile', DB::raw('COUNT(polls.id) as total_wins'))
            ->leftJoin('polls', function ($join) {
                $join->on('users.id', '=', 'polls.user_id')
                     ->where('polls.status', '=', 'won');
            })
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.name', 'users.mobile')
            ->orderByDesc('total_wins')
            ->limit(50)
            ->get()
            ->values()
            ->map(fn ($u, $i) => [
                'rank'          => $i + 1,
                'id'            => $u->id,
                'name'          => $u->name,
                'mobile_masked' => substr($u->mobile, 0, 3) . '****' . substr($u->mobile, -3),
                'total_wins'    => (int) $u->total_wins,
            ]);

        return response()->json(['leaderboard' => $leaders]);
    }

    public function myRank(Request $request): JsonResponse
    {
        $user = $request->user();

        $coinRank = User::where('coin_balance', '>', $user->coin_balance)
            ->where('is_admin', false)
            ->where('is_active', true)
            ->count() + 1;

        $myWins = $user->polls()->where('status', 'won')->count();

        $winsRank = DB::table('users')
            ->leftJoin('polls', function ($join) {
                $join->on('users.id', '=', 'polls.user_id')
                     ->where('polls.status', '=', 'won');
            })
            ->where('users.is_admin', false)
            ->where('users.is_active', true)
            ->groupBy('users.id')
            ->havingRaw('COUNT(polls.id) > ?', [$myWins])
            ->count() + 1;

        return response()->json([
            'rank'         => $coinRank,
            'coin_balance' => $user->coin_balance,
            'name'         => $user->name,
            'wins_rank'    => $winsRank,
            'total_wins'   => $myWins,
        ]);
    }
}
