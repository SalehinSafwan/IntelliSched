@extends('pdf.layout')

@section('content')

<p class="muted">{{ $profiles->count() }} teachers. Suitability = 40% expertise + 30% teaching history + 20% preference + 10% workload.</p>

@foreach($profiles as $profile)
    @php $teacher = $profile['teacher']; @endphp
    <div class="card">
        <div class="name">{{ $teacher->display_name }}
            <span class="badge {{ $teacher->status === 'ACTIVE' ? 'badge-ok' : 'badge-off' }}">{{ $teacher->status }}</span>
        </div>
        <table class="kv">
            <tr><td class="k">Employee code</td><td>{{ $teacher->employee_code }}</td></tr>
            <tr><td class="k">Department</td><td>{{ $teacher->department }}</td></tr>
            <tr><td class="k">Email</td><td>{{ $teacher->user?->email }}</td></tr>
            <tr><td class="k">Maximum weekly periods</td><td>{{ $teacher->max_weekly_classes }}</td></tr>
            <tr><td class="k">Availability</td><td>
                {{ $teachingSlots - $profile['unavailable'] }} of {{ $teachingSlots }} periods available
                · {{ $profile['preferred'] }} preferred · {{ $profile['avoid'] }} to avoid
            </td></tr>
        </table>

        @if($profile['courses']->isNotEmpty())
            <table>
                <thead><tr><th>Course</th><th>Title</th><th>Expertise</th><th>Preference</th><th>Suitability</th></tr></thead>
                <tbody>
                    @foreach($profile['courses'] as $course)
                        <tr>
                            <td><strong>{{ $course['code'] }}</strong></td>
                            <td>{{ $course['name'] }}</td>
                            <td>{{ $course['expertise'] }}</td>
                            <td>{{ $course['preference'] ?? '—' }}</td>
                            <td><strong>{{ $course['score'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="muted">No courses assigned.</p>
        @endif
    </div>
@endforeach

@endsection
