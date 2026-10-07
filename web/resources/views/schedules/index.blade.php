@extends('layouts.app')

@section('title', 'Routine Visualization')

@section('content')

@php
    $currentView = request('view', 'section');
    $filterVal   = request('filter', ($currentView === 'teacher' ? 'T001' : ($currentView === 'room' ? '101' : 'CSE47-A')));
@endphp

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Academic Timetable Routine</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Weekly schedule visualization with section, teacher, and room perspective filters.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-glass" onclick="window.print();">
            <i class="bi bi-printer me-1"></i> Print Routine
        </button>
        <a href="{{ route('schedules.create') }}" class="btn-indigo">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate Routine
        </a>
    </div>
</div>

<!-- Perspective Filter Toolbar (Phase 14) -->
<div class="glass-card mb-4 p-3">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-4">
            <div class="nav nav-pills custom-pills p-1 rounded-3" style="background: rgba(15, 20, 35, 0.7); border: 1px solid var(--border-color);">
                <a class="nav-link text-center w-33 {{ $currentView === 'section' ? 'active bg-indigo text-white fw-semibold' : 'text-muted' }}" 
                   href="?view=section&filter=CSE47-A" style="font-size: 0.82rem; border-radius: 8px;">
                    <i class="bi bi-collection me-1"></i> Section View
                </a>
                <a class="nav-link text-center w-33 {{ $currentView === 'teacher' ? 'active bg-indigo text-white fw-semibold' : 'text-muted' }}" 
                   href="?view=teacher&filter=T001" style="font-size: 0.82rem; border-radius: 8px;">
                    <i class="bi bi-person-video3 me-1"></i> Teacher View
                </a>
                <a class="nav-link text-center w-33 {{ $currentView === 'room' ? 'active bg-indigo text-white fw-semibold' : 'text-muted' }}" 
                   href="?view=room&filter=101" style="font-size: 0.82rem; border-radius: 8px;">
                    <i class="bi bi-building me-1"></i> Room View
                </a>
            </div>
        </div>

        <div class="col-12 col-md-5">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label-custom mb-0 text-white flex-shrink-0">Filter Selection:</label>
                <select class="form-select form-select-custom" onchange="window.location.href='?view={{ $currentView }}&filter=' + this.value;">
                    @if($currentView === 'section')
                        <option value="CSE47-A" {{ $filterVal === 'CSE47-A' ? 'selected' : '' }}>CSE 47 — Section A (60 Students)</option>
                        <option value="CSE47-B" {{ $filterVal === 'CSE47-B' ? 'selected' : '' }}>CSE 47 — Section B (60 Students)</option>
                        <option value="CSE48-A" {{ $filterVal === 'CSE48-A' ? 'selected' : '' }}>CSE 48 — Section A (58 Students)</option>
                    @elseif($currentView === 'teacher')
                        <option value="T001" {{ $filterVal === 'T001' ? 'selected' : '' }}>Dr. Ahmed Rahman (Database Systems)</option>
                        <option value="T002" {{ $filterVal === 'T002' ? 'selected' : '' }}>Dr. Farhana Nusrat (Artificial Intelligence)</option>
                        <option value="T003" {{ $filterVal === 'T003' ? 'selected' : '' }}>Tanvir Hasan (Software Engineering)</option>
                    @else
                        <option value="101" {{ $filterVal === '101' ? 'selected' : '' }}>Room 101 — Lecture Hall (Theory · 60 Seats)</option>
                        <option value="102" {{ $filterVal === '102' ? 'selected' : '' }}>Room 102 — Lecture Hall (Theory · 60 Seats)</option>
                        <option value="LAB-01" {{ $filterVal === 'LAB-01' ? 'selected' : '' }}>LAB-01 — Database & Software Lab (30 Seats)</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="col-12 col-md-3 text-md-end">
            <span class="badge bg-success p-2" style="font-size: 0.8rem;">
                <i class="bi bi-shield-check me-1"></i> Conflict Free Routine
            </span>
        </div>
    </div>
</div>

<!-- Master Timetable Grid (Phase 13) -->
<div class="timetable-container mb-4">
    <table class="timetable-grid table-borderless">
        <thead>
            <tr>
                <th style="width: 100px;">TIME</th>
                <th>SUNDAY</th>
                <th>MONDAY</th>
                <th>TUESDAY</th>
                <th>WEDNESDAY</th>
                <th>THURSDAY</th>
            </tr>
        </thead>
        <tbody>
            @php
                $timeSlots = [
                    '08:00 AM',
                    '08:50 AM',
                    '09:40 AM',
                    '10:25 AM',
                    '11:15 AM',
                    '12:05 PM',
                    '12:50 PM',
                    '01:40 PM',
                    '02:30 PM',
                ];

                // Simulated timetable schedule matrix
                // Days: 0=Sun, 1=Mon, 2=Tue, 3=Wed, 4=Thu
            @endphp

            @foreach($timeSlots as $slotIdx => $slotLabel)
                @if($slotIdx === 2 || $slotIdx === 5)
                    <tr>
                        <td class="fw-semibold text-muted text-center" style="font-size: 0.78rem;">{{ $slotLabel }}</td>
                        <td colspan="5" class="break-cell">
                            ☕ BREAK / RECESS PERIOD (45 MINS)
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="fw-semibold text-white text-center align-middle" style="font-size: 0.82rem;">
                            {{ $slotLabel }}
                        </td>

                        <!-- Sunday Slot -->
                        <td>
                            @if($slotIdx === 0)
                                <div class="timetable-card" onclick="openClassModal('CSE3201', 'Database Systems', 'Dr. Ahmed', 'Room 101', 'Theory', '08:00 AM - 08:50 AM')">
                                    <div class="course-code">CSE3201 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 1)
                                <div class="timetable-card" onclick="openClassModal('CSE3201', 'Database Systems', 'Dr. Ahmed', 'Room 101', 'Theory', '08:50 AM - 09:40 AM')">
                                    <div class="course-code">CSE3201 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 3 || $slotIdx === 4)
                                <div class="timetable-card" onclick="openClassModal('CSE3205', 'Software Engineering', 'Tanvir Hasan', 'Room 101', 'Theory', '10:25 AM')">
                                    <div class="course-code">CSE3205 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Tanvir Hasan</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 6)
                                <div class="timetable-card" onclick="openClassModal('CSE3203', 'Artificial Intelligence', 'Dr. Farhana', 'Room 102', 'Theory', '12:50 PM')">
                                    <div class="course-code">CSE3203 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Farhana</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 102</div>
                                </div>
                            @endif
                        </td>

                        <!-- Monday Slot -->
                        <td>
                            @if($slotIdx === 0 || $slotIdx === 1)
                                <div class="timetable-card" onclick="openClassModal('CSE3205', 'Software Engineering', 'Tanvir Hasan', 'Room 101', 'Theory', '08:00 AM')">
                                    <div class="course-code">CSE3205 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Tanvir Hasan</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 3 || $slotIdx === 4 || $slotIdx === 6)
                                <div class="timetable-card lab-type" onclick="openClassModal('CSE3202', 'Database Systems Lab', 'Dr. Ahmed', 'LAB-01', 'Lab', '10:25 AM - 01:40 PM')">
                                    <div class="course-code">CSE3202 LAB <span class="badge bg-dark" style="font-size: 0.65rem;">Lab</span></div>
                                    <div class="teacher-name"><i class="bi bi-pc-display"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> LAB-01</div>
                                </div>
                            @endif
                        </td>

                        <!-- Tuesday Slot -->
                        <td>
                            @if($slotIdx === 0 || $slotIdx === 1)
                                <div class="timetable-card" onclick="openClassModal('CSE3203', 'Artificial Intelligence', 'Dr. Farhana', 'Room 102', 'Theory', '08:00 AM')">
                                    <div class="course-code">CSE3203 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Farhana</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 102</div>
                                </div>
                            @elseif($slotIdx === 3 || $slotIdx === 4)
                                <div class="timetable-card" onclick="openClassModal('CSE3205', 'Software Engineering', 'Tanvir Hasan', 'Room 101', 'Theory', '10:25 AM')">
                                    <div class="course-code">CSE3205 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Tanvir Hasan</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 6 || $slotIdx === 7)
                                <div class="timetable-card" onclick="openClassModal('CSE3201', 'Database Systems', 'Dr. Ahmed', 'Room 101', 'Theory', '12:50 PM')">
                                    <div class="course-code">CSE3201 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @endif
                        </td>

                        <!-- Wednesday Slot -->
                        <td>
                            @if($slotIdx === 0 || $slotIdx === 1)
                                <div class="timetable-card" onclick="openClassModal('CSE3201', 'Database Systems', 'Dr. Ahmed', 'Room 101', 'Theory', '08:00 AM')">
                                    <div class="course-code">CSE3201 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 3 || $slotIdx === 4)
                                <div class="timetable-card" onclick="openClassModal('CSE3203', 'Artificial Intelligence', 'Dr. Farhana', 'Room 102', 'Theory', '10:25 AM')">
                                    <div class="course-code">CSE3203 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Farhana</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 102</div>
                                </div>
                            @endif
                        </td>

                        <!-- Thursday Slot -->
                        <td>
                            @if($slotIdx === 0 || $slotIdx === 1)
                                <div class="timetable-card" onclick="openClassModal('CSE3205', 'Software Engineering', 'Tanvir Hasan', 'Room 101', 'Theory', '08:00 AM')">
                                    <div class="course-code">CSE3205 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Tanvir Hasan</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @elseif($slotIdx === 3 || $slotIdx === 4)
                                <div class="timetable-card" onclick="openClassModal('CSE3201', 'Database Systems', 'Dr. Ahmed', 'Room 101', 'Theory', '10:25 AM')">
                                    <div class="course-code">CSE3201 <span class="badge bg-dark" style="font-size: 0.65rem;">Theory</span></div>
                                    <div class="teacher-name"><i class="bi bi-person"></i> Dr. Ahmed</div>
                                    <div class="room-badge"><i class="bi bi-geo-alt"></i> Room 101</div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>

<!-- Class Details Modal -->
<x-modal id="classDetailsModal" title="Class Session Specification">
    <div class="d-flex flex-column gap-3" style="font-size: 0.9rem;">
        <div class="p-3 rounded" style="background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3);">
            <div class="text-muted" style="font-size: 0.75rem;">COURSE TITLE</div>
            <div class="h5 fw-bold text-white mb-0" id="modalCourseTitle">Database Systems</div>
            <div class="text-indigo fw-semibold" id="modalCourseCode">CSE3201</div>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Assigned Teacher:</span>
            <span class="fw-bold text-white" id="modalTeacher">Dr. Ahmed Rahman</span>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Allocated Room:</span>
            <span class="fw-bold text-white" id="modalRoom">Room 101 (Lecture Hall)</span>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
            <span class="text-muted">Time Interval:</span>
            <span class="fw-bold text-indigo" id="modalTime">08:00 AM - 08:50 AM</span>
        </div>

        <div class="d-flex justify-content-between py-2">
            <span class="text-muted">Class Type:</span>
            <span class="badge bg-success" id="modalType">Theory</span>
        </div>
    </div>
    <x-slot:footer>
        <button type="button" class="btn-glass" data-bs-dismiss="modal">Close</button>
    </x-slot:footer>
</x-modal>

@push('scripts')
<script>
    function openClassModal(code, title, teacher, room, type, time) {
        document.getElementById('modalCourseCode').textContent = code;
        document.getElementById('modalCourseTitle').textContent = title;
        document.getElementById('modalTeacher').textContent = teacher;
        document.getElementById('modalRoom').textContent = room;
        document.getElementById('modalTime').textContent = time;
        document.getElementById('modalType').textContent = type;

        const myModal = new bootstrap.Modal(document.getElementById('classDetailsModal'));
        myModal.show();
    }
</script>
@endpush

@endsection
