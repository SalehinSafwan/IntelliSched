@extends('layouts.app')

@section('title', 'Routines')

@section('content')

@php
    $publishedCount = $runs->where('status', 'PUBLISHED')->count();
    $batchCount = $runs->pluck('batch_id')->unique()->count();
@endphp

<div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-4">
    <div>
        <div class="page-eyebrow">Scheduling Engine · CP-SAT</div>
        <h2 class="brand-font fw-bold text-gradient mt-1 mb-1">Academic Timetable Routines</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">
            Approved routines saved in Oracle. A batch's PUBLISHED version reserves its teachers and rooms for other batches.
        </p>
    </div>
    @if($canManage)
        <a href="{{ route('schedules.create') }}" class="btn-indigo">
            <i class="bi bi-stars"></i> Generate New Routine
        </a>
    @endif
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="glass-card card-hoverable stat-card">
            <div>
                <div class="stat-lbl">Saved Routines</div>
                <div class="stat-val text-white">{{ $runs->count() }}</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary"><i class="bi bi-collection-fill"></i></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="glass-card card-hoverable stat-card">
            <div>
                <div class="stat-lbl">Published</div>
                <div class="stat-val text-white">{{ $publishedCount }}</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-success"><i class="bi bi-patch-check-fill"></i></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="glass-card card-hoverable stat-card">
            <div>
                <div class="stat-lbl">Batches Covered</div>
                <div class="stat-val text-white">{{ $batchCount }}</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-accent"><i class="bi bi-mortarboard-fill"></i></div>
        </div>
    </div>
</div>

@if($runs->isEmpty())
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-calendar2-week"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No routines saved yet</h4>
        @if($canManage)
            <p class="text-muted mb-4" style="font-size: 0.9rem;">Generate a routine, review the preview, and approve it to see it here.</p>
            <a href="{{ route('schedules.create') }}" class="btn-indigo"><i class="bi bi-stars"></i> Generate Routine</a>
        @else
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Routines appear here once the coordinator publishes them.</p>
        @endif
    </div>
@else
    <div class="table-custom-container mb-4">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Term</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th class="text-end">Period Entries</th>
                    <th>Approved By</th>
                    <th>Approved At</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($runs as $run)
                    @php
                        $statusBadge = match ($run->status) {
                            'PUBLISHED' => 'badge-active',
                            'ARCHIVED' => 'badge-archived',
                            default => 'badge-role',
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold text-white brand-font">{{ $run->batch_name }}</td>
                        <td>{{ $run->term_name }}</td>
                        <td><span class="badge-custom badge-gold">v{{ $run->version }}</span></td>
                        <td><span class="badge-custom {{ $statusBadge }} badge-dot">{{ $run->status }}</span></td>
                        <td class="text-end">{{ $run->period_entry_count }}</td>
                        <td>{{ $run->generated_by_name ?? '—' }}</td>
                        <td class="text-muted">{{ $run->generated_at ? \Carbon\Carbon::parse($run->generated_at)->format('d M Y, h:i A') : '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('schedules.show', $run->id) }}" class="btn-glass btn-sm-custom">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
