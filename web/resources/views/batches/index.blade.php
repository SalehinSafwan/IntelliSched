@extends('layouts.app')

@section('title', 'Batches & Sections')

@section('content')

<x-page-header eyebrow="Academic Setup" title="Batches & Sections"
               subtitle="Seniority 1 is the most senior batch; senior batches are preferred for morning classes.">
    <x-export-pdf-button :href="route('export.batches')" />
    <a href="{{ route('batches.create') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> New Batch</a>
</x-page-header>

<div class="row g-4 mb-4">
    @forelse($batches as $batch)
        @php
            $students = $batch->sections->sum(fn ($s) => (int) $s->student_count);
            $offered = (int) ($courseCounts[$batch->id] ?? 0);
        @endphp
        <div class="col-12 col-md-6 col-xxl-4">
            <div class="glass-card card-hoverable h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge-custom badge-gold mb-2">Seniority {{ $batch->seniority_order }}</span>
                        <a href="{{ route('batches.show', $batch) }}" class="d-block brand-font fw-bold text-white fs-4 text-decoration-none">{{ $batch->name }}</a>
                        <div class="text-muted" style="font-size: 0.84rem;">{{ $batch->program }} · Admitted {{ $batch->admission_year }}</div>
                    </div>
                    <x-status-badge :status="$batch->status" />
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4"><div class="metric-tile"><div class="metric-label">Sections</div><div class="metric-value">{{ $batch->sections->count() }}</div></div></div>
                    <div class="col-4"><div class="metric-tile"><div class="metric-label">Students</div><div class="metric-value">{{ $students }}</div></div></div>
                    <div class="col-4"><div class="metric-tile"><div class="metric-label">Courses</div><div class="metric-value">{{ $offered }}</div></div></div>
                </div>

                <div class="mb-3">
                    @foreach($batch->sections->sortBy('name') as $section)
                        <span class="badge-custom badge-theory me-1 mb-1">Section {{ $section->name }} · {{ $section->student_count }}</span>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a href="{{ route('batches.show', $batch) }}" class="btn-glass btn-sm-custom"><i class="bi bi-sliders"></i> Manage</a>
                    @if(isset($published[$batch->id]))
                        <a href="{{ route('schedules.show', $published[$batch->id]) }}" class="btn-glass btn-sm-custom"><i class="bi bi-calendar3"></i> Routine</a>
                    @endif
                    <x-delete-button :action="route('batches.destroy', $batch)"
                                     :confirm="'Delete batch ' . $batch->name . ' with all its sections and course lists?'" />
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="glass-card text-center py-5">
                <div class="empty-state-icon"><i class="bi bi-mortarboard"></i></div>
                <h4 class="brand-font fw-bold text-white mt-4">No batches yet</h4>
                <a href="{{ route('batches.create') }}" class="btn-indigo mt-2"><i class="bi bi-plus-lg"></i> Create a batch</a>
            </div>
        </div>
    @endforelse
</div>

@if($current)
    <p class="text-dim" style="font-size: 0.8rem;"><i class="bi bi-info-circle me-1"></i> Course counts are for the current term, {{ $current->name }}.</p>
@endif

@endsection
