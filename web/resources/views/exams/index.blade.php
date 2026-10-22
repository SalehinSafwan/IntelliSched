@extends('layouts.app')

@section('title', 'Examinations')

@section('content')

<x-page-header eyebrow="Examinations" title="Exam Schedules"
               :subtitle="$canManage
                    ? 'One schedule per batch and exam: a shared time window and rooms, with a date for every course.'
                    : 'Published exam schedules.'">
    @if($canManage)
        <a href="{{ route('exams.create') }}" class="btn-indigo"><i class="bi bi-calendar-plus"></i> New Exam Schedule</a>
    @endif
</x-page-header>

<div class="row g-4 mb-4">
    @forelse($schedules as $schedule)
        @php
            $dated = $schedule->courses->filter(fn ($c) => $c->exam_date);
            $first = $dated->min('exam_date');
            $last = $dated->max('exam_date');
            $session = config('intellisched.exam_sessions')[$schedule->session] ?? null;
        @endphp
        <div class="col-12 col-md-6 col-xxl-4">
            <div class="glass-card card-hoverable h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="page-eyebrow">{{ $schedule->academicTerm?->name }}</div>
                        <div class="brand-font fw-bold text-white fs-4">{{ $schedule->batch?->name }}</div>
                        <div class="text-muted" style="font-size: 0.85rem;">{{ config('intellisched.exam_types')[$schedule->exam_type] ?? $schedule->exam_type }} Examination</div>
                    </div>
                    <x-status-badge :status="$schedule->status" />
                </div>

                <dl class="detail-list mb-3">
                    <div><dt><i class="bi bi-clock me-1"></i> Time</dt><dd>{{ $session['label'] ?? $schedule->start_time . '–' . $schedule->end_time }}</dd></div>
                    <div><dt><i class="bi bi-calendar-range me-1"></i> Dates</dt><dd>{{ $first ? $first->format('d M') . ' – ' . $last->format('d M Y') : 'Not set' }}</dd></div>
                    <div><dt><i class="bi bi-journal me-1"></i> Courses</dt><dd>{{ $dated->count() }} / {{ $schedule->courses->count() }} dated</dd></div>
                    <div><dt><i class="bi bi-door-open me-1"></i> Rooms</dt><dd>{{ $schedule->rooms->pluck('room_code')->implode(', ') ?: '—' }}</dd></div>
                </dl>

                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a href="{{ route('exams.show', $schedule) }}" class="btn-glass btn-sm-custom"><i class="bi bi-eye"></i> View</a>
                    @if($canManage && !$schedule->isPublished())
                        <a href="{{ route('exams.edit', $schedule) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                    @endif
                    <a href="{{ route('export.exam', $schedule) }}" class="btn-glass btn-sm-custom"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="glass-card text-center py-5">
                <div class="empty-state-icon"><i class="bi bi-card-checklist"></i></div>
                <h4 class="brand-font fw-bold text-white mt-4">No exam schedules {{ $canManage ? 'yet' : 'published yet' }}</h4>
                @if($canManage)
                    <a href="{{ route('exams.create') }}" class="btn-indigo mt-2"><i class="bi bi-calendar-plus"></i> Create one</a>
                @endif
            </div>
        </div>
    @endforelse
</div>

@endsection
