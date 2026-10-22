@props([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'back' => null,
    'backLabel' => 'Back',
])

{{-- Standard page heading with optional back link and action buttons. --}}
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        @if($back)
            <a href="{{ $back }}" class="back-link"><i class="bi bi-arrow-left"></i> {{ $backLabel }}</a>
        @endif
        @if($eyebrow)
            <div class="page-eyebrow {{ $back ? 'mt-3' : '' }}">{{ $eyebrow }}</div>
        @endif
        <h2 class="brand-font fw-bold text-gradient mt-1 mb-1">{{ $title }}</h2>
        @if($subtitle)
            <p class="text-muted mb-0" style="font-size: 0.88rem;">{{ $subtitle }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="d-flex gap-2 flex-wrap no-print">{{ $slot }}</div>
    @endif
</div>
