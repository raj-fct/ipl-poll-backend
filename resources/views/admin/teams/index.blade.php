@extends('admin.layouts.app')

@section('title', 'Teams')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Teams ({{ $teams->count() }})</h5>
    <a href="{{ route('admin.teams.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Add Team
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Short Name</th>
                        <th>Full Name</th>
                        <th>ESPN ID</th>
                        <th>Color</th>
                        <th class="text-center">Matches</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                    <tr>
                        <td class="text-muted">{{ $team->id }}</td>
                        <td>
                            @if($team->logo)
                                <img src="{{ $team->logo }}" alt="{{ $team->short_name }}" style="width:32px;height:32px;object-fit:contain">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $team->short_name }}</span>
                        </td>
                        <td>{{ $team->name }}</td>
                        <td><code class="small">{{ $team->espn_id ?? '-' }}</code></td>
                        <td>
                            @if($team->color)
                                <span class="d-inline-block rounded-circle me-1" style="width:14px;height:14px;background:#{{ $team->color }}"></span>
                                <small class="text-muted">#{{ $team->color }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $team->total_matches }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No teams found. Run <code>php artisan ipl:fetch-matches --season=2025 --with-results</code> to import teams.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($teams->isNotEmpty())
<div class="card mt-3">
    <div class="card-header">Team Logos</div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            @foreach($teams as $team)
                <div class="text-center" style="width:80px">
                    @if($team->logo)
                        <img src="{{ $team->logo }}" alt="{{ $team->short_name }}" style="width:48px;height:48px;object-fit:contain" class="mb-1">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-1" style="width:48px;height:48px;margin:0 auto">
                            <i class="bi bi-shield text-muted"></i>
                        </div>
                    @endif
                    <small class="fw-semibold d-block">{{ $team->short_name }}</small>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
