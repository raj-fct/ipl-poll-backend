<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Match;
use App\Models\Poll;
use App\Models\User;

class DashboardWebController extends Controller
{
    public function index()
    {
        $userStats = [
            'total'    => User::where('is_admin', false)->count(),
            'active'   => User::where('is_admin', false)->where('is_active', true)->count(),
            'inactive' => User::where('is_admin', false)->where('is_active', false)->count(),
        ];

        $matchStats = [
            'total'     => Match::count(),
            'upcoming'  => Match::where('status', 'upcoming')->count(),
            'live'      => Match::where('status', 'live')->count(),
            'completed' => Match::where('status', 'completed')->count(),
        ];

        $coinStats = [
            'total_in_circulation' => User::where('is_admin', false)->sum('coin_balance'),
            'total_bonus_given'    => CoinTransaction::where('type', 'bonus')->sum('amount'),
            'total_staked'         => abs(CoinTransaction::where('type', 'bid_debit')->sum('amount')),
            'total_won_by_users'   => CoinTransaction::where('type', 'win_credit')->sum('amount'),
        ];

        $pollStats = [
            'total'   => Poll::count(),
            'pending' => Poll::where('status', 'pending')->count(),
            'won'     => Poll::where('status', 'won')->count(),
            'lost'    => Poll::where('status', 'lost')->count(),
        ];

        $topUsers = User::where('is_admin', false)
            ->orderByDesc('coin_balance')
            ->limit(10)
            ->get();

        $recentMatches = Match::withCount('polls')
            ->orderByDesc('match_date')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'userStats', 'matchStats', 'coinStats', 'pollStats', 'topUsers', 'recentMatches'
        ));
    }
}
