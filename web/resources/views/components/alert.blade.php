@if (session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #6ee7b7; border-radius: 14px; padding: 14px 18px;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; border-radius: 14px; padding: 14px 18px;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; border-radius: 14px; padding: 14px 18px;">
        <div class="d-flex align-items-center gap-2 fw-semibold mb-2">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <span>Please review the errors below:</span>
        </div>
        <ul class="mb-0 ps-4" style="font-size: 0.88rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
