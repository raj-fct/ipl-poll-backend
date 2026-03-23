@extends('admin.layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Push Notifications</h4>
    <a href="{{ route('admin.notifications.create') }}" class="btn btn-sm btn-danger">
        <i class="bi bi-send"></i> Send Custom Notification
    </a>
</div>

{{-- Upcoming Match Notification Schedule --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check"></i> Upcoming Notification Schedule
    </div>
    <div class="card-body p-0">
        @if($upcomingMatches->isEmpty())
            <div class="text-center text-muted py-4">No upcoming matches</div>
        @else
            @foreach($upcomingMatches as $match)
                <div class="border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>Match #{{ $match->match_number }}</strong>
                            <span class="text-muted mx-1">|</span>
                            <span class="fw-semibold">{{ $match->team_a_short }} vs {{ $match->team_b_short }}</span>
                            <span class="text-muted mx-1">|</span>
                            <span class="text-muted small">{{ $match->match_date->format('d M Y, g:i A') }}</span>
                        </div>
                        <span class="text-muted small">
                            <i class="bi bi-clock"></i> Poll closes: {{ $match->match_date->copy()->subMinutes(30)->format('g:i A') }}
                        </span>
                    </div>
                    <div class="row g-2">
                        @foreach($match->notification_schedule as $notif)
                            <div class="col-md-3 col-sm-6">
                                <div class="rounded border px-3 py-2 d-flex align-items-center gap-2 {{ $notif['sent'] ? 'bg-success bg-opacity-10 border-success' : '' }}" style="font-size: 0.85rem;">
                                    @if($notif['sent'])
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @elseif($notif['scheduled']->isPast())
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @else
                                        <i class="bi bi-clock text-warning"></i>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $notif['label'] }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ $notif['scheduled']->format('d M, g:i A') }}
                                            @if($notif['sent'])
                                                <span class="badge bg-success ms-1">Sent</span>
                                            @elseif($notif['scheduled']->isPast())
                                                <span class="badge bg-danger ms-1">Missed</span>
                                            @else
                                                <span class="badge bg-warning text-dark ms-1">Pending</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Custom Scheduled Notifications --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-clock-history"></i> Custom Scheduled Notifications
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Match</th>
                        <th>Scheduled For</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Created By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scheduledNotifications as $sn)
                        <tr>
                            <td>
                                <div class="fw-semibold" style="font-size:0.85rem;">{{ Str::limit($sn->title, 40) }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ Str::limit($sn->body, 60) }}</div>
                            </td>
                            <td>
                                @if($sn->match)
                                    <span class="small">#{{ $sn->match->match_number }} {{ $sn->match->team_a_short }} vs {{ $sn->match->team_b_short }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $sn->scheduled_at->format('d M Y, g:i A') }}</td>
                            <td>
                                @if($sn->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($sn->status === 'sent')
                                    <span class="badge bg-success">Sent</span>
                                @else
                                    <span class="badge bg-danger">Failed</span>
                                @endif
                            </td>
                            <td>
                                @if($sn->status === 'sent')
                                    <span class="text-success small">{{ $sn->success_count }}</span>
                                    <span class="text-muted small">/</span>
                                    <span class="text-danger small">{{ $sn->failure_count }}</span>
                                @elseif($sn->status === 'pending')
                                    <span class="text-muted small">—</span>
                                @else
                                    <span class="text-danger small">Failed</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $sn->creator->name ?? '-' }}</td>
                            <td>
                                @if($sn->status === 'pending')
                                    <form action="{{ route('admin.notifications.cancel', $sn) }}" method="POST"
                                          onsubmit="return confirm('Cancel this scheduled notification?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem;">
                                            <i class="bi bi-x-lg"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No custom notifications yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($scheduledNotifications->hasPages())
            <div class="p-3">
                {{ $scheduledNotifications->appends(['sent_page' => request('sent_page')])->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Sent Notifications Log --}}
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bell-fill"></i> Sent Notifications Log
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Type</th>
                        <th>Sent At</th>
                        <th>Success</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sentNotifications as $notif)
                        <tr>
                            <td>
                                @if($notif->match)
                                    <strong>#{{ $notif->match->match_number }}</strong>
                                    {{ $notif->match->team_a_short }} vs {{ $notif->match->team_b_short }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'match_day' => ['Match Day', 'primary'],
                                        '2hr_before' => ['2hr Before', 'info'],
                                        '1hr_before' => ['1hr Before', 'warning'],
                                        '30min_before' => ['30min Before', 'danger'],
                                    ];
                                    $label = $typeLabels[$notif->type] ?? [$notif->type, 'secondary'];
                                @endphp
                                <span class="badge bg-{{ $label[1] }}">{{ $label[0] }}</span>
                            </td>
                            <td>{{ $notif->sent_at->format('d M Y, g:i A') }}</td>
                            <td><span class="text-success fw-semibold">{{ $notif->success_count }}</span></td>
                            <td><span class="text-danger fw-semibold">{{ $notif->failure_count }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No notifications sent yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sentNotifications->hasPages())
            <div class="p-3">
                {{ $sentNotifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
