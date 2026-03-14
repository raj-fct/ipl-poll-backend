@extends('admin.layouts.app')

@section('title', 'Create Match')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-plus"></i> Create New Match
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.matches.store') }}">
                    @csrf
                    <div class="row g-3">
                        {{-- Team A --}}
                        <div class="col-md-6">
                            <h6 class="text-muted">Team A</h6>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="team_a" class="form-control @error('team_a') is-invalid @enderror"
                                       value="{{ old('team_a') }}" placeholder="e.g., Mumbai Indians" required>
                                @error('team_a')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Code</label>
                                <input type="text" name="team_a_short" class="form-control @error('team_a_short') is-invalid @enderror"
                                       value="{{ old('team_a_short') }}" placeholder="MI" maxlength="5" required>
                                @error('team_a_short')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo URL <small class="text-muted">(optional)</small></label>
                                <input type="url" name="team_a_logo" class="form-control" value="{{ old('team_a_logo') }}">
                            </div>
                        </div>

                        {{-- Team B --}}
                        <div class="col-md-6">
                            <h6 class="text-muted">Team B</h6>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="team_b" class="form-control @error('team_b') is-invalid @enderror"
                                       value="{{ old('team_b') }}" placeholder="e.g., Chennai Super Kings" required>
                                @error('team_b')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Code</label>
                                <input type="text" name="team_b_short" class="form-control @error('team_b_short') is-invalid @enderror"
                                       value="{{ old('team_b_short') }}" placeholder="CSK" maxlength="5" required>
                                @error('team_b_short')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo URL <small class="text-muted">(optional)</small></label>
                                <input type="url" name="team_b_logo" class="form-control" value="{{ old('team_b_logo') }}">
                            </div>
                        </div>

                        <hr>

                        {{-- Match Details --}}
                        <div class="col-md-4">
                            <label class="form-label">Match Number</label>
                            <input type="number" name="match_number" class="form-control @error('match_number') is-invalid @enderror"
                                   value="{{ old('match_number') }}" min="1" required>
                            @error('match_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Match Date & Time</label>
                            <input type="datetime-local" name="match_date" class="form-control @error('match_date') is-invalid @enderror"
                                   value="{{ old('match_date') }}" required>
                            @error('match_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Season</label>
                            <input type="text" name="season" class="form-control" value="{{ old('season', 'IPL 2025') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue') }}" placeholder="Stadium name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Win Multiplier</label>
                            <input type="number" name="win_multiplier" class="form-control" value="{{ old('win_multiplier', '1.90') }}"
                                   step="0.01" min="1.0" max="10.0">
                            <div class="form-text">Default: 1.90x</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes <small class="text-muted">(optional)</small></label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create Match
                        </button>
                        <a href="{{ route('admin.matches.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
