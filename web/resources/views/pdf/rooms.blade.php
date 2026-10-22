@extends('pdf.layout')

@section('content')

<p class="muted">{{ $rooms->count() }} rooms · {{ $rooms->sum(fn ($r) => (int) $r->capacity) }} seats in total. Lower priority numbers are preferred by the routine generator.</p>

<table>
    <thead>
        <tr><th>Code</th><th>Name</th><th>Type</th><th>Capacity</th><th>Priority</th><th>Location</th><th>Lab courses</th><th>Status</th></tr>
    </thead>
    <tbody>
        @foreach($rooms as $room)
            <tr>
                <td><strong>{{ $room->room_code }}</strong></td>
                <td>{{ $room->room_name }}</td>
                <td>{{ config('intellisched.room_types')[$room->room_type] ?? $room->room_type }}</td>
                <td>{{ $room->capacity }}</td>
                <td>{{ $room->priority }}</td>
                <td>{{ $room->building ?: '—' }}{{ $room->floor ? ', floor ' . $room->floor : '' }}</td>
                <td>
                    @forelse($room->eligibleCourses as $course)
                        <span class="badge badge-lab">{{ $course->course_code }}</span>
                    @empty
                        <span class="muted">{{ $room->room_type === 'THEORY' ? 'Theory only' : 'None' }}</span>
                    @endforelse
                </td>
                <td><span class="badge {{ $room->status === 'ACTIVE' ? 'badge-ok' : 'badge-off' }}">{{ $room->status }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
