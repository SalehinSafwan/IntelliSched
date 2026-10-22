@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

@php
    $isManager = in_array($role, ['admin', 'coordinator'], true);
    $runByBatch = $publishedRuns->keyBy('batch_id');
@endphp

<!-- Welcome -->
<div class="glass-card mb-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.1)); border-color: rgba(139, 92, 246, 0.32);">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="page-eyebrow">{{ config('intellisched.roles')[$role] ?? ucfirst($role) }}</div>
            <h2 class="brand-font fw-bold mb-1 text-gradient">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                @if($term)
                    Current term: <span class="text-indigo fw-semibold">{{ $term->name }}</span>
                    · {{ \Carbon\Carbon::parse($term->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($term->end_date)->format('d M Y') }}
                @else
                    No academic term is marked as current.
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($isManager)
                <a href="{{ route('schedules.create') }}" class="btn-indigo"><i class="bi bi-stars"></i> Generate Routine</a>
                <a href="{{ route('exams.create') }}" class="btn-glass"><i class="bi bi-calendar-plus"></i> Schedule Exams</a>
            @elseif($role === 'teacher')
                <a href="{{ route('my.schedule') }}" class="btn-indigo"><i class="bi bi-calendar3"></i> My Schedule</a>
                <a href="{{ route('my.availability') }}" class="btn-glass"><i class="bi bi-calendar-week"></i> My Availability</a>
            @else
                <a href="{{ route('schedules.index') }}" class="btn-indigo"><i class="bi bi-calendar3"></i> Class Routines</a>
                <a href="{{ route('exams.index') }}" class="btn-glass"><i class="bi bi-card-checklist"></i> Exam Schedules</a>
            @endif
        </div>
    </div>
</div>

@if($isManager)
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('teachers.index') }}" class="text-decoration-none">
                <div class="glass-card card-hoverable stat-card">
                    <div><div class="stat-lbl">Active Teachers</div><div class="stat-val text-white">{{ $stats['teachers'] }}</div></div>
                    <div class="stat-icon-wrapper stat-icon-primary"><i class="bi bi-person-video3"></i></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('courses.index') }}" class="text-decoration-none">
                <div class="glass-card card-hoverable stat-card">
                    <div>
                        <div class="stat-lbl">Active Courses</div><div class="stat-val text-white">{{ $stats['courses'] }}</div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">{{ $stats['theory'] }} theory · {{ $stats['lab'] }} lab</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-accent"><i class="bi bi-journal-bookmark-fill"></i></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('rooms.index') }}" class="text-decoration-none">
                <div class="glass-card card-hoverable stat-card">
                    <div>
                        <div class="stat-lbl">Rooms & Labs</div><div class="stat-val text-white">{{ $stats['rooms'] }}</div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">{{ number_format($stats['seats']) }} seats</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-success"><i class="bi bi-building"></i></div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('batches.index') }}" class="text-decoration-none">
                <div class="glass-card card-hoverable stat-card">
                    <div>
                        <div class="stat-lbl">Active Batches</div><div class="stat-val text-white">{{ $stats['batches'] }}</div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">{{ $stats['sections'] }} sections · {{ $stats['students'] }} students</div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-warning"><i class="bi bi-mortarboard-fill"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Routine status per batch -->
        <div class="col-12 col-xl-7">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="brand-font fw-bold text-white mb-0">Routine Status{{ $term ? ' · ' . $term->name : '' }}</h5>
                    <a href="{{ route('schedules.index') }}" class="text-indigo text-decoration-none" style="font-size: 0.82rem;">All routines</a>
                </div>
                @forelse($batches as $batch)
                    @php $run = $runByBatch->get($batch->id); @endphp
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-bottom border-secondary">
                        <div>
                            <span class="fw-bold text-white brand-font">{{ $batch->name }}</span>
                            <span class="text-dim ms-1" style="font-size: 0.78rem;">Seniority {{ $batch->seniority_order }} · {{ $batch->sections->count() }} sections</span>
                        </div>
                        @if($run)
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge-custom badge-active badge-dot">Published v{{ $run->version }}</span>
                                <a href="{{ route('schedules.show', $run->id) }}" class="btn-glass btn-sm-custom"><i class="bi bi-eye"></i> View</a>
                            </div>
                        @else
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge-custom badge-archived">No routine</span>
                                <a href="{{ route('schedules.create') }}" class="btn-glass btn-sm-custom"><i class="bi bi-stars"></i> Generate</a>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">No active batches. <a href="{{ route('batches.create') }}">Create one</a>.</p>
                @endforelse
                <div class="d-flex gap-3 mt-3 text-muted" style="font-size: 0.8rem;">
                    <span><i class="bi bi-card-checklist me-1"></i>{{ $publishedExams->count() }} published exam schedule(s)</span>
                    <span><i class="bi bi-pencil-square me-1"></i>{{ $draftExams }} draft(s)</span>
                </div>
            </div>
        </div>

        <!-- Readiness -->
        <div class="col-12 col-xl-5">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="brand-font fw-bold text-white mb-0">Generator Readiness</h5>
                    @if(empty($issues))
                        <span class="badge-custom badge-active badge-dot">Ready</span>
                    @else
                        <span class="badge-custom badge-inactive badge-dot">{{ count($issues) }} issue(s)</span>
                    @endif
                </div>
                @if(empty($issues))
                    <p class="text-muted mb-0"><i class="bi bi-check-circle-fill text-emerald me-1"></i> Every course has a teacher pair, every lab course has a room and every batch has a course list.</p>
                @else
                    <ul class="conflict-list">
                        @foreach(array_slice($issues, 0, 6) as $issue)
                            <li><a href="{{ $issue['url'] }}" class="text-decoration-none" style="color: inherit;">{{ $issue['text'] }} <i class="bi bi-arrow-right-short"></i></a></li>
                        @endforeach
                    </ul>
                    @if(count($issues) > 6)<p class="text-dim mt-2 mb-0" style="font-size: 0.8rem;">and {{ count($issues) - 6 }} more…</p>@endif
                @endif
            </div>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="glass-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="brand-font fw-bold text-white mb-0">Recent Activity</h5>
            @if($role === 'admin')
                <a href="{{ route('audit.index') }}" class="text-indigo text-decoration-none" style="font-size: 0.82rem;">Audit trail</a>
            @endif
        </div>
        @forelse($activity as $log)
            <div class="d-flex justify-content-between gap-3 py-2 border-bottom border-secondary" style="font-size: 0.86rem;">
                <span><span class="fw-semibold text-white">{{ $log->user?->name ?? 'System' }}</span>
                    <span class="text-muted">· {{ strtolower(str_replace('_', ' ', $log->action)) }}</span></span>
                <span class="text-dim" style="white-space: nowrap;">{{ $log->created_at?->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">No activity recorded yet.</p>
        @endforelse
    </div>
@else
    <div class="row g-4 mb-4">
        @if($role === 'teacher')
            <div class="col-12 col-md-4">
                <div class="glass-card stat-card">
                    <div>
                        <div class="stat-lbl">My Weekly Periods</div>
                        <div class="stat-val text-white">{{ $teacherPeriods }}<span class="text-muted fs-6"> / {{ $teacher?->max_weekly_classes ?? '—' }}</span></div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-primary"><i class="bi bi-speedometer2"></i></div>
                </div>
            </div>
        @endif
        <div class="col-12 {{ $role === 'teacher' ? 'col-md-8' : '' }}">
            <div class="glass-card h-100">
                <h5 class="brand-font fw-bold text-white mb-3">Published Routines</h5>
                @forelse($publishedRuns as $run)
                    <a href="{{ route('schedules.show', $run->id) }}" class="badge-custom badge-active text-decoration-none me-2 mb-2">{{ $run->batch_name }} · v{{ $run->version }}</a>
                @empty
                    <p class="text-muted mb-0">No routines have been published yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="glass-card mb-4">
        <h5 class="brand-font fw-bold text-white mb-3">Upcoming Exams</h5>
        @php
            $upcoming = $publishedExams->flatMap(fn ($s) => $s->courses->map(fn ($c) => ['schedule' => $s, 'course' => $c]))
                ->filter(fn ($row) => $row['course']->exam_date && $row['course']->exam_date->gte(today()))
                ->sortBy(fn ($row) => $row['course']->exam_date->timestamp)
                ->take(8);
        @endphp
        @forelse($upcoming as $row)
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.86rem;">
                <span><span class="fw-semibold text-white">{{ $row['schedule']->batch?->name }}</span> <span class="text-muted">· {{ $row['course']->course?->course_code }} {{ $row['course']->course?->course_name }}</span></span>
                <a href="{{ route('exams.show', $row['schedule']) }}" class="text-indigo text-decoration-none">{{ $row['course']->exam_date->format('D d M Y') }}</a>
            </div>
        @empty
            <p class="text-muted mb-0">No upcoming exams.</p>
        @endforelse
    </div>
@endif

@endsection
