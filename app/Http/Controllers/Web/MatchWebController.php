<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMatchResult;
use App\Models\IplMatch;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchWebController extends Controller
{
    public function index(Request $request)
    {
        $seasons = Season::orderByDesc('year')->get();
        $selectedSeasonId = $request->query('season');

        $query = IplMatch::with(['teamA', 'teamB', 'seasonRecord'])->withCount('polls');

        if ($selectedSeasonId) {
            $query->where('season_id', $selectedSeasonId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $matches = $query->orderByDesc('match_date')->paginate(20)->withQueryString();

        return view('admin.matches.index', compact('matches', 'seasons', 'selectedSeasonId'));
    }

    public function create()
    {
        $teams = Team::orderBy('name')->get();
        $seasons = Season::orderByDesc('year')->get();

        return view('admin.matches.create', compact('teams', 'seasons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_a_id'      => 'required|exists:teams,id',
            'team_b_id'      => 'required|exists:teams,id|different:team_a_id',
            'season_id'      => 'required|exists:seasons,id',
            'match_date'     => 'required|date',
            'venue'          => 'nullable|string|max:200',
            'match_number'   => 'required|integer|min:1',
            'win_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'notes'          => 'nullable|string',
        ]);

        $teamA = Team::find($data['team_a_id']);
        $teamB = Team::find($data['team_b_id']);
        $season = Season::find($data['season_id']);

        $data['team_a'] = $teamA->name;
        $data['team_b'] = $teamB->name;
        $data['team_a_short'] = $teamA->short_name;
        $data['team_b_short'] = $teamB->short_name;
        $data['team_a_logo'] = $teamA->logo;
        $data['team_b_logo'] = $teamB->logo;
        $data['season'] = $season->name;

        IplMatch::create($data);

        return redirect()->route('admin.matches.index')->with('success', 'Match created.');
    }

    public function show(IplMatch $match)
    {
        $match->loadCount('polls');
        $polls = $match->polls()->with('user')->latest()->get();

        $teamACount = $polls->where('selected_team', $match->team_a_short)->count();
        $teamBCount = $polls->where('selected_team', $match->team_b_short)->count();
        $totalBid   = $polls->sum('bid_amount');

        return view('admin.matches.show', compact('match', 'polls', 'teamACount', 'teamBCount', 'totalBid'));
    }

    public function edit(IplMatch $match)
    {
        $teams = Team::orderBy('name')->get();
        $seasons = Season::orderByDesc('year')->get();

        return view('admin.matches.edit', compact('match', 'teams', 'seasons'));
    }

    public function update(Request $request, IplMatch $match)
    {
        $data = $request->validate([
            'team_a_id'      => 'required|exists:teams,id',
            'team_b_id'      => 'required|exists:teams,id|different:team_a_id',
            'season_id'      => 'required|exists:seasons,id',
            'match_date'     => 'required|date',
            'venue'          => 'nullable|string|max:200',
            'win_multiplier' => 'nullable|numeric|min:1.0|max:10.0',
            'notes'          => 'nullable|string',
        ]);

        $teamA = Team::find($data['team_a_id']);
        $teamB = Team::find($data['team_b_id']);
        $season = Season::find($data['season_id']);

        $data['team_a'] = $teamA->name;
        $data['team_b'] = $teamB->name;
        $data['team_a_short'] = $teamA->short_name;
        $data['team_b_short'] = $teamB->short_name;
        $data['team_a_logo'] = $teamA->logo;
        $data['team_b_logo'] = $teamB->logo;
        $data['season'] = $season->name;

        $match->update($data);

        return redirect()->route('admin.matches.show', $match)->with('success', 'Match updated.');
    }

    public function updateStatus(Request $request, IplMatch $match)
    {
        $data = $request->validate([
            'status' => 'required|in:upcoming,live',
        ]);

        if ($match->status === 'completed' || $match->status === 'cancelled') {
            return back()->with('error', 'Cannot change status of a completed/cancelled match.');
        }

        $match->update(['status' => $data['status']]);

        return back()->with('success', 'Match status updated to ' . $data['status'] . '.');
    }

    public function setResult(Request $request, IplMatch $match)
    {
        $teams = $match->getTeams();

        $data = $request->validate([
            'winning_team' => ['required', 'string', function ($attribute, $value, $fail) use ($teams) {
                if (!in_array($value, $teams)) {
                    $fail("winning_team must be one of: " . implode(', ', $teams));
                }
            }],
        ]);

        if ($match->status === 'completed') {
            return back()->with('error', 'Result already declared.');
        }

        ProcessMatchResult::dispatch($match->id, $data['winning_team']);

        return back()->with('success', 'Result submitted. Settlement processing in background.');
    }

    public function cancel(IplMatch $match)
    {
        if ($match->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed match.');
        }

        DB::transaction(function () use ($match) {
            $polls = $match->polls()->with('user')->where('status', 'pending')->get();

            foreach ($polls as $poll) {
                $poll->user->creditCoins(
                    $poll->bid_amount,
                    'refund',
                    "Refund for cancelled Match #{$match->match_number}",
                    $poll
                );
                $poll->update(['status' => 'refunded']);
            }

            $match->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Match cancelled and all pending bids refunded.');
    }
}
