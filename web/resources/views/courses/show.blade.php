@extends('layouts.app')

@section('title', 'Course Details — CSE3201')

@section('content')

<div class="mb-4">
    <a href="{{ route('courses.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Courses Catalog
    </a>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-2">
        <div>
            <h2 class="brand-font fw-bold text-white mb-1">CSE3201 — Database Systems</h2>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">Computer Science & Engineering Department · Theory Course</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('courses.edit', ['course' => 'CSE3201']) }}" class="btn-indigo">
                <i class="bi bi-pencil me-1"></i> Edit Course
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3">Specification</h5>
            <div class="d-flex flex-column gap-2" style="font-size: 0.88rem;">
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Course Code</span>
                    <span class="fw-bold text-white">CSE3201</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Type</span>
                    <span class="badge-custom badge-theory">Theory</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Credit Hours</span>
                    <span class="fw-bold text-indigo">3.0 Credits</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Weekly Lectures</span>
                    <span class="fw-bold text-white">3 Sessions / Wk</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted">Status</span>
                    <span class="badge-custom badge-active">Active</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8">
        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3">Eligible & Qualified Teachers</h5>
            <div class="table-custom-container">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Expertise Score</th>
                            <th>Preference</th>
                            <th>Past Batches Taught</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-white">Dr. Ahmed Rahman</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-1" style="height: 6px; width: 80px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar bg-success" style="width: 90%;"></div>
                                    </div>
                                    <span class="fw-bold text-success" style="font-size: 0.8rem;">90 / 100</span>
                                </div>
                            </td>
                            <td><span class="badge bg-indigo" style="background: var(--primary);">High (85)</span></td>
                            <td class="text-muted">3 Times (CSE 44, 45, 46)</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-white">Tanvir Hasan</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-1" style="height: 6px; width: 80px; background: rgba(255,255,255,0.1);">
                                        <div class="progress-bar bg-info" style="width: 75%;"></div>
                                    </div>
                                    <span class="fw-bold text-info" style="font-size: 0.8rem;">75 / 100</span>
                                </div>
                            </td>
                            <td><span class="badge bg-dark border border-secondary">Medium (70)</span></td>
                            <td class="text-muted">1 Time (CSE 46)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
