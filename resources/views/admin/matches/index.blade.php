@extends('admin.layouts.app')

@section('title', 'Matches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Matches</h5>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex align-items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <select name="season" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">All Seasons</option>
                @foreach($seasons as $season)
                    <option value="{{ $season->id }}" {{ $selectedSeasonId == $season->id ? 'selected' : '' }}>
                        {{ $season->name }}
                    </option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('admin.matches.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Add Match
        </a>
    </div>
</div>

{{-- Status Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.matches.index', array_filter(['season' => $selectedSeasonId])) }}"
               class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
            <a href="{{ route('admin.matches.index', array_filter(['status' => 'upcoming', 'season' => $selectedSeasonId])) }}"
               class="btn btn-sm {{ request('status') === 'upcoming' ? 'btn-warning' : 'btn-outline-warning' }}">Upcoming</a>
            <a href="{{ route('admin.matches.index', array_filter(['status' => 'live', 'season' => $selectedSeasonId])) }}"
               class="btn btn-sm {{ request('status') === 'live' ? 'btn-danger' : 'btn-outline-danger' }}">Live</a>
            <a href="{{ route('admin.matches.index', array_filter(['status' => 'completed', 'season' => $selectedSeasonId])) }}"
               class="btn btn-sm {{ request('status') === 'completed' ? 'btn-success' : 'btn-outline-success' }}">Completed</a>
            <a href="{{ route('admin.matches.index', array_filter(['status' => 'cancelled', 'season' => $selectedSeasonId])) }}"
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
                        <th>Team A</th>
                        <th></th>
                        <th>Team B</th>
                        <th>Season</th>
                        <th>Date & Venue</th>
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
                            <div class="d-flex align-items-center gap-2">
                                @if($match->teamA && $match->teamA->logo)
                                    <img src="{{ $match->teamA->logo }}" alt="{{ $match->team_a_short }}" style="width:24px;height:24px;object-fit:contain">
                                @elseif($match->team_a_logo)
                                    <img src="{{ $match->team_a_logo }}" alt="{{ $match->team_a_short }}" style="width:24px;height:24px;object-fit:contain">
                                @endif
                                <div>
                                    <span class="fw-semibold">{{ $match->team_a_short }}</span>
                                    <br><small class="text-muted">{{ $match->team_a }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center text-muted fw-bold">vs</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($match->teamB && $match->teamB->logo)
                                    <img src="{{ $match->teamB->logo }}" alt="{{ $match->team_b_short }}" style="width:24px;height:24px;object-fit:contain">
                                @elseif($match->team_b_logo)
                                    <img src="{{ $match->team_b_logo }}" alt="{{ $match->team_b_short }}" style="width:24px;height:24px;object-fit:contain">
                                @endif
                                <div>
                                    <span class="fw-semibold">{{ $match->team_b_short }}</span>
                                    <br><small class="text-muted">{{ $match->team_b }}</small>
                                </div>
                            </div>
                        </td>
                        <td><small class="text-muted">{{ $match->seasonRecord->name ?? $match->season }}</small></td>
                        <td>
                            <small>{{ $match->match_date->format('d M Y, h:i A') }}</small>
                            <br><small class="text-muted">{{ $match->venue ?? '-' }}</small>
                        </td>
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
                        <td colspan="10" class="text-center text-muted py-4">No matches found.</td>
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
