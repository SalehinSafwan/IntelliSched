@extends('layouts.app')

@section('title', 'Section Detail — CSE 47 Section A')

@section('content')

<div class="mb-4">
    <a href="{{ route('batches.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Batches Overview
    </a>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-2">
        <div>
            <h2 class="brand-font fw-bold text-white mb-1">CSE 47 — Section A</h2>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">6th Semester · Computer Science & Engineering · Fall 2026</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('schedules.index', ['view' => 'section']) }}" class="btn-indigo">
                <i class="bi bi-calendar-week me-1"></i> View Full Section Timetable
            </a>
        </div>
    </div>
</div>

<!-- Overview Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="glass-card stat-card">
            <div>
                <div class="stat-lbl">Student Capacity</div>
                <div class="stat-val text-white">60 Students</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="glass-card stat-card">
            <div>
                <div class="stat-lbl">Enrolled Courses</div>
                <div class="stat-val text-white">8 Courses</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-accent">
                <i class="bi bi-journal-check"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="glass-card stat-card">
            <div>
                <div class="stat-lbl">Assigned Faculty</div>
                <div class="stat-val text-white">6 Teachers</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="bi bi-person-badge-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3">
        <div class="glass-card stat-card">
            <div>
                <div class="stat-lbl">Routine Version</div>
                <div class="stat-val text-emerald" style="color: #6ee7b7;">Run #5</div>
            </div>
            <div class="stat-icon-wrapper stat-icon-warning">
                <i class="bi bi-cpu-fill"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Enrolled Courses & Assigned Faculty Table -->
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3">Assigned Semester Courses & Teachers</h5>
            <div class="table-custom-container">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Type</th>
                            <th>Assigned Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold text-white brand-font">CSE3201</td>
                            <td class="fw-semibold text-white">Database Systems</td>
                            <td><span class="badge-custom badge-theory">Theory</span></td>
                            <td><span class="text-indigo fw-semibold">Dr. Ahmed Rahman</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-white brand-font">CSE3202</td>
                            <td class="fw-semibold text-white">Database Systems Lab</td>
                            <td><span class="badge-custom badge-lab">Lab</span></td>
                            <td><span class="text-indigo fw-semibold">Dr. Ahmed Rahman</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-white brand-font">CSE3203</td>
                            <td class="fw-semibold text-white">Artificial Intelligence</td>
                            <td><span class="badge-custom badge-theory">Theory</span></td>
                            <td><span class="text-indigo fw-semibold">Dr. Farhana Nusrat</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-white brand-font">CSE3205</td>
                            <td class="fw-semibold text-white">Software Engineering</td>
                            <td><span class="badge-custom badge-theory">Theory</span></td>
                            <td><span class="text-indigo fw-semibold">Tanvir Hasan</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Class Representative & Quick Info -->
    <div class="col-12 col-lg-5">
        <div class="glass-card mb-4">
            <h5 class="brand-font fw-bold text-white mb-3"><i class="bi bi-person-fill-check me-2 text-indigo"></i> Class Representative (CR)</h5>
            <div class="d-flex align-items-center gap-3 p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                <div class="avatar-circle" style="width: 46px; height: 46px;">S</div>
                <div>
                    <div class="fw-bold text-white">Sabbir Ahmed</div>
                    <div class="text-muted" style="font-size: 0.8rem;">ID: 2023-1-60-045 · sabbir@student.intellisched.edu</div>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3">Routine Quick Summary</h5>
            <div class="p-3 rounded" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);">
                <div class="d-flex justify-content-between text-white mb-2" style="font-size: 0.85rem;">
                    <span>Weekly Classes:</span> <strong>18 Sessions</strong>
                </div>
                <div class="d-flex justify-content-between text-white mb-2" style="font-size: 0.85rem;">
                    <span>Lab Sessions:</span> <strong>3 Practical Labs</strong>
                </div>
                <div class="d-flex justify-content-between text-white" style="font-size: 0.85rem;">
                    <span>Free Day:</span> <strong class="text-success">Thursday Afternoon</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
