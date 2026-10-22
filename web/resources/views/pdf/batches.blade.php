@extends('pdf.layout')

@section('content')

<p class="muted">{{ $batches->count() }} batches. Seniority 1 is the most senior batch.{{ $term ? ' Course lists are for ' . $term->name . '.' : '' }}</p>

@foreach($batches as $batch)
    @php $courses = $coursesByBatch[$batch->id] ?? collect(); @endphp
    <div class="card">
        <div class="name">{{ $batch->name }}
            <span class="badge {{ $batch->status === 'ACTIVE' ? 'badge-ok' : 'badge-off' }}">{{ $batch->status }}</span>
        </div>
        <table class="kv">
            <tr><td class="k">Program</td><td>{{ $batch->program }} · {{ $batch->department }}</td></tr>
            <tr><td class="k">Admission year</td><td>{{ $batch->admission_year }}</td></tr>
            <tr><td class="k">Seniority</td><td>{{ $batch->seniority_order }}</td></tr>
            <tr><td class="k">Sections</td><td>
                @foreach($batch->sections->sortBy('name') as $section)
                    <span class="badge">{{ $section->name }} · {{ $section->student_count }} students</span>
                @endforeach
                ({{ $batch->sections->sum(fn ($s) => (int) $s->student_count) }} students)
            </td></tr>
            <tr><td class="k">Courses this term</td><td>
                @forelse($courses as $course)
                    <span class="badge {{ $course->course_type === 'LAB' ? 'badge-lab' : '' }}">{{ $course->course_code }}</span>
                @empty
                    <span class="muted">None set</span>
                @endforelse
            </td></tr>
        </table>
    </div>
@endforeach

@endsection
