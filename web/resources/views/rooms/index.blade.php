@extends('layouts.app')

@section('title', 'Room Management')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Classrooms & Laboratories</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage room capacities, lab course eligibility, priority levels, and building locations.</p>
    </div>
    <div>
        <a href="{{ route('rooms.create') }}" class="btn-indigo">
            <i class="bi bi-plus-lg"></i> Add New Room
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="glass-card mb-4 p-3">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
                <input type="text" class="form-control form-control-custom" placeholder="Search by room number, building, or type..." style="padding-left: 40px !important;">
            </div>
        </div>
        <div class="col-12 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Room Types</option>
                <option value="Theory">Theory Classroom</option>
                <option value="Lab">Specialized Lab</option>
                <option value="Lab/Theory">Lab / Theory Multi-use</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <select class="form-select form-select-custom">
                <option value="">All Buildings</option>
                <option value="Academic Building 1">Academic Bldg 1</option>
                <option value="CSE Building">CSE Building</option>
            </select>
        </div>
    </div>
</div>

<!-- Rooms Table -->
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Room No</th>
                <th>Room Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Priority</th>
                <th>Building / Floor</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rooms = [
                    ['no' => '101', 'name' => 'Lecture Hall 101', 'type' => 'Theory', 'cap' => 60, 'prio' => 1, 'bldg' => 'Academic Bldg 1', 'fl' => '1st Floor', 'status' => 'ACTIVE'],
                    ['no' => '102', 'name' => 'Lecture Hall 102', 'type' => 'Theory', 'cap' => 60, 'prio' => 2, 'bldg' => 'Academic Bldg 1', 'fl' => '1st Floor', 'status' => 'ACTIVE'],
                    ['no' => 'LAB-01', 'name' => 'Database & Software Lab', 'type' => 'Lab', 'cap' => 30, 'prio' => 5, 'bldg' => 'CSE Building', 'fl' => '2nd Floor', 'status' => 'ACTIVE'],
                    ['no' => 'LAB-02', 'name' => 'AI & Robotics Lab', 'type' => 'Lab', 'cap' => 30, 'prio' => 4, 'bldg' => 'CSE Building', 'fl' => '2nd Floor', 'status' => 'ACTIVE'],
                    ['no' => 'LAB-T1', 'name' => 'Multi-purpose Lab/Theory', 'type' => 'Lab/Theory', 'cap' => 60, 'prio' => 3, 'bldg' => 'CSE Building', 'fl' => '3rd Floor', 'status' => 'ACTIVE'],
                ];
            @endphp

            @foreach($rooms as $r)
                <tr>
                    <td class="fw-bold text-white brand-font">{{ $r['no'] }}</td>
                    <td class="fw-semibold text-white">{{ $r['name'] }}</td>
                    <td>
                        @if($r['type'] === 'Theory')
                            <span class="badge-custom badge-theory">Theory</span>
                        @elseif($r['type'] === 'Lab')
                            <span class="badge-custom badge-lab">Specialized Lab</span>
                        @else
                            <span class="badge bg-purple" style="background: var(--accent-purple);">Lab / Theory</span>
                        @endif
                    </td>
                    <td class="fw-bold text-indigo"><i class="bi bi-people-fill me-1"></i> {{ $r['cap'] }} Seats</td>
                    <td><span class="badge bg-dark border border-secondary">Priority #{{ $r['prio'] }}</span></td>
                    <td class="text-muted" style="font-size: 0.85rem;">{{ $r['bldg'] }} ({{ $r['fl'] }})</td>
                    <td><span class="badge-custom badge-active">Active</span></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('rooms.show', ['room' => $r['no']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="Lab Eligibility & Details">
                                <i class="bi bi-shield-check"></i>
                            </a>
                            <a href="{{ route('rooms.edit', ['room' => $r['no']]) }}" class="btn btn-sm btn-glass py-1 px-2" title="Edit Room">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-glass text-danger py-1 px-2" title="Delete Room">
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
