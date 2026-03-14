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
                        {{-- Teams --}}
                        <div class="col-md-6">
                            <label class="form-label">Team A</label>
                            <select name="team_a_id" class="form-select @error('team_a_id') is-invalid @enderror" required>
                                <option value="">Select Team A</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ old('team_a_id', $match->team_a_id) == $team->id ? 'selected' : '' }}>
                                        {{ $team->short_name }} - {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('team_a_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Team B</label>
                            <select name="team_b_id" class="form-select @error('team_b_id') is-invalid @enderror" required>
                                <option value="">Select Team B</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ old('team_b_id', $match->team_b_id) == $team->id ? 'selected' : '' }}>
                                        {{ $team->short_name }} - {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('team_b_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr>

                        <div class="col-md-4">
                            <label class="form-label">Season</label>
                            <select name="season_id" class="form-select @error('season_id') is-invalid @enderror" required>
                                @foreach($seasons as $season)
                                    <option value="{{ $season->id }}" {{ old('season_id', $match->season_id) == $season->id ? 'selected' : '' }}>
                                        {{ $season->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('season_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Match Date & Time</label>
                            <input type="datetime-local" name="match_date" class="form-control"
                                   value="{{ old('match_date', $match->match_date->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Win Multiplier</label>
                            <input type="number" name="win_multiplier" class="form-control"
                                   value="{{ old('win_multiplier', $match->win_multiplier) }}" step="0.01" min="1.0" max="10.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue', $match->venue) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ESPN ID</label>
                            <input type="text" class="form-control" value="{{ $match->espn_id }}" disabled>
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
