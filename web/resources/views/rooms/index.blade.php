@extends('layouts.app')

@section('title', 'Rooms')

@section('content')

@php
    $icons = ['THEORY' => 'bi-easel2', 'LAB' => 'bi-pc-display', 'MULTIPURPOSE' => 'bi-grid-1x2'];
@endphp

<x-page-header eyebrow="Infrastructure" title="Rooms & Labs"
               subtitle="Rooms with a lower priority number are preferred by the routine generator.">
    <x-export-pdf-button :href="route('export.rooms')" />
    <a href="{{ route('rooms.create') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> New Room</a>
</x-page-header>

<div class="row g-4 mb-4">
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Rooms</div><div class="stat-val text-white">{{ $stats['total'] }}</div></div>
            <div class="stat-icon-wrapper stat-icon-primary"><i class="bi bi-building"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Total Seats</div><div class="stat-val text-white">{{ number_format($stats['seats']) }}</div></div>
            <div class="stat-icon-wrapper stat-icon-success"><i class="bi bi-people"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Labs</div><div class="stat-val text-white">{{ $stats['byType']['LAB'] ?? 0 }}</div></div>
            <div class="stat-icon-wrapper stat-icon-accent"><i class="bi bi-pc-display"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Multipurpose</div><div class="stat-val text-white">{{ $stats['byType']['MULTIPURPOSE'] ?? 0 }}</div></div>
            <div class="stat-icon-wrapper stat-icon-warning"><i class="bi bi-grid-1x2"></i></div>
        </div>
    </div>
</div>

<div class="filter-pills mb-4">
    <a href="{{ route('rooms.index') }}" class="{{ !$type ? 'active' : '' }}">All</a>
    @foreach(config('intellisched.room_types') as $value => $label)
        <a href="{{ route('rooms.index', ['type' => $value]) }}" class="{{ $type === $value ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="row g-4 mb-4">
    @forelse($rooms as $room)
        <div class="col-12 col-md-6 col-xxl-4">
            <div class="glass-card card-hoverable room-card h-100 d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="room-type-icon room-type-{{ $room->room_type }}"><i class="bi {{ $icons[$room->room_type] ?? 'bi-door-open' }}"></i></div>
                        <div>
                            <a href="{{ route('rooms.show', $room) }}" class="brand-font fw-bold text-white fs-5 text-decoration-none">{{ $room->room_code }}</a>
                            <div class="text-muted" style="font-size: 0.82rem;">{{ $room->room_name }}</div>
                        </div>
                    </div>
                    <x-status-badge :status="$room->status" />
                </div>

                <div class="room-meta mb-3">
                    <span><i class="bi bi-people me-1"></i><strong>{{ $room->capacity }}</strong> seats</span>
                    <span><i class="bi bi-sort-numeric-down me-1"></i>Priority <strong>{{ $room->priority }}</strong></span>
                    @if($room->building)
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $room->building }}{{ $room->floor ? ' · Floor ' . $room->floor : '' }}</span>
                    @endif
                </div>

                <div class="mb-3" style="min-height: 28px;">
                    @if($room->room_type === 'THEORY')
                        <span class="text-dim" style="font-size: 0.78rem;">Theory classes</span>
                    @else
                        @forelse($room->eligibleCourses as $course)
                            <span class="badge-custom badge-lab me-1 mb-1">{{ $course->course_code }}</span>
                        @empty
                            <span class="text-dim" style="font-size: 0.78rem;"><i class="bi bi-exclamation-circle me-1"></i>No lab courses assigned</span>
                        @endforelse
                    @endif
                </div>

                <div class="d-flex gap-2 mt-auto">
                    <a href="{{ route('rooms.show', $room) }}" class="btn-glass btn-sm-custom"><i class="bi bi-eye"></i> View</a>
                    <a href="{{ route('rooms.edit', $room) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                    <x-delete-button :action="route('rooms.destroy', $room)" :confirm="'Delete room ' . $room->room_code . '?'" />
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="glass-card text-center py-5">
                <div class="empty-state-icon"><i class="bi bi-building-add"></i></div>
                <h4 class="brand-font fw-bold text-white mt-4">No rooms here yet</h4>
                <a href="{{ route('rooms.create') }}" class="btn-indigo mt-2"><i class="bi bi-plus-lg"></i> Add a room</a>
            </div>
        </div>
    @endforelse
</div>

@endsection
