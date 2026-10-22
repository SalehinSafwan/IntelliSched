@extends('layouts.app')

@section('title', 'Edit Exam Schedule')

@section('content')

@php
    $selectedRooms = old('room_ids', $exam->rooms->pluck('id')->all());
    $termStart = \Carbon\Carbon::parse($exam->academicTerm->start_date)->format('Y-m-d');
    $termEnd = \Carbon\Carbon::parse($exam->academicTerm->end_date)->format('Y-m-d');
    $conflicts = session('exam_conflicts', []);
@endphp

<x-page-header :back="route('exams.index')" back-label="Exam Schedules"
               :eyebrow="(config('intellisched.exam_types')[$exam->exam_type] ?? $exam->exam_type) . ' · ' . $exam->academicTerm->name . ' · Draft'"
               :title="$exam->batch->name . ' Exam Schedule'"
               :subtitle="$students . ' students · ' . $exam->courses->count() . ' courses'">
    <x-delete-button :action="route('exams.destroy', $exam)" label="Delete" :small="false"
                     confirm="Delete this exam schedule?" />
</x-page-header>

@if($conflicts)
    <div class="glass-card mb-4" style="border-color: rgba(239, 68, 68, 0.4);">
        <h6 class="fw-bold mb-3" style="color: #fca5a5;"><i class="bi bi-exclamation-octagon me-1"></i> Fix these before publishing</h6>
        <ul class="conflict-list">
            @foreach($conflicts as $conflict)
                <li>{{ $conflict }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('exams.update', $exam) }}">
    @csrf
    @method('PUT')

    <div class="row g-4 mb-4">
        <!-- Time window -->
        <div class="col-12 col-xl-4">
            <div class="glass-card h-100">
                <div class="section-heading">
                    <span class="stat-icon-wrapper stat-icon-primary" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-clock"></i></span>
                    <h5 class="brand-font fw-bold text-white mb-0">Exam Time</h5>
                </div>
                <p class="text-muted" style="font-size: 0.82rem;">All exams of this schedule take place in the same window.</p>
                <div class="d-grid gap-2">
                    @foreach(config('intellisched.exam_sessions') as $value => $session)
                        <label class="choice-card">
                            <input type="radio" name="session" value="{{ $value }}" @checked(old('session', $exam->session) === $value)>
                            <span class="choice-body">
                                <span class="choice-title"><i class="bi {{ $value === 'MORNING' ? 'bi-sunrise' : 'bi-sun' }} me-1"></i> {{ ucfirst(strtolower($value)) }}</span>
                                <span class="choice-sub">{{ $session['label'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Rooms -->
        <div class="col-12 col-xl-8">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="section-heading mb-0">
                        <span class="stat-icon-wrapper stat-icon-accent" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-door-open"></i></span>
                        <h5 class="brand-font fw-bold text-white mb-0">Exam Rooms</h5>
                    </div>
                    <span class="score-chip" id="seatCounter">0 / {{ $students }} seats</span>
                </div>
                <p class="text-muted" style="font-size: 0.82rem;">Used for every exam of this schedule. While an exam runs, no class or other exam may use these rooms.</p>
                <div class="choice-grid" style="grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));">
                    @foreach($rooms as $room)
                        <label class="choice-card">
                            <input type="checkbox" name="room_ids[]" value="{{ $room->id }}" class="js-room" data-capacity="{{ $room->capacity }}"
                                   @checked(in_array($room->id, array_map('intval', $selectedRooms)))>
                            <span class="choice-body">
                                <span class="choice-title">{{ $room->room_code }}</span>
                                <span class="choice-sub">{{ $room->capacity }} seats · {{ ucfirst(strtolower($room->room_type)) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Dates -->
    <div class="glass-card mb-4">
        <div class="section-heading">
            <span class="stat-icon-wrapper stat-icon-success" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-calendar-event"></i></span>
            <h5 class="brand-font fw-bold text-white mb-0">Exam Dates</h5>
        </div>
        <p class="text-muted" style="font-size: 0.82rem;">Courses {{ $exam->batch->name }} studies in {{ $exam->academicTerm->name }}. Pick one date per course; the batch sits at most one exam per day.</p>

        @foreach($exam->courses->sortBy(fn ($c) => $c->course?->course_code) as $examCourse)
            <div class="exam-course-row">
                <div>
                    <span class="fw-bold text-white brand-font">{{ $examCourse->course?->course_code }}</span>
                    <span class="badge-custom {{ $examCourse->course?->course_type === 'LAB' ? 'badge-lab' : 'badge-theory' }} ms-1" style="padding: 1px 7px; font-size: 0.62rem;">{{ $examCourse->course?->course_type }}</span>
                    <div class="text-muted" style="font-size: 0.82rem;">{{ $examCourse->course?->course_name }}</div>
                </div>
                <div>
                    <input type="date" name="dates[{{ $examCourse->id }}]" class="form-control form-control-custom date-input js-date"
                           min="{{ $termStart }}" max="{{ $termEnd }}" aria-label="Exam date for {{ $examCourse->course?->course_code }}"
                           value="{{ old('dates.' . $examCourse->id, $examCourse->exam_date?->format('Y-m-d')) }}">
                    <div class="form-hint js-weekday"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="glass-card d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2">
        <div class="text-muted" style="font-size: 0.85rem;">
            Publishing checks room capacity, clashes with other published exams, and classes in published routines that use these rooms during the exam time.
        </div>
        <div class="d-flex gap-2">
            <button name="action" value="save" class="btn-glass"><i class="bi bi-save"></i> Save Draft</button>
            <button name="action" value="publish" class="btn-indigo btn-success-glow"><i class="bi bi-megaphone"></i> Save & Publish</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    // Live seat total for the selected rooms.
    const needed = {{ (int) $students }};
    function updateSeats() {
        let seats = 0;
        document.querySelectorAll('.js-room:checked').forEach(el => seats += parseInt(el.dataset.capacity, 10));
        const counter = document.getElementById('seatCounter');
        counter.textContent = seats + ' / ' + needed + ' seats';
        counter.style.color = seats >= needed ? '#6ee7b7' : '#fca5a5';
    }
    document.querySelectorAll('.js-room').forEach(el => el.addEventListener('change', updateSeats));
    updateSeats();

    // Show the weekday under each date.
    function updateWeekday(input) {
        const hint = input.parentElement.querySelector('.js-weekday');
        hint.textContent = input.value
            ? new Date(input.value + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'long' })
            : 'No date yet';
    }
    document.querySelectorAll('.js-date').forEach(function (input) {
        input.addEventListener('change', () => updateWeekday(input));
        updateWeekday(input);
    });
</script>
@endpush

@endsection
