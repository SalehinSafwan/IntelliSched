@extends('layouts.app')

@section('title', 'System Audit Logs')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">System Audit & Activity Logs</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Complete immutable log history of user actions, schedule generations, and database modifications.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-glass" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Logs
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="glass-card mb-4 p-3">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
                <input type="text" class="form-control form-control-custom" placeholder="Search by user, action, or entity..." style="padding-left: 40px !important;">
            </div>
        </div>
        <div class="col-12 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Action Types</option>
                <option value="ROUTINE">Routine Generation</option>
                <option value="COURSE">Course Modifications</option>
                <option value="PREFERENCE">Preference Changes</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <input type="date" class="form-control form-control-custom" value="2026-10-06">
        </div>
    </div>
</div>

<!-- Audit Log Table -->
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User / Initiator</th>
                <th>Role</th>
                <th>Action Description</th>
                <th>Target Entity</th>
                <th class="text-end">Details</th>
            </tr>
        </thead>
        <tbody>
            @php
                $logs = [
                    [
                        'time' => '06 Oct 2026 · 08:32 PM',
                        'user' => 'System Administrator',
                        'role' => 'ADMIN',
                        'action' => 'Generated Routine',
                        'entity' => 'CSE 47 Routine (Run #5)',
                        'prev'   => 'Schedule Run #4',
                        'new'    => 'Schedule Run #5 (Score: 91.4%)'
                    ],
                    [
                        'time' => '06 Oct 2026 · 07:15 PM',
                        'user' => 'Dr. Ahmed Rahman',
                        'role' => 'TEACHER',
                        'action' => 'Updated Teacher Preference',
                        'entity' => 'Teacher T001 Preferences',
                        'prev'   => 'Database Systems Score: 80',
                        'new'    => 'Database Systems Score: 90'
                    ],
                    [
                        'time' => '05 Oct 2026 · 04:20 PM',
                        'user' => 'Academic Coordinator',
                        'role' => 'COORDINATOR',
                        'action' => 'Changed Room Allocation',
                        'entity' => 'LAB-01 (Database Lab)',
                        'prev'   => 'Capacity: 25',
                        'new'    => 'Capacity: 30'
                    ],
                    [
                        'time' => '05 Oct 2026 · 02:10 PM',
                        'user' => 'System Administrator',
                        'role' => 'ADMIN',
                        'action' => 'Updated Course Specification',
                        'entity' => 'CSE3202 (Database Lab)',
                        'prev'   => 'Credit: 1.0',
                        'new'    => 'Credit: 1.5'
                    ],
                ];
            @endphp

            @foreach($logs as $idx => $l)
                <tr>
                    <td class="font-monospace text-muted" style="font-size: 0.82rem;">{{ $l['time'] }}</td>
                    <td class="fw-semibold text-white">{{ $l['user'] }}</td>
                    <td>
                        @if($l['role'] === 'ADMIN')
                            <span class="badge bg-danger" style="font-size: 0.7rem;">ADMIN</span>
                        @elseif($l['role'] === 'COORDINATOR')
                            <span class="badge bg-primary" style="font-size: 0.7rem;">COORDINATOR</span>
                        @else
                            <span class="badge bg-success" style="font-size: 0.7rem;">TEACHER</span>
                        @endif
                    </td>
                    <td class="fw-semibold text-indigo">{{ $l['action'] }}</td>
                    <td class="text-white">{{ $l['entity'] }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-glass py-1 px-3" style="font-size: 0.8rem;" 
                                onclick="openAuditModal('{{ $l['user'] }}', '{{ $l['action'] }}', '{{ $l['time'] }}', '{{ $l['entity'] }}', '{{ $l['prev'] }}', '{{ $l['new'] }}')">
                            <i class="bi bi-eye me-1"></i> Inspect Diff
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Audit Details Modal (Phase 17) -->
<x-modal id="auditModal" title="Audit Action Details">
    <div class="d-flex flex-column gap-3" style="font-size: 0.88rem;">
        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">User:</span>
            <span class="fw-bold text-white" id="auditUser">Admin</span>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Action Executed:</span>
            <span class="fw-bold text-indigo" id="auditAction">Schedule Regenerated</span>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Timestamp:</span>
            <span class="font-monospace text-white" id="auditTime">06 Oct 2026 08:32 PM</span>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Target Entity:</span>
            <span class="fw-bold text-white" id="auditEntity">CSE 47 Routine</span>
        </div>

        <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
            <div class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">Previous State Value</div>
            <div class="fw-mono text-danger" id="auditPrev">Schedule Run #4</div>
        </div>

        <div class="p-3 rounded" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);">
            <div class="text-muted mb-1" style="font-size: 0.75rem; text-transform: uppercase;">New Updated Value</div>
            <div class="fw-mono text-success" id="auditNew">Schedule Run #5</div>
        </div>
    </div>
</x-modal>

@push('scripts')
<script>
    function openAuditModal(user, action, time, entity, prev, newval) {
        document.getElementById('auditUser').textContent = user;
        document.getElementById('auditAction').textContent = action;
        document.getElementById('auditTime').textContent = time;
        document.getElementById('auditEntity').textContent = entity;
        document.getElementById('auditPrev').textContent = prev;
        document.getElementById('auditNew').textContent = newval;

        const myModal = new bootstrap.Modal(document.getElementById('auditModal'));
        myModal.show();
    }
</script>
@endpush

@endsection
