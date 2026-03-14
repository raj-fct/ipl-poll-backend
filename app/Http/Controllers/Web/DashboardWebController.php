<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\IplMatch;
use App\Models\Poll;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardWebController extends Controller
{
    public function index(Request $request)
    {
        $seasons = Season::orderByDesc('year')->get();
        $selectedSeasonId = $request->query('season');
        $selectedSeason = $selectedSeasonId ? Season::find($selectedSeasonId) : null;

        // Match stats — filtered by season if selected
        $matchQuery = IplMatch::query();
        if ($selectedSeason) {
            $matchQuery->where('season_id', $selectedSeason->id);
        }

        $matchStats = [
            'total'     => (clone $matchQuery)->count(),
            'upcoming'  => (clone $matchQuery)->where('status', 'upcoming')->count(),
            'live'      => (clone $matchQuery)->where('status', 'live')->count(),
            'completed' => (clone $matchQuery)->where('status', 'completed')->count(),
        ];

        // Poll stats — filtered by season through match relationship
        $pollQuery = Poll::query();
        if ($selectedSeason) {
            $matchIds = IplMatch::where('season_id', $selectedSeason->id)->pluck('id');
            $pollQuery->whereIn('match_id', $matchIds);
        }

        $pollStats = [
            'total'   => (clone $pollQuery)->count(),
            'pending' => (clone $pollQuery)->where('status', 'pending')->count(),
            'won'     => (clone $pollQuery)->where('status', 'won')->count(),
            'lost'    => (clone $pollQuery)->where('status', 'lost')->count(),
        ];

        // Coin stats — filtered by season through poll/match
        $coinQuery = CoinTransaction::query();
        if ($selectedSeason) {
            $pollIds = Poll::whereIn('match_id', $matchIds)->pluck('id');
            $coinQuery->where(function ($q) use ($pollIds) {
                $q->whereIn('reference_id', $pollIds)
                  ->whereIn('type', ['bid_debit', 'win_credit', 'refund']);
            });
        }

        $coinStats = [
            'total_in_circulation' => User::where('is_admin', false)->sum('coin_balance'),
            'total_bonus_given'    => $selectedSeason
                ? (clone $coinQuery)->where('type', 'refund')->sum('amount') // not meaningful per-season
                : CoinTransaction::where('type', 'bonus')->sum('amount'),
            'total_staked'         => $selectedSeason
                ? abs((clone $coinQuery)->where('type', 'bid_debit')->sum('amount'))
                : abs(CoinTransaction::where('type', 'bid_debit')->sum('amount')),
            'total_won_by_users'   => $selectedSeason
                ? (clone $coinQuery)->where('type', 'win_credit')->sum('amount')
                : CoinTransaction::where('type', 'win_credit')->sum('amount'),
        ];

        // Always show global bonus total
        if ($selectedSeason) {
            $coinStats['total_bonus_given'] = CoinTransaction::where('type', 'bonus')->sum('amount');
        }

        $userStats = [
            'total'    => User::where('is_admin', false)->count(),
            'active'   => User::where('is_admin', false)->where('is_active', true)->count(),
            'inactive' => User::where('is_admin', false)->where('is_active', false)->count(),
        ];

        $topUsers = User::where('is_admin', false)
            ->orderByDesc('coin_balance')
            ->limit(10)
            ->get();

        $recentMatchesQuery = IplMatch::withCount('polls');
        if ($selectedSeason) {
            $recentMatchesQuery->where('season_id', $selectedSeason->id);
        }
        $recentMatches = $recentMatchesQuery->orderByDesc('match_date')->limit(5)->get();

        return view('admin.dashboard', compact(
            'userStats', 'matchStats', 'coinStats', 'pollStats',
            'topUsers', 'recentMatches', 'seasons', 'selectedSeason'
        ));
    }
}
