@extends('pdf.layout')

@section('content')

<p class="muted">{{ $courses->count() }} courses. Theory courses meet 3 times a week (one class per pair teacher plus one combined); labs run in 3-period sessions taught jointly.</p>

<table>
    <thead>
        <tr><th>Code</th><th>Title</th><th>Type</th><th>Credits</th><th>Weekly</th><th>Qualified teachers</th><th>Lab rooms</th><th>Status</th></tr>
    </thead>
    <tbody>
        @foreach($courses as $course)
            <tr>
                <td><strong>{{ $course->course_code }}</strong></td>
                <td>{{ $course->course_name }}<br><span class="muted">{{ $course->department }}</span></td>
                <td><span class="badge {{ $course->course_type === 'LAB' ? 'badge-lab' : '' }}">{{ $course->course_type }}</span></td>
                <td>{{ rtrim(rtrim(number_format((float) $course->credit_hours, 2), '0'), '.') }}</td>
                <td>{{ $course->course_type === 'LAB' ? $course->weekly_lab_classes . ' × lab' : $course->weekly_theory_classes . ' classes' }}</td>
                <td>{{ $course->expertise->map(fn ($e) => $e->teacher?->display_name)->filter()->implode(', ') ?: '—' }}</td>
                <td>{{ $course->roomEligibility->map(fn ($e) => $roomCodes[$e->room_id] ?? null)->filter()->implode(', ') ?: '—' }}</td>
                <td><span class="badge {{ $course->status === 'ACTIVE' ? 'badge-ok' : 'badge-off' }}">{{ $course->status }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
