@extends('layouts.app')

@section('title', $term->exists ? 'Edit Term' : 'New Term')

@section('content')

<x-page-header :back="route('terms.index')" back-label="All Terms" eyebrow="Academic Setup"
               :title="$term->exists ? 'Edit ' . $term->name : 'New Academic Term'" />

<div class="glass-card" style="max-width: 820px;">
    <form method="POST" action="{{ $term->exists ? route('terms.update', $term) : route('terms.store') }}">
        @csrf
        @if($term->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="name">Name *</label>
                <input id="name" name="name" class="form-control form-control-custom" required
                       value="{{ old('name', $term->name) }}" placeholder="e.g. Fall 2026">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label-custom" for="academic_year">Academic Year *</label>
                <input id="academic_year" name="academic_year" class="form-control form-control-custom" required
                       value="{{ old('academic_year', $term->academic_year) }}" placeholder="2026">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label-custom" for="term">Term *</label>
                <input id="term" name="term" class="form-control form-control-custom" required
                       value="{{ old('term', $term->term) }}" placeholder="FALL">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="start_date">Start Date *</label>
                <input id="start_date" name="start_date" type="date" class="form-control form-control-custom date-input" required
                       value="{{ old('start_date', $term->start_date ? \Carbon\Carbon::parse($term->start_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="end_date">End Date *</label>
                <input id="end_date" name="end_date" type="date" class="form-control form-control-custom date-input" required
                       value="{{ old('end_date', $term->end_date ? \Carbon\Carbon::parse($term->end_date)->format('Y-m-d') : '') }}">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('terms.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $term->exists ? 'Save Changes' : 'Create Term' }}</button>
        </div>
    </form>
</div>

@endsection
