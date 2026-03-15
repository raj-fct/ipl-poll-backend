@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Season Filter --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Dashboard</h5>
    <form method="GET" class="d-flex align-items-center gap-2">
        <select name="season" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
            <option value="">All Seasons</option>
            @foreach($seasons as $season)
                <option value="{{ $season->id }}" {{ $selectedSeason && $selectedSeason->id == $season->id ? 'selected' : '' }}>
                    {{ $season->name }}
                </option>
            @endforeach
        </select>
    </form>
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value">{{ number_format($userStats['total']) }}</div>
                    <small class="text-success">{{ $userStats['active'] }} active</small>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Matches</div>
                    <div class="stat-value">{{ number_format($matchStats['total']) }}</div>
                    <small class="text-warning">{{ $matchStats['upcoming'] }} upcoming</small>
                    @if($matchStats['live'] > 0)
                        <small class="text-danger ms-1">{{ $matchStats['live'] }} live</small>
                    @endif
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Polls</div>
                    <div class="stat-value">{{ number_format($pollStats['total']) }}</div>
                    <small class="text-info">{{ $pollStats['pending'] }} pending</small>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Coins in Circulation</div>
                    <div class="stat-value">{{ number_format($coinStats['total_in_circulation']) }}</div>
                    <small class="text-muted">{{ number_format($coinStats['total_won_by_users']) }} won</small>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-coin"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Coin Economy Row --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Total Bonus Given</div>
            <div class="stat-value text-primary">{{ number_format($coinStats['total_bonus_given']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Total Staked</div>
            <div class="stat-value text-warning">{{ number_format($coinStats['total_staked']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Total Won by Users</div>
            <div class="stat-value text-success">{{ number_format($coinStats['total_won_by_users']) }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Top Users --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-trophy"></i> Top Users</span>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topUsers as $i => $user)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none">
                                    {{ $user->name }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $user->mobile }}</td>
                            <td class="text-end fw-semibold">{{ number_format($user->coin_balance) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No users yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Matches --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event"></i> Recent Completed / Live</span>
                <a href="{{ route('admin.matches.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Match</th>
                            <th>Score</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMatches as $match)
                        <tr>
                            <td>{{ $match->match_number }}</td>
                            <td>
                                <a href="{{ route('admin.matches.show', $match) }}" class="text-decoration-none d-flex align-items-center gap-1">
                                    @if($match->teamA && $match->teamA->logo)
                                        <img src="{{ $match->teamA->logo }}" alt="" style="width:18px;height:18px;object-fit:contain">
                                    @endif
                                    <span class="{{ $match->winning_team === $match->team_a_short ? 'fw-bold' : '' }}">{{ $match->team_a_short }}</span>
                                    <span class="text-muted">vs</span>
                                    <span class="{{ $match->winning_team === $match->team_b_short ? 'fw-bold' : '' }}">{{ $match->team_b_short }}</span>
                                    @if($match->teamB && $match->teamB->logo)
                                        <img src="{{ $match->teamB->logo }}" alt="" style="width:18px;height:18px;object-fit:contain">
                                    @endif
                                </a>
                            </td>
                            <td class="small">
                                @if($match->score_a || $match->score_b)
                                    {{ $match->score_a ?? '-' }} vs {{ $match->score_b ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($match->winning_team)
                                    <span class="badge bg-dark">{{ $match->winning_team }}</span>
                                @elseif($match->status === 'live')
                                    <span class="badge badge-live">LIVE</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No matches yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
