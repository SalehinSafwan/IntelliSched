@extends('layouts.app')

@section('title', $exam->batch->name . ' Exams')

@section('content')

@php
    $session = config('intellisched.exam_sessions')[$exam->session] ?? null;
    $courses = $exam->courses->sortBy(fn ($c) => $c->exam_date?->timestamp ?? PHP_INT_MAX);
@endphp

<x-page-header :back="route('exams.index')" back-label="Exam Schedules"
               :eyebrow="(config('intellisched.exam_types')[$exam->exam_type] ?? $exam->exam_type) . ' Examination · ' . $exam->academicTerm?->name"
               :title="$exam->batch->name"
               :subtitle="($session['label'] ?? $exam->start_time . '–' . $exam->end_time) . ' · Rooms: ' . ($exam->rooms->pluck('room_code')->implode(', ') ?: '—')">
    <x-status-badge :status="$exam->status" />
    <x-export-pdf-button :href="route('export.exam', $exam)" />
    @if($canManage)
        @if($exam->isPublished())
            <form method="POST" action="{{ route('exams.unpublish', $exam) }}" onsubmit="return confirm('Move this schedule back to draft? Its rooms are released.');">
                @csrf
                <button class="btn-glass"><i class="bi bi-arrow-counterclockwise"></i> Unpublish</button>
            </form>
        @else
            <a href="{{ route('exams.edit', $exam) }}" class="btn-indigo"><i class="bi bi-pencil"></i> Edit</a>
        @endif
    @endif
</x-page-header>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr><th>Date</th><th>Day</th><th>Course</th><th>Time</th><th>Rooms</th></tr>
        </thead>
        <tbody>
            @foreach($courses as $examCourse)
                <tr>
                    <td class="fw-bold text-white brand-font">{{ $examCourse->exam_date?->format('d M Y') ?? 'Not set' }}</td>
                    <td class="text-muted">{{ $examCourse->exam_date?->format('l') }}</td>
                    <td>
                        <span class="fw-semibold text-white">{{ $examCourse->course?->course_code }}</span>
                        <span class="text-muted">— {{ $examCourse->course?->course_name }}</span>
                    </td>
                    <td>{{ $session['label'] ?? $exam->start_time . '–' . $exam->end_time }}</td>
                    <td>
                        @foreach($exam->rooms as $room)
                            <span class="badge-custom badge-theory me-1">{{ $room->room_code }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($exam->published_at)
    <p class="text-dim" style="font-size: 0.8rem;">Published {{ $exam->published_at->format('d M Y, h:i A') }}{{ $exam->createdBy ? ' · created by ' . $exam->createdBy->name : '' }}.</p>
@endif

@endsection
