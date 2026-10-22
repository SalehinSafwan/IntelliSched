@extends('layouts.app')

@section('title', $teacher->display_name)

@section('content')

<x-page-header :back="route('teachers.index')" back-label="All Teachers"
               :eyebrow="$teacher->employee_code . ' · ' . $teacher->department"
               :title="$teacher->display_name"
               :subtitle="$teacher->user?->email">
    <x-status-badge :status="$teacher->status" />
    <a href="{{ route('teachers.availability', $teacher) }}" class="btn-glass"><i class="bi bi-calendar-week"></i> Availability</a>
    <a href="{{ route('teachers.edit', $teacher) }}" class="btn-indigo"><i class="bi bi-pencil"></i> Edit Profile</a>
</x-page-header>

<div class="row g-4 mb-4">
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Weekly Load</div><div class="stat-val text-white">{{ $schedule['periods'] }}<span class="text-muted fs-6"> / {{ $teacher->max_weekly_classes }}</span></div></div>
            <div class="stat-icon-wrapper stat-icon-primary"><i class="bi bi-speedometer2"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Courses</div><div class="stat-val text-white">{{ $suitability->where('eligible', true)->count() }}</div></div>
            <div class="stat-icon-wrapper stat-icon-accent"><i class="bi bi-journal-bookmark"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Available</div><div class="stat-val text-white">{{ $availability['available'] }}<span class="text-muted fs-6"> / {{ $availability['teaching'] }}</span></div></div>
            <div class="stat-icon-wrapper stat-icon-success"><i class="bi bi-calendar-check"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="glass-card stat-card">
            <div><div class="stat-lbl">Preferred / Avoid</div><div class="stat-val text-white">{{ $availability['preferred'] }}<span class="text-muted fs-6"> / {{ $availability['avoid'] }}</span></div></div>
            <div class="stat-icon-wrapper stat-icon-warning"><i class="bi bi-stars"></i></div>
        </div>
    </div>
</div>

<!-- Course suitability editor -->
<div class="section-heading">
    <span class="stat-icon-wrapper stat-icon-accent" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-mortarboard"></i></span>
    <h5 class="brand-font fw-bold text-white mb-0">Courses & Suitability</h5>
</div>
<p class="text-muted" style="font-size: 0.84rem;">Tick the courses this teacher can teach. Suitability = 40% expertise + 30% teaching history + 20% preference + 10% workload; the generator prefers higher scores.</p>

<form method="POST" action="{{ route('teachers.suitability', $teacher) }}">
    @csrf
    @method('PUT')
    <div class="table-custom-container mb-3">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 60px;">Teaches</th>
                    <th>Course</th>
                    <th class="text-center">Expertise (0–100)</th>
                    <th class="text-center">Preference (0–100)</th>
                    <th class="text-center">Times Taught</th>
                    <th class="text-center">Suitability</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suitability as $row)
                    @php $id = $row['course']->id; @endphp
                    <tr>
                        <td>
                            <input type="hidden" name="courses[{{ $id }}][eligible]" value="0">
                            <input class="form-check-input" type="checkbox" name="courses[{{ $id }}][eligible]" value="1"
                                   @checked($row['eligible']) aria-label="Teaches {{ $row['course']->course_code }}">
                        </td>
                        <td>
                            <span class="fw-bold text-white brand-font">{{ $row['course']->course_code }}</span>
                            <span class="badge-custom {{ $row['course']->course_type === 'LAB' ? 'badge-lab' : 'badge-theory' }} ms-1" style="padding: 1px 7px; font-size: 0.62rem;">{{ $row['course']->course_type }}</span>
                            <div class="text-muted" style="font-size: 0.78rem;">{{ $row['course']->course_name }}</div>
                        </td>
                        <td class="text-center">
                            <input type="number" min="0" max="100" name="courses[{{ $id }}][expertise]" value="{{ $row['expertise'] ?? 50 }}"
                                   class="form-control form-control-custom matrix-input d-inline-block" aria-label="Expertise">
                        </td>
                        <td class="text-center">
                            <input type="number" min="0" max="100" name="courses[{{ $id }}][preference]" value="{{ $row['preference'] ?? 50 }}"
                                   class="form-control form-control-custom matrix-input d-inline-block" aria-label="Preference">
                        </td>
                        <td class="text-center text-muted">{{ $row['history'] }}</td>
                        <td class="text-center">
                            @if($row['score'] !== null)
                                <span class="score-chip">{{ $row['score'] }}</span>
                            @else
                                <span class="text-dim">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No courses in the catalogue yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end mb-5">
        <button class="btn-indigo"><i class="bi bi-check2"></i> Save Course Suitability</button>
    </div>
</form>

<!-- Weekly schedule -->
<div class="section-heading">
    <span class="stat-icon-wrapper stat-icon-primary" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-calendar3"></i></span>
    <h5 class="brand-font fw-bold text-white mb-0">Weekly Classes <span class="text-muted fw-normal fs-6">(published routines)</span></h5>
</div>
@if($schedule['periods'] === 0)
    <div class="glass-card text-muted mb-4">No classes in published routines yet.</div>
@else
    @include('schedules.partials.timetable', [
        'days' => $schedule['grid']['days'],
        'rows' => $schedule['grid']['rows'],
        'section' => $schedule['grid']['sections'][0],
    ])
@endif

@endsection
