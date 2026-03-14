<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'balance'      => $user->coin_balance,
            'total_won'    => $user->coinTransactions()->where('type', 'win_credit')->sum('amount'),
            'total_staked' => abs($user->coinTransactions()->where('type', 'bid_debit')->sum('amount')),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $txns = $request->user()
            ->coinTransactions()
            ->latest()
            ->paginate(30);

        return response()->json([
            'transactions' => $txns->map(fn ($t) => [
                'id'            => $t->id,
                'type'          => $t->type,
                'amount'        => $t->amount,
                'balance_after' => $t->balance_after,
                'description'   => $t->description,
                'created_at'    => $t->created_at->toIso8601String(),
            ]),
            'meta' => ['total' => $txns->total()],
        ]);
    }
}
