@extends('admin.layouts.app')

@section('title', 'Matches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Matches</h5>
    <a href="{{ route('admin.matches.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Add Match
    </a>
</div>

{{-- Status Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.matches.index') }}"
               class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
            <a href="{{ route('admin.matches.index', ['status' => 'upcoming']) }}"
               class="btn btn-sm {{ request('status') === 'upcoming' ? 'btn-warning' : 'btn-outline-warning' }}">Upcoming</a>
            <a href="{{ route('admin.matches.index', ['status' => 'live']) }}"
               class="btn btn-sm {{ request('status') === 'live' ? 'btn-danger' : 'btn-outline-danger' }}">Live</a>
            <a href="{{ route('admin.matches.index', ['status' => 'completed']) }}"
               class="btn btn-sm {{ request('status') === 'completed' ? 'btn-success' : 'btn-outline-success' }}">Completed</a>
            <a href="{{ route('admin.matches.index', ['status' => 'cancelled']) }}"
               class="btn btn-sm {{ request('status') === 'cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Cancelled</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Match</th>
                        <th>Date & Venue</th>
                        <th class="text-center">Multiplier</th>
                        <th class="text-center">Polls</th>
                        <th class="text-center">Status</th>
                        <th>Winner</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matches as $match)
                    <tr>
                        <td class="text-muted">{{ $match->match_number }}</td>
                        <td>
                            <a href="{{ route('admin.matches.show', $match) }}" class="text-decoration-none fw-semibold">
                                {{ $match->team_a_short }} vs {{ $match->team_b_short }}
                            </a>
                            <br><small class="text-muted">{{ $match->team_a }} vs {{ $match->team_b }}</small>
                        </td>
                        <td>
                            <small>{{ $match->match_date->format('d M Y, h:i A') }}</small>
                            <br><small class="text-muted">{{ $match->venue ?? '-' }}</small>
                        </td>
                        <td class="text-center">{{ $match->win_multiplier }}x</td>
                        <td class="text-center">
                            <a href="{{ route('admin.polls.index', ['match_id' => $match->id]) }}" class="text-decoration-none">
                                {{ $match->polls_count }}
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $match->status }}">{{ ucfirst($match->status) }}</span>
                        </td>
                        <td>
                            @if($match->winning_team)
                                <span class="badge bg-dark">{{ $match->winning_team }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.matches.show', $match) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($match->status === 'upcoming')
                                    <a href="{{ route('admin.matches.edit', $match) }}" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No matches found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($matches->hasPages())
    <div class="card-footer">
        {{ $matches->links() }}
    </div>
    @endif
</div>
@endsection
