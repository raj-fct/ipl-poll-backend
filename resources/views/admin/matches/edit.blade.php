@extends('admin.layouts.app')

@section('title', 'Edit Match')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit Match #{{ $match->match_number }}: {{ $match->team_a_short }} vs {{ $match->team_b_short }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.matches.update', $match) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Team A</h6>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="team_a" class="form-control" value="{{ old('team_a', $match->team_a) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Code</label>
                                <input type="text" name="team_a_short" class="form-control" value="{{ old('team_a_short', $match->team_a_short) }}" maxlength="5" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo URL</label>
                                <input type="url" name="team_a_logo" class="form-control" value="{{ old('team_a_logo', $match->team_a_logo) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Team B</h6>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="team_b" class="form-control" value="{{ old('team_b', $match->team_b) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Code</label>
                                <input type="text" name="team_b_short" class="form-control" value="{{ old('team_b_short', $match->team_b_short) }}" maxlength="5" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo URL</label>
                                <input type="url" name="team_b_logo" class="form-control" value="{{ old('team_b_logo', $match->team_b_logo) }}">
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-4">
                            <label class="form-label">Match Date & Time</label>
                            <input type="datetime-local" name="match_date" class="form-control"
                                   value="{{ old('match_date', $match->match_date->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue', $match->venue) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Win Multiplier</label>
                            <input type="number" name="win_multiplier" class="form-control"
                                   value="{{ old('win_multiplier', $match->win_multiplier) }}" step="0.01" min="1.0" max="10.0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $match->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="{{ route('admin.matches.show', $match) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
