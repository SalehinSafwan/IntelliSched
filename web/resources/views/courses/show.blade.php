@extends('layouts.app')

@section('title', $course->course_code)

@section('content')

<x-page-header :back="route('courses.index')" back-label="All Courses"
               :eyebrow="$course->course_type . ' course · ' . $course->department"
               :title="$course->course_code . ' — ' . $course->course_name">
    <a href="{{ route('courses.edit', $course) }}" class="btn-indigo"><i class="bi bi-pencil"></i> Edit Course</a>
</x-page-header>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <div class="glass-card h-100">
            <h5 class="brand-font fw-bold text-white mb-3">Details</h5>
            <dl class="detail-list mb-0">
                <div><dt>Type</dt><dd><span class="badge-custom {{ $course->course_type === 'LAB' ? 'badge-lab' : 'badge-theory' }}">{{ $course->course_type }}</span></dd></div>
                <div><dt>Credit hours</dt><dd>{{ rtrim(rtrim(number_format((float) $course->credit_hours, 2), '0'), '.') }}</dd></div>
                <div><dt>Theory classes / week</dt><dd>{{ $course->weekly_theory_classes }}</dd></div>
                <div><dt>Lab sessions / week</dt><dd>{{ $course->weekly_lab_classes }}</dd></div>
                <div><dt>Status</dt><dd><x-status-badge :status="$course->status" /></dd></div>
            </dl>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="glass-card h-100">
            <h5 class="brand-font fw-bold text-white mb-3">Qualified Teachers</h5>
            @forelse($course->expertise->sortByDesc('expertise_score') as $expertise)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                    <a href="{{ route('teachers.show', $expertise->teacher_id) }}" class="text-white text-decoration-none fw-semibold">
                        {{ $expertise->teacher?->display_name }}
                    </a>
                    <span class="score-chip">{{ $expertise->expertise_score }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No teacher has expertise recorded for this course. Set it on the
                    <a href="{{ route('teachers.preferences') }}">Teacher Suitability</a> page — the generator needs at least two.</p>
            @endforelse
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="glass-card h-100">
            <h5 class="brand-font fw-bold text-white mb-3">Eligible Lab Rooms</h5>
            @if($course->course_type !== 'LAB')
                <p class="text-muted mb-0">Theory courses can use any theory or multipurpose room with enough capacity.</p>
            @else
                @forelse($eligibleRooms as $room)
                    <a href="{{ route('rooms.show', $room) }}" class="badge-custom badge-lab text-decoration-none me-1 mb-2">{{ $room->room_code }}</a>
                @empty
                    <p class="text-muted mb-0">No lab room is marked as eligible yet. Choose rooms on each room's edit page.</p>
                @endforelse
            @endif
        </div>
    </div>
</div>

<h5 class="brand-font fw-bold text-white mb-3">Offerings</h5>
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead><tr><th>Term</th><th>Batch</th><th>Section</th><th>Weekly</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($offerings as $offering)
                <tr>
                    <td>{{ $offering->academicTerm?->name }}</td>
                    <td><a href="{{ route('batches.show', $offering->section->batch_id) }}" class="text-white fw-semibold text-decoration-none">{{ $offering->section?->batch?->name }}</a></td>
                    <td>Section {{ $offering->section?->name }}</td>
                    <td class="text-muted">{{ $offering->weekly_theory_classes }} theory · {{ $offering->weekly_lab_classes }} lab</td>
                    <td><x-status-badge :status="$offering->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Not offered to any batch yet. Add it from a batch's course list.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
