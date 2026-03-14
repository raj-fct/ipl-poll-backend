<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserWebController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_admin', false)
            ->withCount([
                'polls',
                'polls as won_polls_count'  => fn ($q) => $q->where('status', 'won'),
                'polls as lost_polls_count' => fn ($q) => $q->where('status', 'lost'),
            ]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $filter = $request->query('filter');
        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        }

        $sort = $request->query('sort', 'coin_balance');
        $dir  = $request->query('dir', 'desc');
        $query->orderBy($sort, $dir);

        $users = $query->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'filter'));
    }

    public function create()
    {
        $bonusCoins = (int) Setting::get('bonus_coins', 1000);
        return view('admin.users.create', compact('bonusCoins'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'mobile'   => 'required|string|max:15|unique:users,mobile',
            'password' => ['required', Password::min(6)],
        ]);

        $bonusCoins = (int) Setting::get('bonus_coins', 1000);

        DB::transaction(function () use ($data, $bonusCoins) {
            $user = User::create([
                'name'                 => $data['name'],
                'mobile'               => $data['mobile'],
                'password'             => Hash::make($data['password']),
                'must_change_password' => true,
                'coin_balance'         => 0,
                'is_active'            => true,
            ]);
            $user->creditCoins($bonusCoins, 'bonus', "Welcome bonus: {$bonusCoins} coins");
        });

        return redirect()->route('admin.users.index')->with('success', "User created with {$bonusCoins} bonus coins.");
    }

    public function show(User $user)
    {
        $user->loadCount([
            'polls',
            'polls as won_polls_count'  => fn ($q) => $q->where('status', 'won'),
            'polls as lost_polls_count' => fn ($q) => $q->where('status', 'lost'),
        ]);

        $recentPolls = $user->polls()->with('match')->latest()->limit(10)->get();
        $recentTransactions = $user->coinTransactions()->latest()->limit(15)->get();

        return view('admin.users.show', compact('user', 'recentPolls', 'recentTransactions'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'mobile' => "required|string|max:15|unique:users,mobile,{$user->id}",
        ]);

        $user->update($data);

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$status}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'new_password' => ['required', Password::min(6)],
        ]);

        $user->update([
            'password'             => Hash::make($data['new_password']),
            'must_change_password' => true,
        ]);
        $user->tokens()->delete();

        return back()->with('success', 'Password reset. User must change on next login.');
    }

    public function adjustCoins(Request $request, User $user)
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

        return back()->with('success', 'Coins adjusted.');
    }

    public function awardBonusToAll(Request $request)
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

        return back()->with('success', "Bonus of {$data['amount']} coins awarded to {$users->count()} users.");
    }
}
