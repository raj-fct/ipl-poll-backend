@extends('admin.layouts.app')

@section('title', 'Add Team')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-plus"></i> Add New Team
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.teams.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g., Mumbai Indians" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Name / Code</label>
                        <input type="text" name="short_name" class="form-control @error('short_name') is-invalid @enderror"
                               value="{{ old('short_name') }}" placeholder="e.g., MI" maxlength="10" required>
                        @error('short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ESPN ID <small class="text-muted">(optional)</small></label>
                        <input type="text" name="espn_id" class="form-control @error('espn_id') is-invalid @enderror"
                               value="{{ old('espn_id') }}" placeholder="e.g., 335978">
                        @error('espn_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo URL <small class="text-muted">(optional)</small></label>
                        <input type="url" name="logo" class="form-control @error('logo') is-invalid @enderror"
                               value="{{ old('logo') }}" placeholder="https://...">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color Hex <small class="text-muted">(optional, without #)</small></label>
                        <input type="text" name="color" class="form-control @error('color') is-invalid @enderror"
                               value="{{ old('color') }}" placeholder="e.g., 003B7A" maxlength="10">
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Team</button>
                        <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
