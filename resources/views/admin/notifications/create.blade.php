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
                <form action="{{ route('admin.notifications.send') }}" method="POST" id="notifForm">
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

                    {{-- Send Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">When to Send</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_type" id="sendNow"
                                       value="now" {{ old('send_type', 'now') === 'now' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sendNow">
                                    <i class="bi bi-send"></i> Send Now
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_type" id="sendScheduled"
                                       value="scheduled" {{ old('send_type') === 'scheduled' ? 'checked' : '' }}>
                                <label class="form-check-label" for="sendScheduled">
                                    <i class="bi bi-clock"></i> Schedule for Later
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Schedule Date/Time --}}
                    <div class="mb-3" id="scheduleField" style="display: none;">
                        <label class="form-label fw-semibold">Scheduled Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_at" class="form-control"
                               value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                        <div class="form-text">Notification will be sent automatically at this time.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger" id="submitBtn">
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
                            <div class="text-muted mt-1" style="font-size:0.7rem;">IPL Poll &middot; <span id="preview-time">just now</span></div>
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
    const sendNow = document.getElementById('sendNow');
    const sendScheduled = document.getElementById('sendScheduled');
    const scheduleField = document.getElementById('scheduleField');
    const submitBtn = document.getElementById('submitBtn');
    const scheduledInput = document.querySelector('input[name="scheduled_at"]');
    const previewTime = document.getElementById('preview-time');

    function toggleSchedule() {
        const isScheduled = sendScheduled.checked;
        scheduleField.style.display = isScheduled ? 'block' : 'none';
        scheduledInput.required = isScheduled;

        if (isScheduled) {
            submitBtn.innerHTML = '<i class="bi bi-clock"></i> Schedule Notification';
            submitBtn.classList.replace('btn-danger', 'btn-primary');
            submitBtn.onclick = function() { return confirm('Schedule this notification?'); };
        } else {
            submitBtn.innerHTML = '<i class="bi bi-send"></i> Send to All Users';
            submitBtn.classList.replace('btn-primary', 'btn-danger');
            submitBtn.onclick = function() { return confirm('Send this notification to ALL users right now?'); };
        }
    }

    sendNow.addEventListener('change', toggleSchedule);
    sendScheduled.addEventListener('change', toggleSchedule);

    // Initialize on page load
    toggleSchedule();

    // Preview updates
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || 'Notification Title';
    });
    document.querySelector('textarea[name="body"]').addEventListener('input', function() {
        document.getElementById('preview-body').textContent = this.value || 'Notification message will appear here...';
    });
    scheduledInput.addEventListener('input', function() {
        if (this.value) {
            const d = new Date(this.value);
            previewTime.textContent = d.toLocaleString('en-IN', { day:'numeric', month:'short', hour:'numeric', minute:'2-digit', hour12:true });
        } else {
            previewTime.textContent = 'just now';
        }
    });
</script>
@endpush
