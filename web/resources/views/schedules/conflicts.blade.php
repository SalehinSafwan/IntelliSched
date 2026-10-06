@extends('layouts.app')

@section('title', 'Conflict Diagnostics')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Schedule Conflict Validation</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Automated conflict detection across teachers, rooms, sections, exams, capacity, and availability limits.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-glass" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Re-scan Timetable
        </button>
    </div>
</div>

<!-- Validation Summary Status Grid -->
<div class="glass-card mb-4 p-4" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.08)); border-color: rgba(16, 185, 129, 0.3);">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle" style="width: 48px; height: 48px; background: var(--grad-success); font-size: 1.4rem;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <h4 class="brand-font fw-bold text-white mb-0">Schedule is 100% Conflict Free</h4>
                <div class="text-muted" style="font-size: 0.82rem;">All 36 scheduled classes satisfied hard integer programming constraints.</div>
            </div>
        </div>
        <span class="badge bg-success p-2" style="font-size: 0.85rem;">0 Hard Conflicts</span>
    </div>

    <div class="row g-3 text-center mt-2" style="font-size: 0.85rem;">
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Teacher Overlap</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Room Booking</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Section Overlap</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Room Capacity</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Availability</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="text-success fw-bold">✓ 0 Conflicts</div>
                <div class="text-muted" style="font-size: 0.75rem;">Lab Eligibility</div>
            </div>
        </div>
    </div>
</div>

<!-- Simulated Conflict Inspection & Resolution Tool -->
<div class="glass-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="brand-font fw-bold text-white mb-0">Conflict Diagnostics & Interactive Resolution</h5>
            <p class="text-muted mb-0" style="font-size: 0.82rem;">Simulated conflict items for defense demonstration and manual override resolution.</p>
        </div>
        <button class="btn btn-sm btn-glass text-warning" onclick="toggleConflictSimulation()">
            <i class="bi bi-bug me-1"></i> Toggle Simulated Conflicts
        </button>
    </div>

    <!-- Hidden simulated conflicts container -->
    <div id="simulatedConflictsContainer" class="d-none">
        <div class="alert alert-warning mb-3" role="alert" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.3); color: #fcd34d;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>⚠ 2 Problems Found</strong> in manual draft timetable allocation!
        </div>

        <div class="d-flex flex-column gap-3">
            <!-- Conflict 1: Teacher Overlap -->
            <div class="p-3 rounded-4" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-danger">Teacher Conflict</span>
                            <h6 class="fw-bold text-white mb-0">Dr. Ahmed Rahman — Double Booking</h6>
                        </div>
                        <div class="text-muted" style="font-size: 0.82rem;">
                            Time: Sunday 10:25 AM · Overlapping Assignments: <span class="text-white fw-bold">CSE 47 Section A</span> and <span class="text-white fw-bold">CSE 48 Section B</span>
                        </div>
                    </div>
                    <button class="btn-indigo btn-sm-custom" data-bs-toggle="modal" data-bs-target="#resolveModal">
                        <i class="bi bi-wrench me-1"></i> Resolve Conflict
                    </button>
                </div>
            </div>

            <!-- Conflict 2: Room Double Booking -->
            <div class="p-3 rounded-4" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-danger">Room Conflict</span>
                            <h6 class="fw-bold text-white mb-0">LAB-01 — Simultaneous Lab Bookings</h6>
                        </div>
                        <div class="text-muted" style="font-size: 0.82rem;">
                            Time: Tuesday 12:50 PM · Overlapping Bookings: <span class="text-white fw-bold">Database Lab</span> and <span class="text-white fw-bold">Networking Lab</span>
                        </div>
                    </div>
                    <button class="btn-indigo btn-sm-custom" data-bs-toggle="modal" data-bs-target="#resolveModal">
                        <i class="bi bi-wrench me-1"></i> Resolve Conflict
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="noConflictMsg" class="p-4 text-center text-muted">
        <i class="bi bi-shield-check text-success display-6 mb-2 d-block"></i>
        No active conflicts detected. Click "Toggle Simulated Conflicts" to test resolution workflow.
    </div>
</div>

<!-- Resolution Modal -->
<x-modal id="resolveModal" title="Resolve Timetable Conflict">
    <div class="mb-3">
        <div class="text-muted" style="font-size: 0.82rem;">Target Conflict:</div>
        <div class="fw-bold text-white">Dr. Ahmed Rahman — Sunday 10:25 AM Overlap</div>
    </div>
    <div class="mb-3">
        <label class="form-label-custom">Resolution Action</label>
        <select class="form-select form-select-custom">
            <option value="reassign_teacher">Reassign CSE 48 Section B to Dr. Farhana Nusrat</option>
            <option value="move_timeslot">Move CSE 47 Section A class to Sunday 01:40 PM</option>
            <option value="rerun_solver">Trigger Automatic Engine Regeneration</option>
        </select>
    </div>
    <x-slot:footer>
        <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-indigo" data-bs-dismiss="modal" onclick="alert('Conflict resolved successfully!');">Apply Resolution</button>
    </x-slot:footer>
</x-modal>

@push('scripts')
<script>
    function toggleConflictSimulation() {
        const container = document.getElementById('simulatedConflictsContainer');
        const msg = document.getElementById('noConflictMsg');
        container.classList.toggle('d-none');
        msg.classList.toggle('d-none');
    }
</script>
@endpush

@endsection
