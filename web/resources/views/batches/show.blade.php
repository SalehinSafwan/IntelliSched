@extends('layouts.app')

@section('title', $batch->name)

@section('content')

<x-page-header :back="route('batches.index')" back-label="All Batches"
               :eyebrow="$batch->program . ' · Seniority ' . $batch->seniority_order"
               :title="$batch->name"
               :subtitle="$batch->department . ' · Admitted ' . $batch->admission_year">
    <x-status-badge :status="$batch->status" />
    <a href="{{ route('batches.edit', $batch) }}" class="btn-glass"><i class="bi bi-pencil"></i> Edit Batch</a>
</x-page-header>

<div class="row g-4 mb-4">
    <!-- Sections -->
    <div class="col-12 col-xl-5">
        <div class="glass-card h-100">
            <div class="section-heading">
                <span class="stat-icon-wrapper stat-icon-primary" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-diagram-3"></i></span>
                <h5 class="brand-font fw-bold text-white mb-0">Sections</h5>
            </div>

            @foreach($batch->sections as $section)
                <form method="POST" action="{{ route('sections.update', $section) }}" class="row g-2 align-items-center py-2 border-bottom border-secondary">
                    @csrf
                    @method('PUT')
                    <div class="col-3">
                        <input name="name" class="form-control form-control-custom matrix-input w-100" value="{{ $section->name }}" aria-label="Section name">
                    </div>
                    <div class="col-3">
                        <input name="student_count" type="number" min="1" class="form-control form-control-custom matrix-input w-100" value="{{ $section->student_count }}" aria-label="Students">
                    </div>
                    <div class="col-3">
                        <select name="status" class="form-select form-select-custom" style="padding: 6px 30px 6px 10px !important; font-size: 0.8rem !important;" aria-label="Status">
                            @foreach(config('intellisched.statuses') as $value => $label)
                                <option value="{{ $value }}" @selected($section->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3 text-end">
                        <button class="btn-glass btn-sm-custom" title="Save section"><i class="bi bi-check2"></i></button>
                    </div>
                </form>
                <div class="text-end mb-1">
                    <x-delete-button :action="route('sections.destroy', $section)" label="Remove {{ $section->name }}"
                                     :confirm="'Remove section ' . $section->name . ' and its course offerings?'" />
                </div>
            @endforeach

            <form method="POST" action="{{ route('sections.store', $batch) }}" class="row g-2 align-items-end mt-3">
                @csrf
                <div class="col-4">
                    <label class="form-label-custom" for="new_section_name">New section</label>
                    <input id="new_section_name" name="name" class="form-control form-control-custom" placeholder="C" required>
                </div>
                <div class="col-4">
                    <label class="form-label-custom" for="new_section_students">Students</label>
                    <input id="new_section_students" name="student_count" type="number" min="1" class="form-control form-control-custom" value="60" required>
                </div>
                <div class="col-4">
                    <button class="btn-indigo w-100"><i class="bi bi-plus-lg"></i> Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Course list for a term -->
    <div class="col-12 col-xl-7">
        <div class="glass-card h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-heading mb-0">
                    <span class="stat-icon-wrapper stat-icon-accent" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-journal-bookmark"></i></span>
                    <h5 class="brand-font fw-bold text-white mb-0">Courses this Term</h5>
                </div>
                @if($terms->isNotEmpty())
                    <form method="GET" action="{{ route('batches.show', $batch) }}">
                        <select name="term" class="form-select form-select-custom" onchange="this.form.submit()" style="padding: 8px 36px 8px 12px !important;" aria-label="Term">
                            @foreach($terms as $option)
                                <option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->name }}{{ $option->status === 'ACTIVE' ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if(!$term)
                <p class="text-muted">Create an academic term first.</p>
            @elseif($courses->isEmpty())
                <p class="text-muted">No courses in the catalogue yet. <a href="{{ route('courses.create') }}">Add one</a>.</p>
            @else
                <p class="text-muted" style="font-size: 0.84rem;">Every section of {{ $batch->name }} studies the selected courses in {{ $term->name }}. These are scheduled by the routine generator and listed for exams.</p>
                <form method="POST" action="{{ route('batches.offerings', $batch) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="academic_term_id" value="{{ $term->id }}">
                    <div class="choice-grid mb-3">
                        @foreach($courses as $course)
                            <label class="choice-card">
                                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}"
                                       @checked(in_array((int) $course->id, $offeredCourseIds, true))
                                       @disabled($course->status !== 'ACTIVE' && !in_array((int) $course->id, $offeredCourseIds, true))>
                                <span class="choice-body">
                                    <span class="choice-title">{{ $course->course_code }}
                                        <span class="badge-custom {{ $course->course_type === 'LAB' ? 'badge-lab' : 'badge-theory' }} ms-1" style="padding: 1px 7px; font-size: 0.62rem;">{{ $course->course_type }}</span>
                                    </span>
                                    <span class="choice-sub">{{ $course->course_name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-dim" style="font-size: 0.8rem;">{{ count($offeredCourseIds) }} selected</span>
                        <button class="btn-indigo"><i class="bi bi-check2"></i> Save Course List</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <h5 class="brand-font fw-bold text-white mb-3">Routines</h5>
        <div class="table-custom-container">
            <table class="table-custom">
                <thead><tr><th>Term</th><th>Version</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->term_name }}</td>
                            <td><span class="badge-custom badge-gold">v{{ $run->version }}</span></td>
                            <td><x-status-badge :status="$run->status" /></td>
                            <td class="text-end"><a href="{{ route('schedules.show', $run->id) }}" class="btn-glass btn-sm-custom"><i class="bi bi-eye"></i> View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-4">No routines yet. <a href="{{ route('schedules.create') }}">Generate one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <h5 class="brand-font fw-bold text-white mb-3">Exam Schedules</h5>
        <div class="table-custom-container">
            <table class="table-custom">
                <thead><tr><th>Term</th><th>Exam</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($examSchedules as $exam)
                        <tr>
                            <td>{{ $exam->academicTerm?->name }}</td>
                            <td>{{ config('intellisched.exam_types')[$exam->exam_type] ?? $exam->exam_type }}</td>
                            <td><x-status-badge :status="$exam->status" /></td>
                            <td class="text-end"><a href="{{ route('exams.show', $exam) }}" class="btn-glass btn-sm-custom"><i class="bi bi-eye"></i> View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-4">No exam schedules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
