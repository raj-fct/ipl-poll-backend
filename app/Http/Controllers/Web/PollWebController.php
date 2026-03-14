<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;

class PollWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Poll::with(['user', 'match']);

        if ($matchId = $request->query('match_id')) {
            $query->where('match_id', $matchId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $polls = $query->latest()->paginate(30)->withQueryString();

        return view('admin.polls.index', compact('polls'));
    }
}
