<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Match;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $userStats = [
            'total'    => User::where('is_admin', false)->count(),
            'active'   => User::where('is_admin', false)->where('is_active', true)->count(),
            'inactive' => User::where('is_admin', false)->where('is_active', false)->count(),
        ];

        $matchStats = Match::selectRaw("
            COUNT(*) as total,
            SUM(status = 'upcoming')  as upcoming,
            SUM(status = 'live')      as live,
            SUM(status = 'completed') as completed
        ")->first();

        $coinStats = [
            'total_in_circulation' => User::where('is_admin', false)->sum('coin_balance'),
            'total_bonus_given'    => CoinTransaction::where('type', 'bonus')->sum('amount'),
            'total_staked'         => abs(CoinTransaction::where('type', 'bid_debit')->sum('amount')),
            'total_won_by_users'   => CoinTransaction::where('type', 'win_credit')->sum('amount'),
        ];

        $pollStats = Poll::selectRaw("
            COUNT(*) as total,
            SUM(status = 'pending') as pending,
            SUM(status = 'won')     as won,
            SUM(status = 'lost')    as lost
        ")->first();

        $topUsers = User::where('is_admin', false)
            ->orderByDesc('coin_balance')
            ->limit(5)
            ->get(['id', 'name', 'mobile', 'coin_balance'])
            ->map(fn ($u) => [
                'id'            => $u->id,
                'name'          => $u->name,
                'mobile_masked' => substr($u->mobile, 0, 3) . '****' . substr($u->mobile, -3),
                'coin_balance'  => $u->coin_balance,
            ]);

        $recentMatches = Match::withCount('polls')
            ->orderByDesc('match_date')
            ->limit(5)
            ->get();

        return response()->json([
            'users'          => $userStats,
            'matches'        => $matchStats,
            'coins'          => $coinStats,
            'polls'          => $pollStats,
            'top_users'      => $topUsers,
            'recent_matches' => $recentMatches,
        ]);
    }
}
