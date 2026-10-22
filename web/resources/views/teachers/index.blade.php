@extends('layouts.app')

@section('title', 'Teachers')

@section('content')

<x-page-header eyebrow="Faculty" title="Teachers"
               subtitle="Each teacher has a login, a weekly period limit, the courses they can teach and their time preferences.">
    <x-export-pdf-button :href="route('export.teachers')" />
    <a href="{{ route('teachers.preferences') }}" class="btn-glass"><i class="bi bi-grid-3x3-gap"></i> Suitability Matrix</a>
    <a href="{{ route('teachers.create') }}" class="btn-indigo"><i class="bi bi-person-plus"></i> New Teacher</a>
</x-page-header>

<div class="row g-4 mb-4">
    @forelse($teachers as $teacher)
        @php
            $load = (int) ($loads[$teacher->id] ?? 0);
            $max = max(1, (int) $teacher->max_weekly_classes);
            $percent = min(100, round($load / $max * 100));
        @endphp
        <div class="col-12 col-md-6 col-xxl-4">
            <div class="glass-card card-hoverable h-100 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar-circle" style="width: 48px; height: 48px; font-size: 1.1rem;">{{ strtoupper(substr($teacher->display_name, 0, 1)) }}</div>
                    <div class="flex-grow-1 min-w-0">
                        <a href="{{ route('teachers.show', $teacher) }}" class="brand-font fw-bold text-white fs-5 text-decoration-none">{{ $teacher->display_name }}</a>
                        <div class="text-muted" style="font-size: 0.8rem;">{{ $teacher->employee_code }} · {{ $teacher->department }} · {{ $teacher->user?->email }}</div>
                    </div>
                    <x-status-badge :status="$teacher->status" />
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                        <span class="text-muted">Published weekly load</span>
                        <span class="text-white fw-bold">{{ $load }} / {{ $teacher->max_weekly_classes }} periods</span>
                    </div>
                    <div class="load-bar {{ $percent >= 85 ? 'is-high' : '' }}"><span style="width: {{ $percent }}%;"></span></div>
                </div>

                <div class="mb-3" style="min-height: 28px;">
                    @forelse($teacher->expertise->sortByDesc('expertise_score')->take(6) as $expertise)
                        <span class="badge-custom badge-theory me-1 mb-1">{{ $expertise->course?->course_code }}</span>
                    @empty
                        <span class="text-dim" style="font-size: 0.78rem;"><i class="bi bi-exclamation-circle me-1"></i>No courses assigned yet</span>
                    @endforelse
                </div>

                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a href="{{ route('teachers.show', $teacher) }}" class="btn-glass btn-sm-custom"><i class="bi bi-person-lines-fill"></i> Profile</a>
                    <a href="{{ route('teachers.availability', $teacher) }}" class="btn-glass btn-sm-custom"><i class="bi bi-calendar-week"></i> Availability</a>
                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="glass-card text-center py-5">
                <div class="empty-state-icon"><i class="bi bi-person-video3"></i></div>
                <h4 class="brand-font fw-bold text-white mt-4">No teachers yet</h4>
                <a href="{{ route('teachers.create') }}" class="btn-indigo mt-2"><i class="bi bi-person-plus"></i> Add a teacher</a>
            </div>
        </div>
    @endforelse
</div>

@endsection
