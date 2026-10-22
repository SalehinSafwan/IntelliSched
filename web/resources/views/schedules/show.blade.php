@extends('layouts.app')

@section('title', 'Routine — ' . $run->batch_name)

@section('content')

@php
    $statusBadge = match ($run->status) {
        'PUBLISHED' => 'badge-active',
        'ARCHIVED' => 'badge-archived',
        default => 'badge-role',
    };
@endphp

<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <a href="{{ route('schedules.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> All Routines</a>
        <div class="d-flex align-items-center gap-2 mt-3">
            <span class="page-eyebrow">Version {{ $run->version }}</span>
            <span class="badge-custom {{ $statusBadge }} badge-dot">{{ $run->status }}</span>
        </div>
        <h2 class="brand-font fw-bold text-gradient mt-1 mb-1">{{ $run->batch_name }} · {{ $run->term_name }}</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">
            <i class="bi bi-person-check me-1"></i> Approved by {{ $run->generated_by_name ?? 'Unknown user' }}
            @if($run->generated_at)
                <span class="mx-1">·</span><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($run->generated_at)->format('d M Y, h:i A') }}
            @endif
            <span class="mx-1">·</span><i class="bi bi-grid-3x3 me-1"></i>{{ $periodCount }} saved period entries
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <button class="btn-glass" onclick="window.print();">
            <i class="bi bi-printer"></i> Print
        </button>
        <x-export-pdf-button :href="route('export.routine', $run->id)" />
        @if($canManage)
            <a href="{{ route('schedules.create') }}" class="btn-indigo">
                <i class="bi bi-arrow-repeat"></i> Regenerate Routine
            </a>
        @endif
    </div>
</div>

<div class="section-heading">
    <span class="stat-icon-wrapper stat-icon-accent" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 10px;"><i class="bi bi-people-fill"></i></span>
    <h5 class="brand-font fw-bold text-white mb-0">Course Teacher Pairs</h5>
</div>
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Course</th>
                <th>Title</th>
                <th>Teacher pair (all sections)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coursePairs as $pair)
                <tr>
                    <td><span class="badge-custom badge-theory">{{ $pair['course_code'] }}</span></td>
                    <td class="text-white">{{ $pair['course_name'] }}</td>
                    <td class="fw-semibold">{{ implode(' & ', $pair['teachers']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('schedules.partials.section-timetables', ['grid' => $grid])

@endsection
