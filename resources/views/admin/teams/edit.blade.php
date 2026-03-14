@extends('admin.layouts.app')

@section('title', 'Edit Team')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit Team: {{ $team->short_name }} - {{ $team->name }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.teams.update', $team) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $team->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Name / Code</label>
                        <input type="text" name="short_name" class="form-control @error('short_name') is-invalid @enderror"
                               value="{{ old('short_name', $team->short_name) }}" maxlength="10" required>
                        @error('short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ESPN ID</label>
                        <input type="text" name="espn_id" class="form-control @error('espn_id') is-invalid @enderror"
                               value="{{ old('espn_id', $team->espn_id) }}">
                        @error('espn_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="url" name="logo" class="form-control @error('logo') is-invalid @enderror"
                               value="{{ old('logo', $team->logo) }}">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($team->logo)
                            <div class="mt-2">
                                <img src="{{ $team->logo }}" alt="{{ $team->short_name }}" style="width:48px;height:48px;object-fit:contain">
                                <small class="text-muted ms-2">Current logo</small>
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color Hex <small class="text-muted">(without #)</small></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="color" class="form-control @error('color') is-invalid @enderror"
                                   value="{{ old('color', $team->color) }}" maxlength="10">
                            @if($team->color)
                                <span class="d-inline-block rounded" style="width:32px;height:32px;background:#{{ $team->color }};flex-shrink:0"></span>
                            @endif
                        </div>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
