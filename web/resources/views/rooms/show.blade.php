@extends('layouts.app')

@section('title', 'Room Details & Eligibility — LAB-01')

@section('content')

<div class="mb-4">
    <a href="{{ route('rooms.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Rooms Directory
    </a>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-2">
        <div>
            <h2 class="brand-font fw-bold text-white mb-1">Room LAB-01 — Database & Software Lab</h2>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">CSE Building · 2nd Floor · Specialized Laboratory (Capacity: 30)</p>
        </div>
        <div>
            <a href="{{ route('rooms.edit', ['room' => 'LAB-01']) }}" class="btn-indigo">
                <i class="bi bi-pencil me-1"></i> Edit Details
            </a>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Basic Room Info -->
    <div class="col-12 col-md-5">
        <div class="glass-card mb-4">
            <h5 class="brand-font fw-bold text-white mb-3">Room Specifications</h5>
            <div class="d-flex flex-column gap-2" style="font-size: 0.88rem;">
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Room Number</span>
                    <span class="fw-bold text-white">LAB-01</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Type</span>
                    <span class="badge-custom badge-lab">Specialized Lab</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Seating Capacity</span>
                    <span class="fw-bold text-indigo">30 Workstations</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Scheduling Priority</span>
                    <span class="badge bg-dark border border-secondary">Priority #5</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Building & Floor</span>
                    <span class="text-white">CSE Building (2nd Floor)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Status</span>
                    <span class="badge-custom badge-active">Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Course Eligibility Matrix -->
    <div class="col-12 col-md-7">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="brand-font fw-bold text-white mb-1">Lab Course Eligibility Matrix</h5>
                    <p class="text-muted mb-0" style="font-size: 0.82rem;">Select which lab courses can be scheduled in LAB-01.</p>
                </div>
                <span class="badge bg-indigo" style="font-size: 0.75rem;">Constraint Rule</span>
            </div>

            <form action="{{ route('rooms.show', ['room' => 'LAB-01']) }}" method="POST">
                @csrf

                <div class="d-flex flex-column gap-2 mb-4">
                    <label class="d-flex align-items-center justify-content-between p-3 rounded cursor-pointer" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="eligible_courses[]" value="CSE3202" checked style="width: 18px; height: 18px; accent-color: var(--success);">
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">CSE3202 — Database Systems Lab</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Requires MySQL/PostgreSQL Server Workstations</div>
                            </div>
                        </div>
                        <span class="badge bg-success" style="font-size: 0.75rem;">✓ Eligible</span>
                    </label>

                    <label class="d-flex align-items-center justify-content-between p-3 rounded cursor-pointer" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="eligible_courses[]" value="CSE3206" checked style="width: 18px; height: 18px; accent-color: var(--success);">
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">CSE3206 — Networking & System Security Lab</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Requires Cisco Packet Tracer Hardware Racks</div>
                            </div>
                        </div>
                        <span class="badge bg-success" style="font-size: 0.75rem;">✓ Eligible</span>
                    </label>

                    <label class="d-flex align-items-center justify-content-between p-3 rounded cursor-pointer" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="eligible_courses[]" value="CSE3204" style="width: 18px; height: 18px; accent-color: var(--danger);">
                            <div>
                                <div class="fw-bold text-white" style="font-size: 0.9rem;">CSE3204 — AI & Neural Networks Lab</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Requires High-Performance GPU Acceleration Workstations</div>
                            </div>
                        </div>
                        <span class="badge bg-danger" style="font-size: 0.75rem;">✕ Not Eligible</span>
                    </label>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-indigo">
                        <i class="bi bi-floppy-fill me-1"></i> Save Eligibility
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
