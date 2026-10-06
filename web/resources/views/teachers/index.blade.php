@extends('layouts.app')

@section('title', 'Teacher Management')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Faculty & Teacher Directory</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage faculty profiles, teaching expertise, course suitability, and workload limits.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('teachers.preferences') }}" class="btn-glass">
            <i class="bi bi-sliders"></i> Course Preferences
        </a>
        <a href="{{ route('teachers.create') }}" class="btn-indigo">
            <i class="bi bi-person-plus-fill"></i> Add New Teacher
        </a>
    </div>
</div>

<!-- Search & Filter -->
<div class="glass-card mb-4 p-3">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
                <input type="text" class="form-control form-control-custom" placeholder="Search by name, employee ID, or department..." style="padding-left: 40px !important;">
            </div>
        </div>
        <div class="col-12 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Departments</option>
                <option value="CSE" selected>CSE Department</option>
                <option value="EEE">EEE Department</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">Workload Status</option>
                <option value="NORMAL">Normal Workload</option>
                <option value="MAXED">Max Capacity</option>
            </select>
        </div>
    </div>
</div>

<!-- Teachers Table -->
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Emp ID</th>
                <th>Teacher Name</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Weekly Workload</th>
                <th>Expertise Areas</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $teachers = [
                    ['id' => 'T001', 'name' => 'Dr. Ahmed Rahman', 'dept' => 'CSE', 'desig' => 'Professor', 'hrs' => 12, 'max_hrs' => 14, 'skills' => ['Database', 'Software Eng', 'AI']],
                    ['id' => 'T002', 'name' => 'Dr. Farhana Nusrat', 'dept' => 'CSE', 'desig' => 'Associate Professor', 'hrs' => 10, 'max_hrs' => 12, 'skills' => ['Algorithms', 'Machine Learning']],
                    ['id' => 'T003', 'name' => 'Tanvir Hasan', 'dept' => 'CSE', 'desig' => 'Assistant Professor', 'hrs' => 14, 'max_hrs' => 14, 'skills' => ['Networking', 'Cybersecurity']],
                    ['id' => 'T004', 'name' => 'Mahmudul Karim', 'dept' => 'CSE', 'desig' => 'Lecturer', 'hrs' => 16, 'max_hrs' => 16, 'skills' => ['C++ Programming', 'Data Structures']],
                ];
            @endphp

            @foreach($teachers as $t)
                <tr>
                    <td class="fw-bold text-white brand-font">{{ $t['id'] }}</td>
                    <td>
                        <a href="{{ route('teachers.show', ['teacher' => $t['id']]) }}" class="fw-semibold text-white text-decoration-none">
                            {{ $t['name'] }}
                        </a>
                    </td>
                    <td><span class="badge bg-dark border border-secondary text-light">{{ $t['dept'] }}</span></td>
                    <td class="text-muted">{{ $t['desig'] }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress" style="height: 6px; width: 70px; background: rgba(255,255,255,0.1);">
                                <div class="progress-bar {{ $t['hrs'] == $t['max_hrs'] ? 'bg-warning' : 'bg-success' }}" style="width: {{ ($t['hrs'] / $t['max_hrs']) * 100 }}%;"></div>
                            </div>
                            <span class="fw-semibold" style="font-size: 0.8rem; color: var(--text-muted);">
                                {{ $t['hrs'] }}/{{ $t['max_hrs'] }} hrs
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($t['skills'] as $skill)
                                <span class="badge bg-indigo" style="font-size: 0.7rem; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc;">{{ $skill }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('teachers.show', ['teacher' => $t['id']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="Teacher Profile">
                                <i class="bi bi-person-lines-fill"></i>
                            </a>
                            <a href="{{ route('teachers.availability', ['teacher' => $t['id']]) }}" class="btn btn-sm btn-glass py-1 px-2 text-info" title="Manage Availability">
                                <i class="bi bi-calendar-week"></i>
                            </a>
                            <a href="{{ route('teachers.edit', ['teacher' => $t['id']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="Edit Teacher">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
