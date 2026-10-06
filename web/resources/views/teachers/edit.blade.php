@extends('layouts.app')

@section('title', 'Edit Teacher')

@section('content')

<div class="mb-4">
    <a href="{{ route('teachers.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Faculty Directory
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">Edit Teacher — Dr. Ahmed Rahman</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Update faculty profile and teaching limit parameters.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <form action="{{ route('teachers.index') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control form-control-custom" value="T001" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-custom" value="Dr. Ahmed Rahman" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-custom" value="ahmed@intellisched.edu" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Department</label>
                        <select name="department" class="form-select form-select-custom" required>
                            <option value="CSE" selected>Computer Science & Engineering</option>
                            <option value="EEE">Electrical & Electronic Engineering</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Designation</label>
                        <select name="designation" class="form-select form-select-custom">
                            <option value="Professor" selected>Professor</option>
                            <option value="Associate Professor">Associate Professor</option>
                            <option value="Assistant Professor">Assistant Professor</option>
                            <option value="Lecturer">Lecturer</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Max Weekly Hours Limit</label>
                        <input type="number" name="max_weekly_hours" class="form-control form-control-custom" value="14" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top border-secondary">
                    <a href="{{ route('teachers.index') }}" class="btn-glass">Cancel</a>
                    <button type="submit" class="btn-indigo">
                        <i class="bi bi-check-lg"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
