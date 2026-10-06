@extends('layouts.app')

@section('title', 'Add New Course')

@section('content')

<div class="mb-4">
    <a href="{{ route('courses.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Courses Catalog
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">Create Academic Course</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Define a new course specification for the intelligent scheduling engine.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <form action="{{ route('courses.index') }}" method="POST">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Course Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control form-control-custom" placeholder="e.g. CSE3201" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Department <span class="text-danger">*</span></label>
                        <select name="department" class="form-select form-select-custom" required>
                            <option value="CSE" selected>Computer Science & Engineering</option>
                            <option value="EEE">Electrical & Electronic Engineering</option>
                            <option value="ME">Mechanical Engineering</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">Course Title / Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Database Systems" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Course Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-custom" required>
                            <option value="THEORY" selected>Theory (Lecture)</option>
                            <option value="LAB">Lab (Practical)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Credit Hours <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="credit_hours" class="form-control form-control-custom" placeholder="3.0" value="3.0" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Status</label>
                        <select name="status" class="form-select form-select-custom">
                            <option value="ACTIVE" selected>Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Weekly Theory Classes (Slots/Wk)</label>
                        <input type="number" name="weekly_theory_classes" class="form-control form-control-custom" value="3">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Weekly Lab Classes (Slots/Wk)</label>
                        <input type="number" name="weekly_lab_classes" class="form-control form-control-custom" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top border-secondary">
                    <a href="{{ route('courses.index') }}" class="btn-glass">Cancel</a>
                    <button type="submit" class="btn-indigo">
                        <i class="bi bi-check-lg"></i> Save Course
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-4 mt-4 mt-lg-0">
        <div class="glass-card">
            <h5 class="brand-font fw-bold text-white mb-3"><i class="bi bi-info-circle me-2 text-indigo"></i> Guidance</h5>
            <p class="text-muted" style="font-size: 0.85rem;">
                Theory courses require regular lecture classrooms, whereas Lab courses require specialized laboratory rooms and continuous double time-slots.
            </p>
            <div class="p-3 rounded" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);">
                <div class="fw-semibold text-white mb-1" style="font-size: 0.85rem;">Continuous Slot Allocation</div>
                <div class="text-muted" style="font-size: 0.78rem;">
                    The scheduler automatically ensures lab classes receive consecutive time slots without mid-session breaks.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
