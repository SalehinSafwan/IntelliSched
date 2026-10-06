@props([
    'id' => 'customModal',
    'title' => 'Modal Title',
    'size' => 'modal-md'
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }} modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; box-shadow: var(--shadow-lg);">
            <div class="modal-header border-bottom" style="border-color: var(--border-color) !important; padding: 20px 24px;">
                <h5 class="modal-title brand-font fw-bold text-white" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{ $slot }}
            </div>
            @if(isset($footer))
                <div class="modal-footer border-top" style="border-color: var(--border-color) !important; padding: 16px 24px;">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
