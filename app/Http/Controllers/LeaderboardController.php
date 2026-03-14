<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function myRank(Request $request): JsonResponse
    {
        $user = $request->user();

        $rank = User::where('coin_balance', '>', $user->coin_balance)
            ->where('is_admin', false)
            ->where('is_active', true)
            ->count() + 1;

        return response()->json([
            'rank'         => $rank,
            'coin_balance' => $user->coin_balance,
            'name'         => $user->name,
        ]);
    }
}
