@extends('layouts.app')

@section('title', 'Courses Management')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Course Catalog</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage academic courses, credit allocations, and weekly lecture/lab hour requirements.</p>
    </div>
    <div>
        <a href="{{ route('courses.create') }}" class="btn-indigo">
            <i class="bi bi-plus-lg"></i> Add New Course
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="glass-card mb-4 p-3">
    <div class="row g-3">
        <div class="col-12 col-md-5">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
                <input type="text" class="form-control form-control-custom" placeholder="Search by course code or title..." style="padding-left: 40px !important;">
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Departments</option>
                <option value="CSE" selected>Computer Science & Eng (CSE)</option>
                <option value="EEE">Electrical & Electronic Eng (EEE)</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Course Types</option>
                <option value="THEORY">Theory</option>
                <option value="LAB">Lab</option>
            </select>
        </div>
        <div class="col-12 col-md-1 d-flex">
            <button class="btn btn-glass w-100 justify-content-center" title="Reset Filters">
                <i class="bi bi-funnel-fill"></i>
            </button>
        </div>
    </div>
</div>

<!-- Course List Table -->
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Code</th>
                <th>Course Title</th>
                <th>Department</th>
                <th>Type</th>
                <th>Credits</th>
                <th>Weekly Hours</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $mockCourses = [
                    ['code' => 'CSE3201', 'name' => 'Database Systems', 'dept' => 'CSE', 'type' => 'THEORY', 'credit' => 3.0, 'theory' => 3, 'lab' => 0, 'status' => 'ACTIVE'],
                    ['code' => 'CSE3202', 'name' => 'Database Systems Lab', 'dept' => 'CSE', 'type' => 'LAB', 'credit' => 1.5, 'theory' => 0, 'lab' => 3, 'status' => 'ACTIVE'],
                    ['code' => 'CSE3203', 'name' => 'Artificial Intelligence', 'dept' => 'CSE', 'type' => 'THEORY', 'credit' => 3.0, 'theory' => 3, 'lab' => 0, 'status' => 'ACTIVE'],
                    ['code' => 'CSE3204', 'name' => 'AI & Neural Networks Lab', 'dept' => 'CSE', 'type' => 'LAB', 'credit' => 1.5, 'theory' => 0, 'lab' => 3, 'status' => 'ACTIVE'],
                    ['code' => 'CSE3205', 'name' => 'Software Engineering', 'dept' => 'CSE', 'type' => 'THEORY', 'credit' => 3.0, 'theory' => 3, 'lab' => 0, 'status' => 'ACTIVE'],
                    ['code' => 'CSE3206', 'name' => 'Compiler Design', 'dept' => 'CSE', 'type' => 'THEORY', 'credit' => 3.0, 'theory' => 3, 'lab' => 0, 'status' => 'ACTIVE'],
                ];
            @endphp

            @foreach($mockCourses as $c)
                <tr>
                    <td class="fw-bold text-white brand-font">{{ $c['code'] }}</td>
                    <td class="fw-semibold text-white">{{ $c['name'] }}</td>
                    <td><span class="badge bg-dark border border-secondary text-light">{{ $c['dept'] }}</span></td>
                    <td>
                        @if($c['type'] === 'THEORY')
                            <span class="badge-custom badge-theory"><i class="bi bi-book me-1"></i> Theory</span>
                        @else
                            <span class="badge-custom badge-lab"><i class="bi bi-pc-display me-1"></i> Lab</span>
                        @endif
                    </td>
                    <td class="fw-bold text-indigo">{{ number_format($c['credit'], 1) }}</td>
                    <td>
                        <span class="text-muted" style="font-size: 0.85rem;">
                            {{ $c['theory'] }}h Theory · {{ $c['lab'] }}h Lab
                        </span>
                    </td>
                    <td>
                        <span class="badge-custom badge-active">Active</span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('courses.show', ['course' => $c['code']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('courses.edit', ['course' => $c['code']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="Edit Course">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-glass text-danger py-1 px-2" title="Delete Course">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
