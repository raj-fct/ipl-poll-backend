<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamWebController extends Controller
{
    public function index()
    {
        $teams = Team::withCount(['matchesAsTeamA', 'matchesAsTeamB'])
            ->orderBy('short_name')
            ->get()
            ->map(function ($team) {
                $team->total_matches = $team->matches_as_team_a_count + $team->matches_as_team_b_count;
                return $team;
            });

        return view('admin.teams.index', compact('teams'));
    }

    public function create()
    {
        return view('admin.teams.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'short_name' => 'required|string|max:10|unique:teams,short_name',
            'espn_id'    => 'nullable|string|max:50',
            'logo'       => 'nullable|url|max:500',
            'color'      => 'nullable|string|max:10',
        ]);

        Team::create($data);

        return redirect()->route('admin.teams.index')->with('success', 'Team created.');
    }

    public function edit(Team $team)
    {
        return view('admin.teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'short_name' => 'required|string|max:10|unique:teams,short_name,' . $team->id,
            'espn_id'    => 'nullable|string|max:50',
            'logo'       => 'nullable|url|max:500',
            'color'      => 'nullable|string|max:10',
        ]);

        $team->update($data);

        return redirect()->route('admin.teams.index')->with('success', 'Team updated.');
    }
}
