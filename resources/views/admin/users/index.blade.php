@extends('admin.layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Users</h5>
    </div>
    <div class="d-flex gap-2">
        {{-- Award Bonus Modal Trigger --}}
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#awardBonusModal">
            <i class="bi bi-gift"></i> Award Bonus to All
        </button>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Add User
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:250px"
                   placeholder="Search name or mobile..." value="{{ $search ?? '' }}">
            <select name="filter" class="form-select form-select-sm" style="max-width:150px">
                <option value="">All Users</option>
                <option value="active" {{ ($filter ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filter ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            @if($search || $filter)
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                        <th>Name</th>
                        <th>Mobile</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Polls (W/L)</th>
                        <th class="text-center">Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="text-muted">{{ $user->id }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="fw-semibold text-decoration-none">
                                {{ $user->name }}
                            </a>
                            @if($user->must_change_password)
                                <span class="badge bg-warning text-dark" title="Must change password"><i class="bi bi-exclamation-triangle"></i></span>
                            @endif
                        </td>
                        <td>{{ $user->mobile }}</td>
                        <td class="text-end fw-semibold">{{ number_format($user->coin_balance) }}</td>
                        <td class="text-center">
                            {{ $user->polls_count }}
                            <small class="text-muted">({{ $user->won_polls_count }}/{{ $user->lost_polls_count }})</small>
                        </td>
                        <td class="text-center">
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-{{ $user->is_active ? 'danger' : 'success' }}" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $user->is_active ? 'x-circle' : 'check-circle' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- Award Bonus Modal --}}
<div class="modal fade" id="awardBonusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.award-bonus') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Award Bonus to All Active Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Weekend bonus" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Award Bonus</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
