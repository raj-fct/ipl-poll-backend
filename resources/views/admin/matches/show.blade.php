@extends('admin.layouts.app')

@section('title', "Match #{$match->match_number}")

@section('content')
<div class="row g-3">
    {{-- Match Info --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <span class="badge badge-{{ $match->status }} mb-2" style="font-size:0.85rem">{{ ucfirst($match->status) }}</span>
                <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                    @if($match->team_a_logo)
                        <img src="{{ $match->team_a_logo }}" alt="{{ $match->team_a_short }}" style="width:40px;height:40px">
                    @endif
                    <h4 class="mb-0">{{ $match->team_a_short }} vs {{ $match->team_b_short }}</h4>
                    @if($match->team_b_logo)
                        <img src="{{ $match->team_b_logo }}" alt="{{ $match->team_b_short }}" style="width:40px;height:40px">
                    @endif
                </div>
                <p class="text-muted mb-1">{{ $match->team_a }} vs {{ $match->team_b }}</p>
                <small class="text-muted">
                    <i class="bi bi-calendar"></i> {{ $match->match_date->format('d M Y, h:i A') }}
                </small>
                @if($match->venue)
                    <br><small class="text-muted"><i class="bi bi-geo-alt"></i> {{ $match->venue }}</small>
                @endif
                @if($match->score_a || $match->score_b)
                    <div class="mt-3">
                        <div class="d-flex justify-content-center align-items-center gap-4">
                            <div class="text-center">
                                <div class="fw-bold">{{ $match->team_a_short }}</div>
                                <div class="fs-5 fw-semibold">{{ $match->score_a ?? '-' }}</div>
                            </div>
                            <div class="text-muted small">vs</div>
                            <div class="text-center">
                                <div class="fw-bold">{{ $match->team_b_short }}</div>
                                <div class="fs-5 fw-semibold">{{ $match->score_b ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
                @if($match->winning_team)
                    <div class="mt-2">
                        <span class="badge bg-success" style="font-size:1rem">
                            <i class="bi bi-trophy"></i> Winner: {{ $match->winning_team }}
                        </span>
                    </div>
                @endif
                @if($match->toss_winner)
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-coin"></i> Toss: <strong>{{ $match->toss_winner }}</strong> elected to <strong>{{ $match->toss_decision }}</strong>
                        </small>
                    </div>
                @endif
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Match Number</span>
                    <span>#{{ $match->match_number }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Season</span>
                    <span>{{ $match->season }}{{ $match->seasonRecord ? '' : '' }}</span>
                </div>
                @if($match->espn_id)
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">ESPN ID</span>
                    <span class="font-monospace small">{{ $match->espn_id }}</span>
                </div>
                @endif
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Win Multiplier</span>
                    <span class="fw-semibold">{{ $match->win_multiplier }}x</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Total Polls</span>
                    <span>{{ $match->polls_count }}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Total Staked</span>
                    <span class="fw-semibold">{{ number_format($totalBid) }}</span>
                </div>
            </div>
        </div>

        {{-- Poll Distribution --}}
        @if($match->polls_count > 0)
        <div class="card mb-3">
            <div class="card-header">Poll Distribution</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold">{{ $match->team_a_short }}</span>
                    <span>{{ $teamACount }} ({{ $match->polls_count > 0 ? round($teamACount / $match->polls_count * 100) : 0 }}%)</span>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-primary" style="width: {{ $match->polls_count > 0 ? ($teamACount / $match->polls_count * 100) : 0 }}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold">{{ $match->team_b_short }}</span>
                    <span>{{ $teamBCount }} ({{ $match->polls_count > 0 ? round($teamBCount / $match->polls_count * 100) : 0 }}%)</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-danger" style="width: {{ $match->polls_count > 0 ? ($teamBCount / $match->polls_count * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="card">
            <div class="card-header">Actions</div>
            <div class="card-body d-grid gap-2">
                @if($match->status === 'upcoming')
                    <a href="{{ route('admin.matches.edit', $match) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil"></i> Edit Match
                    </a>
                    <form method="POST" action="{{ route('admin.matches.update-status', $match) }}">
                        @csrf
                        <input type="hidden" name="status" value="live">
                        <button class="btn btn-danger btn-sm w-100">
                            <i class="bi bi-broadcast"></i> Mark as LIVE
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.matches.cancel', $match) }}"
                          onsubmit="return confirm('Cancel this match and refund all bids?')">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle"></i> Cancel Match
                        </button>
                    </form>
                @endif

                @if($match->status === 'live')
                    <form method="POST" action="{{ route('admin.matches.update-status', $match) }}">
                        @csrf
                        <input type="hidden" name="status" value="upcoming">
                        <button class="btn btn-outline-warning btn-sm w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Revert to Upcoming
                        </button>
                    </form>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#setResultModal">
                        <i class="bi bi-trophy"></i> Set Result
                    </button>
                    <form method="POST" action="{{ route('admin.matches.cancel', $match) }}"
                          onsubmit="return confirm('Cancel this match and refund all bids?')">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle"></i> Cancel Match
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Polls Table --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-line"></i> All Polls ({{ $polls->count() }})</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Selected Team</th>
                                <th class="text-end">Bid</th>
                                <th>Status</th>
                                <th class="text-end">Earned</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($polls as $poll)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.users.show', $poll->user) }}" class="text-decoration-none">
                                        {{ $poll->user->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $poll->user->mobile }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $poll->selected_team === $match->team_a_short ? 'primary' : 'danger' }}">
                                        {{ $poll->selected_team }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($poll->bid_amount) }}</td>
                                <td><span class="badge badge-{{ $poll->status }}">{{ ucfirst($poll->status) }}</span></td>
                                <td class="text-end">{{ $poll->coins_earned > 0 ? number_format($poll->coins_earned) : '-' }}</td>
                                <td class="text-muted small">{{ $poll->created_at->format('d M, H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No polls for this match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Set Result Modal --}}
@if(in_array($match->status, ['live', 'upcoming']))
<div class="modal fade" id="setResultModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.matches.set-result', $match) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Declare Match Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Select the winning team for <strong>Match #{{ $match->match_number }}</strong>:</p>
                    <div class="d-grid gap-2">
                        <label class="btn btn-outline-primary text-start">
                            <input type="radio" name="winning_team" value="{{ $match->team_a_short }}" class="me-2" required>
                            {{ $match->team_a }} ({{ $match->team_a_short }})
                        </label>
                        <label class="btn btn-outline-danger text-start">
                            <input type="radio" name="winning_team" value="{{ $match->team_b_short }}" class="me-2">
                            {{ $match->team_b }} ({{ $match->team_b_short }})
                        </label>
                    </div>
                    <div class="alert alert-warning mt-3 small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> This will settle all polls and distribute coins. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Declare Result</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@if($match->notes)
<div class="card mt-3">
    <div class="card-header">Notes</div>
    <div class="card-body">{{ $match->notes }}</div>
</div>
@endif
@endsection
