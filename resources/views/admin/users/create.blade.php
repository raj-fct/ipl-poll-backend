@extends('admin.layouts.app')

@section('title', 'Create User')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> Create New User
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="bi bi-info-circle"></i> New user will receive <strong>{{ number_format($bonusCoins) }}</strong> bonus coins and must change password on first login.
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile') }}" required>
                        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Temporary Password</label>
                        <input type="text" name="password" class="form-control @error('password') is-invalid @enderror"
                               value="{{ old('password') }}" required>
                        <div class="form-text">Share this with the user. They must change it on first login.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create User
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
