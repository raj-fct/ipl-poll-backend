@extends('admin.layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Coin Transactions</h5>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="type" class="form-select form-select-sm" style="max-width:170px">
                <option value="">All Types</option>
                <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                <option value="bid_debit" {{ request('type') === 'bid_debit' ? 'selected' : '' }}>Bid Debit</option>
                <option value="win_credit" {{ request('type') === 'win_credit' ? 'selected' : '' }}>Win Credit</option>
                <option value="admin_credit" {{ request('type') === 'admin_credit' ? 'selected' : '' }}>Admin Credit</option>
                <option value="admin_debit" {{ request('type') === 'admin_debit' ? 'selected' : '' }}>Admin Debit</option>
                <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>Refund</option>
            </select>
            @if(request('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <span class="badge bg-info">User #{{ request('user_id') }}</span>
            @endif
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['type', 'user_id']))
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance After</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr>
                        <td class="text-muted">{{ $txn->id }}</td>
                        <td>
                            @if($txn->user)
                                <a href="{{ route('admin.users.show', $txn->user) }}" class="text-decoration-none">
                                    {{ $txn->user->name }}
                                </a>
                            @else
                                <span class="text-muted">Deleted</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeColors = [
                                    'bonus' => 'success', 'win_credit' => 'success', 'admin_credit' => 'success', 'refund' => 'info',
                                    'bid_debit' => 'danger', 'admin_debit' => 'danger',
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$txn->type] ?? 'secondary' }}">
                                {{ str_replace('_', ' ', $txn->type) }}
                            </span>
                        </td>
                        <td class="small">{{ Str::limit($txn->description, 50) }}</td>
                        <td class="text-end fw-semibold {{ $txn->amount >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}{{ number_format($txn->amount) }}
                        </td>
                        <td class="text-end text-muted">{{ number_format($txn->balance_after) }}</td>
                        <td class="text-muted small">{{ $txn->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
