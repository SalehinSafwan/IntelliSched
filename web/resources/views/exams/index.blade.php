@extends('layouts.app')

@section('title', 'Examination & CT Schedules')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Examinations & Class Test Timetable</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage CTs, Midterm, and Final examination schedules with collision-free room allocation.</p>
    </div>
    <div>
        <button class="btn-indigo" data-bs-toggle="modal" data-bs-target="#createExamModal">
            <i class="bi bi-plus-lg me-1"></i> Create Examination Schedule
        </button>
    </div>
</div>

<!-- Exam Timetable Table -->
<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Type</th>
                <th>Course Name</th>
                <th>Section</th>
                <th>Exam Date</th>
                <th>Time Slot</th>
                <th>Assigned Room</th>
                <th>Duration</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $exams = [
                    ['type' => 'CT 1', 'course' => 'CSE3201 — Database Systems', 'sec' => 'Section A', 'date' => '15 Nov 2026', 'time' => '10:00 AM', 'room' => 'Room 101', 'dur' => '50 Mins', 'status' => 'SCHEDULED'],
                    ['type' => 'CT 1', 'course' => 'CSE3201 — Database Systems', 'sec' => 'Section B', 'date' => '15 Nov 2026', 'time' => '10:00 AM', 'room' => 'Room 102', 'dur' => '50 Mins', 'status' => 'SCHEDULED'],
                    ['type' => 'CT 1', 'course' => 'CSE3205 — Software Eng', 'sec' => 'Section A', 'date' => '17 Nov 2026', 'time' => '11:00 AM', 'room' => 'Room 101', 'dur' => '50 Mins', 'status' => 'SCHEDULED'],
                    ['type' => 'MIDTERM', 'course' => 'CSE3203 — Artificial Intelligence', 'sec' => 'Sections A & B', 'date' => '05 Dec 2026', 'time' => '09:00 AM', 'room' => 'Auditorium 1', 'dur' => '2 Hours', 'status' => 'DRAFT'],
                ];
            @endphp

            @foreach($exams as $e)
                <tr>
                    <td>
                        @if($e['type'] === 'MIDTERM')
                            <span class="badge bg-danger" style="font-size: 0.75rem;">MIDTERM</span>
                        @else
                            <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">CLASS TEST</span>
                        @endif
                    </td>
                    <td class="fw-semibold text-white">{{ $e['course'] }}</td>
                    <td><span class="badge bg-dark border border-secondary">{{ $e['sec'] }}</span></td>
                    <td class="fw-bold text-white"><i class="bi bi-calendar-event me-1 text-indigo"></i> {{ $e['date'] }}</td>
                    <td class="text-indigo fw-semibold">{{ $e['time'] }}</td>
                    <td><span class="badge-custom badge-theory"><i class="bi bi-building me-1"></i> {{ $e['room'] }}</span></td>
                    <td class="text-muted" style="font-size: 0.85rem;">{{ $e['dur'] }}</td>
                    <td><span class="badge-custom badge-active">{{ $e['status'] }}</span></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-glass py-1 px-2" title="Edit"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-glass text-danger py-1 px-2" title="Delete"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Create Examination Modal (Phase 16 Form) -->
<x-modal id="createExamModal" title="Create Examination Schedule">
    <form action="{{ route('exams.index') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label-custom">Examination Type <span class="text-danger">*</span></label>
            <div class="d-flex gap-3">
                <label class="d-flex align-items-center gap-2 cursor-pointer text-white" style="font-size: 0.88rem;">
                    <input type="radio" name="exam_type" value="CT" checked style="accent-color: var(--primary);"> Class Test (CT)
                </label>
                <label class="d-flex align-items-center gap-2 cursor-pointer text-white" style="font-size: 0.88rem;">
                    <input type="radio" name="exam_type" value="Midterm" style="accent-color: var(--primary);"> Midterm Exam
                </label>
                <label class="d-flex align-items-center gap-2 cursor-pointer text-white" style="font-size: 0.88rem;">
                    <input type="radio" name="exam_type" value="Final" style="accent-color: var(--primary);"> Final Examination
                </label>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label class="form-label-custom">Academic Term</label>
                <input type="text" class="form-control form-control-custom" value="Fall 2026" readonly>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom">Examination Date <span class="text-danger">*</span></label>
                <input type="date" name="exam_date" class="form-control form-control-custom" value="2026-11-15" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label class="form-label-custom">Select Course <span class="text-danger">*</span></label>
                <select name="course_code" class="form-select form-select-custom" required>
                    <option value="CSE3201" selected>CSE3201 — Database Systems</option>
                    <option value="CSE3203">CSE3203 — Artificial Intelligence</option>
                    <option value="CSE3205">CSE3205 — Software Engineering</option>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom">Select Section <span class="text-danger">*</span></label>
                <select name="section_id" class="form-select form-select-custom" required>
                    <option value="A" selected>Section A</option>
                    <option value="B">Section B</option>
                    <option value="ALL">All Sections (A & B)</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <label class="form-label-custom">Exam Duration</label>
                <select name="duration" class="form-select form-select-custom">
                    <option value="50">50 Minutes (Class Test)</option>
                    <option value="90">90 Minutes (1.5 Hours)</option>
                    <option value="120">120 Minutes (2.0 Hours)</option>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom">Room Allocation</label>
                <select name="room_id" class="form-select form-select-custom">
                    <option value="AUTO" selected>Auto-Allocate Available Room</option>
                    <option value="101">Room 101 (60 Seats)</option>
                    <option value="102">Room 102 (60 Seats)</option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary">
            <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-indigo">
                <i class="bi bi-cpu-fill me-1"></i> Generate Allocation
            </button>
        </div>
    </form>
</x-modal>

@endsection
