@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
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
                <span><i class="bi bi-calendar-event"></i> Recent Matches</span>
                <a href="{{ route('admin.matches.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Match</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Polls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMatches as $match)
                        <tr>
                            <td>{{ $match->match_number }}</td>
                            <td>
                                <a href="{{ route('admin.matches.show', $match) }}" class="text-decoration-none">
                                    {{ $match->team_a_short }} vs {{ $match->team_b_short }}
                                </a>
                            </td>
                            <td class="text-muted small">{{ $match->match_date->format('d M, H:i') }}</td>
                            <td><span class="badge badge-{{ $match->status }}">{{ ucfirst($match->status) }}</span></td>
                            <td class="text-end">{{ $match->polls_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No matches yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
