@extends('admin.layouts.app')

@section('title', 'User: ' . $user->name)

@section('content')
<div class="row g-3">
    {{-- User Info Card --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:80px;height:80px;font-size:2rem;color:var(--bs-primary)">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->mobile }}</p>
                @if($user->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-danger">Inactive</span>
                @endif
                @if($user->must_change_password)
                    <span class="badge bg-warning text-dark">Must Change Password</span>
                @endif
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Coin Balance</span>
                    <span class="fw-bold text-primary">{{ number_format($user->coin_balance) }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Total Polls</span>
                    <span>{{ $user->polls_count }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Won / Lost</span>
                    <span>
                        <span class="text-success">{{ $user->won_polls_count }}</span> /
                        <span class="text-danger">{{ $user->lost_polls_count }}</span>
                    </span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Win Rate</span>
                    <span>{{ $user->polls_count > 0 ? round($user->won_polls_count / $user->polls_count * 100, 1) : 0 }}%</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Joined</span>
                    <span>{{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card mb-3">
            <div class="card-header">Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i> Edit Details
                </a>
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                    @csrf
                    <button class="btn btn-outline-{{ $user->is_active ? 'danger' : 'success' }} btn-sm w-100">
                        <i class="bi bi-{{ $user->is_active ? 'x-circle' : 'check-circle' }}"></i>
                        {{ $user->is_active ? 'Deactivate' : 'Activate' }} User
                    </button>
                </form>
                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#resetPwdModal">
                    <i class="bi bi-key"></i> Reset Password
                </button>
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#adjustCoinsModal">
                    <i class="bi bi-coin"></i> Adjust Coins
                </button>
            </div>
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-8">
        {{-- Recent Polls --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-line"></i> Recent Polls</span>
                <a href="{{ route('admin.polls.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Team</th>
                            <th class="text-end">Bid</th>
                            <th>Status</th>
                            <th class="text-end">Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPolls as $poll)
                        <tr>
                            <td>
                                @if($poll->match)
                                    <a href="{{ route('admin.matches.show', $poll->match) }}" class="text-decoration-none">
                                        #{{ $poll->match->match_number }} {{ $poll->match->team_a_short }} vs {{ $poll->match->team_b_short }}
                                    </a>
                                @else
                                    <span class="text-muted">Deleted</span>
                                @endif
                            </td>
                            <td><span class="badge bg-dark">{{ $poll->selected_team }}</span></td>
                            <td class="text-end">{{ number_format($poll->bid_amount) }}</td>
                            <td><span class="badge badge-{{ $poll->status }}">{{ ucfirst($poll->status) }}</span></td>
                            <td class="text-end">{{ $poll->coins_earned > 0 ? number_format($poll->coins_earned) : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No polls yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-coin"></i> Recent Transactions</span>
                <a href="{{ route('admin.transactions.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $txn)
                        <tr>
                            <td>
                                <span class="badge bg-{{ in_array($txn->type, ['bonus', 'win_credit', 'admin_credit', 'refund']) ? 'success' : 'danger' }}">
                                    {{ str_replace('_', ' ', $txn->type) }}
                                </span>
                            </td>
                            <td class="small">{{ Str::limit($txn->description, 40) }}</td>
                            <td class="text-end fw-semibold {{ $txn->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $txn->amount >= 0 ? '+' : '' }}{{ number_format($txn->amount) }}
                            </td>
                            <td class="text-end text-muted">{{ number_format($txn->balance_after) }}</td>
                            <td class="text-muted small">{{ $txn->created_at->format('d M, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No transactions yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Reset Password Modal --}}
<div class="modal fade" id="resetPwdModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">User will be logged out and must change this password on next login.</p>
                    <div class="mb-3">
                        <label class="form-label">New Temporary Password</label>
                        <input type="text" name="new_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Adjust Coins Modal --}}
<div class="modal fade" id="adjustCoinsModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.adjust-coins', $user) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Coins</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Current balance: <strong>{{ number_format($user->coin_balance) }}</strong> coins</p>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="admin_credit">Credit (Add)</option>
                            <option value="admin_debit">Debit (Remove)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Reason for adjustment" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Adjust Coins</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
