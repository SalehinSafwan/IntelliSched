@extends('layouts.app')

@section('title', 'New Exam Schedule')

@section('content')

<x-page-header :back="route('exams.index')" back-label="Exam Schedules" eyebrow="Examinations"
               title="New Exam Schedule"
               subtitle="Choose the batch. Its courses for the term are listed automatically on the next step." />

<div class="glass-card" style="max-width: 860px;">
    <form method="POST" action="{{ route('exams.store') }}">
        @csrf

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="academic_term_id">Academic Term *</label>
                <select id="academic_term_id" name="academic_term_id" class="form-select form-select-custom" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected(old('academic_term_id', $current?->id) == $term->id)>{{ $term->name }}{{ $term->status === 'ACTIVE' ? ' (current)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom">Exam *</label>
                <div class="d-flex gap-2">
                    @foreach(config('intellisched.exam_types') as $value => $label)
                        <label class="choice-card flex-fill">
                            <input type="radio" name="exam_type" value="{{ $value }}" @checked(old('exam_type', 'MIDTERM') === $value)>
                            <span class="choice-body text-center"><span class="choice-title">{{ $label }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-section-title mt-4">Batch</div>
        <div class="choice-grid">
            @forelse($batches as $batch)
                <label class="choice-card">
                    <input type="radio" name="batch_id" value="{{ $batch->id }}" required @checked(old('batch_id') == $batch->id)>
                    <span class="choice-body">
                        <span class="choice-title">{{ $batch->name }}</span>
                        <span class="choice-sub">{{ $batch->program }} · Seniority {{ $batch->seniority_order }}</span>
                    </span>
                </label>
            @empty
                <p class="text-muted">No active batches.</p>
            @endforelse
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('exams.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-arrow-right"></i> Continue</button>
        </div>
    </form>
</div>

@endsection
