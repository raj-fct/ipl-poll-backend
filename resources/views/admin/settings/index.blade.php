@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear"></i> App Settings
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Welcome Bonus Coins</label>
                        <input type="number" name="bonus_coins" class="form-control @error('bonus_coins') is-invalid @enderror"
                               value="{{ old('bonus_coins', $settings['bonus_coins']->value ?? 1000) }}" min="0" required>
                        <div class="form-text">Coins awarded to each new user on creation.</div>
                        @error('bonus_coins')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Bid</label>
                            <input type="number" name="min_bid" class="form-control @error('min_bid') is-invalid @enderror"
                                   value="{{ old('min_bid', $settings['min_bid']->value ?? 10) }}" min="1" required>
                            @error('min_bid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Bid</label>
                            <input type="number" name="max_bid" class="form-control @error('max_bid') is-invalid @enderror"
                                   value="{{ old('max_bid', $settings['max_bid']->value ?? 5000) }}" min="1" required>
                            @error('max_bid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Current Season</label>
                        <input type="text" name="season" class="form-control @error('season') is-invalid @enderror"
                               value="{{ old('season', $settings['season']->value ?? 'IPL 2025') }}" required>
                        @error('season')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
