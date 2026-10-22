@extends('layouts.app')

@section('title', $room->room_code)

@section('content')

@php
    $usedPeriods = $usage->count();
    $utilisation = $teachingSlots > 0 ? round($usedPeriods / $teachingSlots * 100) : 0;
    $dayOrder = array_flip(config('intellisched.days'));
    $byDay = $usage->sortBy(fn ($u) => [($dayOrder[strtoupper($u->day)] ?? 99), (int) $u->slot_number])
                   ->groupBy(fn ($u) => strtoupper($u->day));
@endphp

<x-page-header :back="route('rooms.index')" back-label="All Rooms"
               :eyebrow="config('intellisched.room_types')[$room->room_type] ?? $room->room_type"
               :title="$room->room_code . ' — ' . $room->room_name">
    <a href="{{ route('rooms.edit', $room) }}" class="btn-indigo"><i class="bi bi-pencil"></i> Edit Room</a>
</x-page-header>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-5">
        <div class="glass-card h-100">
            <h5 class="brand-font fw-bold text-white mb-3">Details</h5>
            <dl class="detail-list mb-0">
                <div><dt>Capacity</dt><dd>{{ $room->capacity }} seats</dd></div>
                <div><dt>Priority</dt><dd>{{ $room->priority }}</dd></div>
                <div><dt>Building</dt><dd>{{ $room->building ?: '—' }}</dd></div>
                <div><dt>Floor</dt><dd>{{ $room->floor ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd><x-status-badge :status="$room->status" /></dd></div>
                <div>
                    <dt>Lab courses</dt>
                    <dd>
                        @if($room->room_type === 'THEORY')
                            —
                        @else
                            @forelse($room->eligibleCourses as $course)
                                <span class="badge-custom badge-lab ms-1">{{ $course->course_code }}</span>
                            @empty
                                <span class="text-muted">None</span>
                            @endforelse
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="glass-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="brand-font fw-bold text-white mb-0">Weekly Utilisation</h5>
                <span class="score-chip">{{ $utilisation }}%</span>
            </div>
            <p class="text-muted" style="font-size: 0.84rem;">{{ $usedPeriods }} of {{ $teachingSlots }} teaching periods are used by published routines.</p>
            <div class="load-bar mb-4 {{ $utilisation >= 85 ? 'is-high' : '' }}"><span style="width: {{ $utilisation }}%;"></span></div>

            @forelse($byDay as $day => $periods)
                <div class="d-flex flex-wrap align-items-center gap-2 py-2 border-bottom border-secondary">
                    <span class="text-muted fw-semibold" style="width: 110px; font-size: 0.78rem; letter-spacing: 0.08em;">{{ $day }}</span>
                    @foreach($periods as $period)
                        <span class="badge-custom badge-theory" title="{{ $period->batch_name }} · Section {{ $period->section_name }}">
                            {{ $period->start_time }} {{ $period->course_code }}
                        </span>
                    @endforeach
                </div>
            @empty
                <p class="text-muted mb-0">Not used by any published routine yet.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
