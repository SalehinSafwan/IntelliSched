@extends('layouts.app')

@section('title', 'Teacher Profile — Dr. Ahmed Rahman')

@section('content')

<div class="mb-4">
    <a href="{{ route('teachers.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Faculty Directory
    </a>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-2">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-circle" style="width: 54px; height: 54px; font-size: 1.4rem;">
                A
            </div>
            <div>
                <h2 class="brand-font fw-bold text-white mb-0">Dr. Ahmed Rahman</h2>
                <span class="text-muted" style="font-size: 0.88rem;">Professor · Department of CSE · Employee ID: T001</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('teachers.availability', ['teacher' => 'T001']) }}" class="btn-glass">
                <i class="bi bi-calendar-week text-info me-1"></i> Availability Timetable
            </a>
            <a href="{{ route('teachers.preferences') }}" class="btn-indigo">
                <i class="bi bi-sliders me-1"></i> Edit Preferences & Scores
            </a>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Basic Information & Workload -->
    <div class="col-12 col-md-4">
        <div class="glass-card mb-4">
            <h5 class="brand-font fw-bold text-white mb-3"><i class="bi bi-person-vcard me-2 text-indigo"></i> Basic Information</h5>
            <div class="d-flex flex-column gap-2" style="font-size: 0.88rem;">
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Employee ID</span>
                    <span class="fw-bold text-white">T001</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Department</span>
                    <span class="badge bg-dark border border-secondary">CSE</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Email</span>
                    <span class="text-indigo">ahmed@intellisched.edu</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Max Workload</span>
                    <span class="fw-bold text-white">14 Credit Hrs / Wk</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Current Assigned</span>
                    <span class="fw-bold text-success">12 Credit Hrs</span>
                </div>
            </div>
        </div>

        <!-- Teaching History -->
        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3"><i class="bi bi-clock-history me-2 text-warning"></i> Teaching History</h5>
            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: rgba(255,255,255,0.03);">
                    <div>
                        <div class="fw-semibold text-white" style="font-size: 0.88rem;">Database Systems</div>
                        <div class="text-muted" style="font-size: 0.75rem;">CSE3201 · Theory</div>
                    </div>
                    <span class="badge bg-indigo" style="background: var(--primary);">3 Times</span>
                </div>

                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: rgba(255,255,255,0.03);">
                    <div>
                        <div class="fw-semibold text-white" style="font-size: 0.88rem;">Software Engineering</div>
                        <div class="text-muted" style="font-size: 0.75rem;">CSE3205 · Theory</div>
                    </div>
                    <span class="badge bg-indigo" style="background: var(--primary);">2 Times</span>
                </div>

                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background: rgba(255,255,255,0.03);">
                    <div>
                        <div class="fw-semibold text-white" style="font-size: 0.88rem;">Database Systems Lab</div>
                        <div class="text-muted" style="font-size: 0.75rem;">CSE3202 · Lab</div>
                    </div>
                    <span class="badge bg-indigo" style="background: var(--primary);">3 Times</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Expertise & Preferred Courses (Input for scoring engine) -->
    <div class="col-12 col-md-8">
        <!-- Domain Expertise -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="brand-font fw-bold text-white mb-0">Domain Expertise Scores</h5>
                    <p class="text-muted mb-0" style="font-size: 0.78rem;">Scoring engine evaluation factor (Weight: 35%)</p>
                </div>
                <span class="badge bg-success" style="font-size: 0.75rem;">Verified</span>
            </div>

            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="fw-semibold text-white">Database Systems</span>
                        <span class="fw-bold text-success">90 / 100</span>
                    </div>
                    <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px;">
                        <div class="progress-bar bg-success" style="width: 90%;"></div>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="fw-semibold text-white">Software Engineering</span>
                        <span class="fw-bold text-info">80 / 100</span>
                    </div>
                    <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px;">
                        <div class="progress-bar bg-info" style="width: 80%;"></div>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="fw-semibold text-white">Artificial Intelligence</span>
                        <span class="fw-bold text-warning">70 / 100</span>
                    </div>
                    <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px;">
                        <div class="progress-bar bg-warning" style="width: 70%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preferred Courses -->
        <div class="glass-card mb-4">
            <h5 class="brand-font fw-bold text-white mb-3">Preferred Courses Ranking</h5>
            <div class="table-custom-container">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Type</th>
                            <th>Preference Score</th>
                            <th>Suitability Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-white">CSE3201 — Database Systems</td>
                            <td><span class="badge-custom badge-theory">Theory</span></td>
                            <td><span class="fw-bold text-success">90</span></td>
                            <td><span class="badge bg-success">High (0.92)</span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">CSE3205 — Software Engineering</td>
                            <td><span class="badge-custom badge-theory">Theory</span></td>
                            <td><span class="fw-bold text-info">85</span></td>
                            <td><span class="badge bg-info">High (0.85)</span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">CSE3202 — Database Systems Lab</td>
                            <td><span class="badge-custom badge-lab">Lab</span></td>
                            <td><span class="fw-bold text-warning">75</span></td>
                            <td><span class="badge bg-warning">Medium (0.78)</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Availability Preview -->
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="brand-font fw-bold text-white mb-0">Availability Summary</h5>
                <a href="{{ route('teachers.availability', ['teacher' => 'T001']) }}" class="btn btn-sm btn-glass text-info">
                    <i class="bi bi-pencil me-1"></i> Edit Grid
                </a>
            </div>
            <div class="row g-2 text-center" style="font-size: 0.8rem;">
                <div class="col"><div class="p-2 rounded" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7;">Sun: 8/8 Slots</div></div>
                <div class="col"><div class="p-2 rounded" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7;">Mon: 7/8 Slots</div></div>
                <div class="col"><div class="p-2 rounded" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #fde68a;">Tue: 5/8 Slots</div></div>
                <div class="col"><div class="p-2 rounded" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7;">Wed: 8/8 Slots</div></div>
                <div class="col"><div class="p-2 rounded" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7;">Thu: 8/8 Slots</div></div>
            </div>
        </div>
    </div>

</div>

@endsection
