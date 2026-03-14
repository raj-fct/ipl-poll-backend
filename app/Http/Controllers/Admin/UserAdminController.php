<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('is_admin', false)
            ->withCount([
                'polls',
                'polls as won_polls_count'  => fn ($q) => $q->where('status', 'won'),
                'polls as lost_polls_count' => fn ($q) => $q->where('status', 'lost'),
            ])
            ->orderByDesc('coin_balance');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(30);

        return response()->json([
            'users' => $users->map(fn ($u) => $this->userResource($u)),
            'meta'  => ['total' => $users->total(), 'last_page' => $users->lastPage()],
        ]);
    }

    /**
     * Admin creates a new user with a temporary password.
     * Bonus coins are auto-awarded. must_change_password = true.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'mobile'   => 'required|string|max:15|unique:users,mobile',
            'password' => ['required', Password::min(6)],
        ]);

        $bonusCoins = (int) Setting::get('bonus_coins', 1000);

        $user = DB::transaction(function () use ($data, $bonusCoins) {
            $user = User::create([
                'name'                 => $data['name'],
                'mobile'               => $data['mobile'],
                'password'             => Hash::make($data['password']),
                'must_change_password' => true,
                'coin_balance'         => 0,
                'is_active'            => true,
            ]);

            $user->creditCoins($bonusCoins, 'bonus', "Welcome bonus: {$bonusCoins} coins");

            return $user;
        });

        return response()->json([
            'user'          => $this->userResource($user->fresh()),
            'temp_password' => $data['password'],
            'bonus_awarded' => $bonusCoins,
            'message'       => 'User created. Share the temporary password with them.',
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'sometimes|string|max:100',
            'mobile' => "sometimes|string|max:15|unique:users,mobile,{$user->id}",
        ]);

        $user->update($data);

        return response()->json(['user' => $this->userResource($user->fresh()), 'message' => 'User updated.']);
    }

    /**
     * Reset to a new temporary password. Forces re-login + password change.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'new_password' => ['required', Password::min(6)],
        ]);

        $user->update([
            'password'             => Hash::make($data['new_password']),
            'must_change_password' => true,
        ]);

        $user->tokens()->delete();

        return response()->json([
            'message'       => 'Password reset. User will be prompted to change it on next login.',
            'temp_password' => $data['new_password'],
        ]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'is_active' => $user->is_active,
            'message'   => $user->is_active ? 'User activated.' : 'User deactivated.',
        ]);
    }

    public function adjustCoins(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'type'        => 'required|in:admin_credit,admin_debit',
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($user, $data) {
            if ($data['type'] === 'admin_credit') {
                $user->creditCoins($data['amount'], 'admin_credit', $data['description']);
            } else {
                $user->debitCoins($data['amount'], 'admin_debit', $data['description']);
            }
        });

        return response()->json([
            'coin_balance' => $user->fresh()->coin_balance,
            'message'      => "Coins adjusted successfully.",
        ]);
    }

    /**
     * Award bonus coins to ALL active users at once.
     */
    public function awardBonusToAll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        $users = User::where('is_admin', false)->where('is_active', true)->get();

        DB::transaction(function () use ($users, $data) {
            foreach ($users as $user) {
                $user->creditCoins($data['amount'], 'admin_credit', $data['description']);
            }
        });

        return response()->json([
            'message'        => "Bonus of {$data['amount']} coins awarded to {$users->count()} users.",
            'users_credited' => $users->count(),
        ]);
    }

    private function userResource(User $user): array
    {
        return [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'mobile'               => $user->mobile,
            'coin_balance'         => $user->coin_balance,
            'is_active'            => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'polls_count'          => $user->polls_count ?? 0,
            'won_polls_count'      => $user->won_polls_count ?? 0,
            'lost_polls_count'     => $user->lost_polls_count ?? 0,
            'created_at'           => $user->created_at->toDateString(),
        ];
    }
}
