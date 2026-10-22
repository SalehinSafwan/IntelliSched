@extends('pdf.layout')

@section('content')

@php
    $session = config('intellisched.exam_sessions')[$exam->session] ?? null;
    $courses = $exam->courses->sortBy(fn ($c) => $c->exam_date?->timestamp ?? PHP_INT_MAX);
@endphp

<table class="kv" style="margin-bottom: 12px;">
    <tr><td class="k">Batch</td><td><strong>{{ $exam->batch->name }}</strong></td></tr>
    <tr><td class="k">Academic term</td><td>{{ $exam->academicTerm?->name }}</td></tr>
    <tr><td class="k">Exam time</td><td>{{ $session['label'] ?? $exam->start_time . '–' . $exam->end_time }}</td></tr>
    <tr><td class="k">Rooms</td><td>{{ $exam->rooms->pluck('room_code')->implode(', ') ?: '—' }}</td></tr>
    <tr><td class="k">Status</td><td><span class="badge {{ $exam->isPublished() ? 'badge-ok' : '' }}">{{ $exam->status }}</span></td></tr>
</table>

<table>
    <thead><tr><th>Date</th><th>Day</th><th>Course</th><th>Title</th><th>Time</th></tr></thead>
    <tbody>
        @foreach($courses as $examCourse)
            <tr>
                <td><strong>{{ $examCourse->exam_date?->format('d M Y') ?? 'Not set' }}</strong></td>
                <td>{{ $examCourse->exam_date?->format('l') }}</td>
                <td>{{ $examCourse->course?->course_code }}</td>
                <td>{{ $examCourse->course?->course_name }}</td>
                <td>{{ $session['label'] ?? $exam->start_time . '–' . $exam->end_time }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
