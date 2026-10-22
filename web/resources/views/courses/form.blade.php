@extends('layouts.app')

@section('title', $course->exists ? 'Edit Course' : 'New Course')

@section('content')

<x-page-header :back="$course->exists ? route('courses.show', $course) : route('courses.index')"
               :back-label="$course->exists ? $course->course_code : 'All Courses'" eyebrow="Academic Setup"
               :title="$course->exists ? 'Edit ' . $course->course_code : 'New Course'" />

<div class="glass-card" style="max-width: 900px;">
    <form method="POST" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}">
        @csrf
        @if($course->exists) @method('PUT') @endif

        <div class="form-section-title">Identity</div>
        <div class="row g-4 mb-2">
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="course_code">Course Code *</label>
                <input id="course_code" name="course_code" class="form-control form-control-custom" required
                       value="{{ old('course_code', $course->course_code) }}" placeholder="CSE-3201">
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label-custom" for="course_name">Course Title *</label>
                <input id="course_name" name="course_name" class="form-control form-control-custom" required
                       value="{{ old('course_name', $course->course_name) }}" placeholder="Database Systems">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="department">Department *</label>
                <input id="department" name="department" class="form-control form-control-custom" required
                       value="{{ old('department', $course->department ?? 'CSE') }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label-custom" for="credit_hours">Credit Hours *</label>
                <input id="credit_hours" name="credit_hours" type="number" step="0.25" min="0" class="form-control form-control-custom" required
                       value="{{ old('credit_hours', $course->credit_hours) }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label-custom" for="status">Status *</label>
                <select id="status" name="status" class="form-select form-select-custom">
                    @foreach(config('intellisched.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $course->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-section-title mt-4">Weekly teaching pattern</div>
        <div class="choice-grid mb-3" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
            @foreach(config('intellisched.course_types') as $value => $label)
                <label class="choice-card">
                    <input type="radio" name="course_type" value="{{ $value }}" class="js-course-type"
                           @checked(old('course_type', $course->course_type) === $value)>
                    <span class="choice-body">
                        <span class="choice-title">
                            <i class="bi {{ $value === 'LAB' ? 'bi-pc-display' : 'bi-easel2' }} me-1"></i> {{ $label }}
                        </span>
                        <span class="choice-sub">
                            {{ $value === 'LAB'
                                ? '3-period lab sessions, conducted jointly by the teacher pair'
                                : config('intellisched.weekly_theory_classes') . ' classes a week: one per pair teacher + one combined' }}
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="row g-4" id="labCountRow">
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="weekly_lab_classes">Lab sessions per week</label>
                <input id="weekly_lab_classes" name="weekly_lab_classes" type="number" min="1" max="5" class="form-control form-control-custom"
                       value="{{ old('weekly_lab_classes', $course->weekly_lab_classes ?: 1) }}">
                <div class="form-hint">Each session occupies 3 consecutive periods.</div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ $course->exists ? route('courses.show', $course) : route('courses.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $course->exists ? 'Save Changes' : 'Create Course' }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Lab session count only applies to lab courses.
    function syncLabRow() {
        const lab = document.querySelector('.js-course-type[value="LAB"]').checked;
        document.getElementById('labCountRow').hidden = !lab;
    }
    document.querySelectorAll('.js-course-type').forEach(el => el.addEventListener('change', syncLabRow));
    syncLabRow();
</script>
@endpush

@endsection
