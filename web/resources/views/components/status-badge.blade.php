@props(['status'])

@php
    $class = match (strtoupper((string) $status)) {
        'ACTIVE', 'PUBLISHED', 'SCHEDULED' => 'badge-active',
        'INACTIVE', 'CANCELLED' => 'badge-inactive',
        'ARCHIVED', 'COMPLETED' => 'badge-archived',
        default => 'badge-role',
    };
@endphp

<span class="badge-custom {{ $class }} badge-dot">{{ strtoupper((string) $status) }}</span>
