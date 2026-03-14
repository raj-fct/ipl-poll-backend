@extends('admin.layouts.app')

@section('title', 'Polls')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Polls</h5>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="status" class="form-select form-select-sm" style="max-width:150px">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="won" {{ request('status') === 'won' ? 'selected' : '' }}>Won</option>
                <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Lost</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
            @if(request('match_id'))
                <input type="hidden" name="match_id" value="{{ request('match_id') }}">
                <span class="badge bg-info">Match #{{ request('match_id') }}</span>
            @endif
            @if(request('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <span class="badge bg-info">User #{{ request('user_id') }}</span>
            @endif
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['status', 'match_id', 'user_id']))
                <a href="{{ route('admin.polls.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                        <th>Match</th>
                        <th>Team</th>
                        <th class="text-end">Bid</th>
                        <th>Status</th>
                        <th class="text-end">Earned</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($polls as $poll)
                    <tr>
                        <td class="text-muted">{{ $poll->id }}</td>
                        <td>
                            @if($poll->user)
                                <a href="{{ route('admin.users.show', $poll->user) }}" class="text-decoration-none">
                                    {{ $poll->user->name }}
                                </a>
                            @else
                                <span class="text-muted">Deleted</span>
                            @endif
                        </td>
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
                        <td class="text-end fw-semibold">{{ number_format($poll->bid_amount) }}</td>
                        <td><span class="badge badge-{{ $poll->status }}">{{ ucfirst($poll->status) }}</span></td>
                        <td class="text-end">{{ $poll->coins_earned > 0 ? number_format($poll->coins_earned) : '-' }}</td>
                        <td class="text-muted small">{{ $poll->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No polls found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($polls->hasPages())
    <div class="card-footer">
        {{ $polls->links() }}
    </div>
    @endif
</div>
@endsection
