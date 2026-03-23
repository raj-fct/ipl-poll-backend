@extends('admin.layouts.app')
@section('title', 'Send Notification')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Send Custom Notification</h4>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.notifications.send') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                               placeholder="e.g. Match Day! CSK vs MI" required maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control" rows="3" required maxlength="500"
                                  placeholder="e.g. Place your prediction now and win big!">{{ old('body') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link to Match <small class="text-muted">(optional)</small></label>
                        <select name="match_id" class="form-select">
                            <option value="">No link (general notification)</option>
                            @foreach($matches as $match)
                                <option value="{{ $match->id }}" {{ old('match_id') == $match->id ? 'selected' : '' }}>
                                    #{{ $match->match_number }} — {{ $match->team_a_short }} vs {{ $match->team_b_short }}
                                    ({{ $match->match_date->format('d M, g:i A') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">If selected, tapping the notification will open this match in the app.</div>
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Send this notification to ALL users?')">
                            <i class="bi bi-send"></i> Send to All Users
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-phone"></i> Preview
            </div>
            <div class="card-body">
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-primary rounded p-1" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-trophy-fill text-white" style="font-size:0.8rem;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:0.85rem;" id="preview-title">Notification Title</div>
                            <div class="text-muted" style="font-size:0.8rem;" id="preview-body">Notification message will appear here...</div>
                            <div class="text-muted mt-1" style="font-size:0.7rem;">IPL Poll &middot; just now</div>
                        </div>
                    </div>
                </div>
                <div class="text-muted small mt-2">
                    <i class="bi bi-info-circle"></i> This will be sent to all active users with push notifications enabled.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || 'Notification Title';
    });
    document.querySelector('textarea[name="body"]').addEventListener('input', function() {
        document.getElementById('preview-body').textContent = this.value || 'Notification message will appear here...';
    });
</script>
@endpush
