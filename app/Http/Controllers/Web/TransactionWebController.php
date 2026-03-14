<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use Illuminate\Http\Request;

class TransactionWebController extends Controller
{
    public function index(Request $request)
    {
        $query = CoinTransaction::with('user');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $transactions = $query->latest()->paginate(30)->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }
}
