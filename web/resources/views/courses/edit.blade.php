@extends('layouts.app')

@section('title', 'Edit Course')

@section('content')

<div class="mb-4">
    <a href="{{ route('courses.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Courses Catalog
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">Edit Course — CSE3201</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Update course credits, type, and weekly session requirements.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <form action="{{ route('courses.index') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Course Code</label>
                        <input type="text" name="code" class="form-control form-control-custom" value="CSE3201" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Department</label>
                        <select name="department" class="form-select form-select-custom" required>
                            <option value="CSE" selected>Computer Science & Engineering</option>
                            <option value="EEE">Electrical & Electronic Engineering</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">Course Title / Name</label>
                    <input type="text" name="name" class="form-control form-control-custom" value="Database Systems" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Course Type</label>
                        <select name="type" class="form-select form-select-custom" required>
                            <option value="THEORY" selected>Theory (Lecture)</option>
                            <option value="LAB">Lab (Practical)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Credit Hours</label>
                        <input type="number" step="0.5" name="credit_hours" class="form-control form-control-custom" value="3.0" required>
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
                        <label class="form-label-custom">Weekly Theory Classes</label>
                        <input type="number" name="weekly_theory_classes" class="form-control form-control-custom" value="3">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Weekly Lab Classes</label>
                        <input type="number" name="weekly_lab_classes" class="form-control form-control-custom" value="0">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top border-secondary">
                    <a href="{{ route('courses.index') }}" class="btn-glass">Cancel</a>
                    <button type="submit" class="btn-indigo">
                        <i class="bi bi-check-lg"></i> Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
