@extends('layouts.app')

@section('title', 'Teacher Suitability')

@section('content')

<x-page-header eyebrow="Intelligence & Optimization" title="Teacher–Course Suitability"
               subtitle="Computed from expertise, teaching history, preference and workload. Empty cells mean the teacher is not eligible for that course.">
    <a href="{{ route('teachers.index') }}" class="btn-glass"><i class="bi bi-people"></i> Teachers</a>
</x-page-header>

@if($teachers->isEmpty() || $courses->isEmpty())
    <div class="glass-card text-muted">Add teachers and active courses to see the matrix.</div>
@else
    <div class="d-flex flex-wrap gap-3 align-items-center mb-3 text-muted" style="font-size: 0.8rem;">
        <span>Low</span>
        <span style="width: 160px; height: 10px; border-radius: 6px; background: linear-gradient(90deg, rgba(99,102,241,0.15), rgba(139,92,246,0.55), rgba(16,185,129,0.85));"></span>
        <span>High</span>
        <span class="ms-3"><i class="bi bi-info-circle me-1"></i>Click a teacher to edit their courses and scores.</span>
    </div>

    <div class="table-custom-container mb-4">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Teacher</th>
                    @foreach($courses as $course)
                        <th class="text-center" title="{{ $course->course_name }}">{{ $course->course_code }}</th>
                    @endforeach
                    <th class="text-center">Courses</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teachers as $teacher)
                    <tr>
                        <td>
                            <a href="{{ route('teachers.show', $teacher) }}" class="fw-semibold text-white text-decoration-none">{{ $teacher->display_name }}</a>
                            <div class="text-dim" style="font-size: 0.74rem;">{{ $teacher->employee_code }}</div>
                        </td>
                        @foreach($courses as $course)
                            @php $score = $matrix[$teacher->id][$course->id]; @endphp
                            <td class="text-center">
                                @if($score === null)
                                    <span class="text-dim">—</span>
                                @else
                                    @php
                                        $alpha = 0.15 + min(1, $score / 100) * 0.7;
                                        $color = $score >= 70 ? "rgba(16,185,129,$alpha)" : "rgba(139,92,246,$alpha)";
                                    @endphp
                                    <span class="d-inline-block fw-bold text-white" style="min-width: 52px; padding: 6px 8px; border-radius: 9px; background: {{ $color }};">{{ $score }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center">{{ collect($matrix[$teacher->id])->filter(fn ($s) => $s !== null)->count() }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="text-muted fw-semibold">Eligible teachers</td>
                    @foreach($courses as $course)
                        @php $eligible = collect($matrix)->filter(fn ($row) => $row[$course->id] !== null)->count(); @endphp
                        <td class="text-center">
                            <span class="badge-custom {{ $eligible >= 2 ? 'badge-active' : 'badge-inactive' }}" title="{{ $eligible >= 2 ? 'Enough for a teacher pair' : 'Needs at least 2 for a pair' }}">{{ $eligible }}</span>
                        </td>
                    @endforeach
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="text-dim" style="font-size: 0.8rem;"><i class="bi bi-exclamation-triangle me-1"></i>Every course needs at least 2 eligible teachers, because each course is taught by a teacher pair.</p>
@endif

@endsection
