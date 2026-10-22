@extends('layouts.app')

@section('title', 'Routine Preview')

@section('content')

@php
    $validation = $result['validation'] ?? ['is_valid' => false, 'errors' => []];
@endphp

<!-- Header -->
<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <a href="{{ route('schedules.create') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Generator</a>
        <div class="page-eyebrow mt-3">Draft preview · not saved yet</div>
        <h2 class="brand-font fw-bold text-gradient mt-1 mb-1">{{ $batch->name }} · {{ $term->name }}</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">
            Generated {{ \Carbon\Carbon::parse($preview['generated_at'])->format('d M Y, h:i A') }}.
            Review the routine below and approve it to publish. This preview expires after {{ $expiresInMinutes / 60 }} hours.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <form method="POST" action="{{ route('schedules.generate') }}" class="js-busy-form">
            @csrf
            <input type="hidden" name="batch_id" value="{{ $preview['batch_id'] }}">
            <input type="hidden" name="academic_term_id" value="{{ $preview['academic_term_id'] }}">
            <button type="submit" class="btn-glass">
                <i class="bi bi-arrow-repeat"></i> Regenerate
            </button>
        </form>
        @include('schedules.partials.approve-button')
    </div>
</div>

<!-- Solver and validation summary -->
<div class="glass-card mb-4">
    <div class="row g-3">
        <div class="col-6 col-xl-3">
            <div class="metric-tile">
                <div class="metric-label"><i class="bi bi-cpu me-1"></i> Solver status</div>
                <div class="metric-value">{{ $result['status'] }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile">
                <div class="metric-label"><i class="bi bi-calendar-check me-1"></i> Sessions scheduled</div>
                <div class="metric-value">{{ $result['scheduled_sessions'] }} <span class="text-muted fs-6">/ {{ $result['total_sessions'] }}</span></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile">
                <div class="metric-label"><i class="bi bi-graph-up-arrow me-1"></i> Objective value</div>
                <div class="metric-value">{{ number_format($result['objective_value']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-tile d-flex flex-column justify-content-center">
                <div class="metric-label mb-2"><i class="bi bi-shield me-1"></i> Independent validation</div>
                @if($validation['is_valid'])
                    <span class="badge-custom badge-active badge-dot">All hard constraints satisfied</span>
                @else
                    <span class="badge-custom badge-inactive badge-dot">{{ count($validation['errors']) }} violation(s)</span>
                @endif
            </div>
        </div>
    </div>

    @if(!$validation['is_valid'])
        <ul class="mt-3 mb-0 ps-4" style="color: #fca5a5; font-size: 0.85rem;">
            @foreach($validation['errors'] as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
</div>

@if(!empty($result['quality']))
    @php
        $q = $result['quality'];
        $total = max(1, $q['total_periods']);
        $metrics = [
            ['Morning periods', $q['morning_periods'] . ' / ' . $q['total_periods'], 'bi-sunrise', 'Senior batches are steered to mornings', true],
            ['Single-class days', $q['single_period_days'], 'bi-1-circle', 'Days a section comes in for only one period', $q['single_period_days'] === 0],
            ['Student gaps', $q['student_gaps'], 'bi-distribute-vertical', 'Empty periods between classes', $q['student_gaps'] === 0],
            ['Teacher gaps', $q['teacher_gaps'], 'bi-person-dash', 'Idle periods inside a teacher\'s day', $q['teacher_gaps'] === 0],
            ['Daily limit overruns', $q['daily_theory_excess'] + $q['daily_period_excess'], 'bi-speedometer', 'Above 4 theory classes or 6 periods a day', ($q['daily_theory_excess'] + $q['daily_period_excess']) === 0],
            ['Preferred / avoided periods', $q['preferred_periods'] . ' / ' . $q['avoided_periods'], 'bi-stars', 'Teacher time preferences honoured', $q['avoided_periods'] === 0],
        ];
    @endphp
    <div class="glass-card mb-4">
        <div class="section-heading">
            <span class="stat-icon-wrapper stat-icon-success" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-clipboard-data"></i></span>
            <h5 class="brand-font fw-bold text-white mb-0">Soft-Constraint Scorecard</h5>
        </div>
        <div class="row g-3">
            @foreach($metrics as [$label, $value, $icon, $hint, $good])
                <div class="col-6 col-lg-4 col-xxl-2">
                    <div class="metric-tile" title="{{ $hint }}">
                        <div class="metric-label"><i class="bi {{ $icon }} me-1"></i> {{ $label }}</div>
                        <div class="metric-value" style="color: {{ $good ? '#6ee7b7' : '#fcd34d' }};">{{ $value }}</div>
                        <div class="form-hint mt-1">{{ $hint }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- Course teacher pairs -->
    <div class="col-12 col-xl-6">
        <div class="section-heading">
            <span class="stat-icon-wrapper stat-icon-accent" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-people-fill"></i></span>
            <h5 class="brand-font fw-bold text-white mb-0">Course Teacher Pairs</h5>
        </div>
        <div class="table-custom-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Teacher pair (all sections)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(collect($result['course_teacher_pairs'])->sortBy('course_code') as $pair)
                        <tr>
                            <td><span class="badge-custom badge-theory">{{ $pair['course_code'] }}</span></td>
                            <td class="fw-semibold">
                                {{ collect($pair['teacher_ids'])->map(fn ($id) => $teacherNames[$id] ?? "Teacher {$id}")->implode(' & ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Teacher workload -->
    <div class="col-12 col-xl-6">
        <div class="section-heading">
            <span class="stat-icon-wrapper stat-icon-warning" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-speedometer2"></i></span>
            <h5 class="brand-font fw-bold text-white mb-0">Teacher Workload <span class="text-muted fw-normal fs-6">(periods / week)</span></h5>
        </div>
        <div class="table-custom-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th class="text-end">Existing</th>
                        <th class="text-end">New</th>
                        <th style="width: 34%;">Total / Max</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($result['teacher_workload_periods'] as $load)
                        @php
                            $percent = $load['maximum_periods'] > 0
                                ? min(100, round($load['total_periods'] / $load['maximum_periods'] * 100))
                                : 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold text-white">{{ $teacherNames[$load['teacher_id']] ?? $load['teacher_code'] }}</div>
                                <div class="text-dim" style="font-size: 0.74rem;">{{ $load['teacher_code'] }}</div>
                            </td>
                            <td class="text-end">{{ $load['existing_periods'] }}</td>
                            <td class="text-end">{{ $load['new_periods'] }}</td>
                            <td>
                                <div class="fw-bold text-white" style="font-size: 0.85rem;">{{ $load['total_periods'] }} / {{ $load['maximum_periods'] }}</div>
                                <div class="load-bar {{ $percent >= 85 ? 'is-high' : '' }}"><span style="width: {{ $percent }}%;"></span></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('schedules.partials.section-timetables', ['grid' => $grid])

<div class="glass-card d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2 no-print">
    <div>
        <div class="fw-bold text-white brand-font">Ready to publish?</div>
        <div class="text-muted" style="font-size: 0.85rem;">
            Approving re-validates the routine against current data, saves it to Oracle as PUBLISHED and archives the previous version.
        </div>
    </div>
    @include('schedules.partials.approve-button')
</div>

@push('scripts')
<script>
    // Prevent double submission of approve / regenerate.
    document.querySelectorAll('.js-busy-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            document.querySelectorAll('.js-busy-form button').forEach(function (button) {
                button.disabled = true;
            });
            const button = form.querySelector('button');
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Working…';
        });
    });
</script>
@endpush

@endsection
