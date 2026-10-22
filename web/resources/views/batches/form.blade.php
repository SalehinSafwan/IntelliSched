@extends('layouts.app')

@section('title', $batch->exists ? 'Edit Batch' : 'New Batch')

@section('content')

<x-page-header :back="$batch->exists ? route('batches.show', $batch) : route('batches.index')"
               :back-label="$batch->exists ? $batch->name : 'All Batches'" eyebrow="Academic Setup"
               :title="$batch->exists ? 'Edit ' . $batch->name : 'New Batch'" />

<div class="glass-card" style="max-width: 900px;">
    <form method="POST" action="{{ $batch->exists ? route('batches.update', $batch) : route('batches.store') }}">
        @csrf
        @if($batch->exists) @method('PUT') @endif

        <div class="form-section-title">Batch</div>
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="name">Batch Name *</label>
                <input id="name" name="name" class="form-control form-control-custom" required
                       value="{{ old('name', $batch->name) }}" placeholder="CSE 49">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="department">Department *</label>
                <input id="department" name="department" class="form-control form-control-custom" required
                       value="{{ old('department', $batch->department) }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="program">Program *</label>
                <input id="program" name="program" class="form-control form-control-custom" required
                       value="{{ old('program', $batch->program) }}" placeholder="BSc CSE">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label-custom" for="admission_year">Admission Year *</label>
                <input id="admission_year" name="admission_year" type="number" class="form-control form-control-custom" required
                       value="{{ old('admission_year', $batch->admission_year) }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label-custom" for="seniority_order">Seniority *</label>
                <input id="seniority_order" name="seniority_order" type="number" min="1" class="form-control form-control-custom" required
                       value="{{ old('seniority_order', $batch->seniority_order) }}">
                <div class="form-hint">1 = most senior (gets morning priority).</div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label-custom" for="status">Status *</label>
                <select id="status" name="status" class="form-select form-select-custom">
                    @foreach(config('intellisched.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $batch->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @unless($batch->exists)
            <div class="form-section-title mt-4">Sections</div>
            <div class="row g-4">
                <div class="col-6 col-md-4">
                    <label class="form-label-custom" for="section_count">Number of Sections *</label>
                    <input id="section_count" name="section_count" type="number" min="1" max="10" class="form-control form-control-custom" required
                           value="{{ old('section_count', 2) }}">
                    <div class="form-hint">Named A, B, C, …</div>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label-custom" for="student_count">Students per Section *</label>
                    <input id="student_count" name="student_count" type="number" min="1" class="form-control form-control-custom" required
                           value="{{ old('student_count', 60) }}">
                </div>
            </div>
        @endunless

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ $batch->exists ? route('batches.show', $batch) : route('batches.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $batch->exists ? 'Save Changes' : 'Create Batch' }}</button>
        </div>
    </form>
</div>

@endsection
