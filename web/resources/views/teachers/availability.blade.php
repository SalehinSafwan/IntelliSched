@extends('layouts.app')

@section('title', 'Teacher Availability Schedule')

@section('content')

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h2 class="brand-font fw-bold text-white mb-1">Teacher Availability Timetable</h2>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">Click individual time slots to toggle between Available (✓) and Unavailable (✕) states.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-glass" onclick="setAllAvailable()">Set All Available</button>
            <button type="submit" form="availabilityForm" class="btn-indigo">
                <i class="bi bi-floppy-fill me-1"></i> Save Availability
            </button>
        </div>
    </div>
</div>

<!-- Active Teacher Selector Bar -->
<div class="glass-card mb-4 p-3">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-3">
            <label class="form-label-custom mb-0 text-white fw-bold">Active Teacher Profile:</label>
        </div>
        <div class="col-12 col-md-6">
            <select class="form-select form-select-custom" onchange="window.location.href='?teacher_id=' + this.value;">
                <option value="T001" selected>Dr. Ahmed Rahman (Prof · CSE)</option>
                <option value="T002">Dr. Farhana Nusrat (Assoc Prof · CSE)</option>
                <option value="T003">Tanvir Hasan (Asst Prof · CSE)</option>
            </select>
        </div>
        <div class="col-12 col-md-3 text-md-end">
            <div class="d-inline-flex gap-2" style="font-size: 0.8rem;">
                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Available</span>
                <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i> Unavailable</span>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Timetable Grid Form -->
<form id="availabilityForm" action="{{ route('teachers.availability', ['teacher' => 'T001']) }}" method="POST">
    @csrf

    <div class="timetable-container mb-4">
        <table class="table table-dark table-bordered align-middle text-center mb-0" style="border-color: var(--border-color);">
            <thead>
                <tr style="background: rgba(255,255,255,0.04);">
                    <th style="width: 120px;" class="py-3 text-muted">Time Slot</th>
                    <th class="py-3 text-white">Sunday</th>
                    <th class="py-3 text-white">Monday</th>
                    <th class="py-3 text-white">Tuesday</th>
                    <th class="py-3 text-white">Wednesday</th>
                    <th class="py-3 text-white">Thursday</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $slots = [
                        '08:00 - 08:50',
                        '08:50 - 09:40',
                        '09:40 - 10:25 (Break)',
                        '10:25 - 11:15',
                        '11:15 - 12:05',
                        '12:05 - 12:50 (Break)',
                        '12:50 - 13:40',
                        '13:40 - 14:30',
                        '14:30 - 15:20',
                    ];
                    $days = ['sun', 'mon', 'tue', 'wed', 'thu'];
                @endphp

                @foreach($slots as $sIndex => $slotTime)
                    @if(str_contains($slotTime, 'Break'))
                        <tr style="background: rgba(255,255,255,0.02);">
                            <td class="fw-semibold text-muted" style="font-size: 0.8rem;">{{ $slotTime }}</td>
                            <td colspan="5" class="text-center text-dim font-monospace" style="font-size: 0.78rem; letter-spacing: 0.1em;">
                                ☕ RECESS / BREAK PERIOD
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td class="fw-semibold text-white" style="font-size: 0.85rem; background: rgba(255,255,255,0.02);">
                                {{ $slotTime }}
                            </td>
                            @foreach($days as $day)
                                @php
                                    // Mock unavailablity for Tuesday 08:50 and Mon 11:15
                                    $isAvail = !($day === 'tue' && $sIndex === 1) && !($day === 'mon' && $sIndex === 4);
                                @endphp
                                <td class="avail-cell {{ $isAvail ? 'available' : 'unavailable' }}" 
                                    id="cell_{{ $day }}_{{ $sIndex }}"
                                    onclick="toggleCell('{{ $day }}', {{ $sIndex }})">
                                    <input type="hidden" name="avail[{{ $day }}][{{ $sIndex }}]" id="input_{{ $day }}_{{ $sIndex }}" value="{{ $isAvail ? '1' : '0' }}">
                                    <span id="text_{{ $day }}_{{ $sIndex }}" class="fw-bold" style="font-size: 0.88rem;">
                                        {{ $isAvail ? '✓ Available' : '✕ Unavailable' }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end gap-3">
        <a href="{{ route('teachers.show', ['teacher' => 'T001']) }}" class="btn-glass">Cancel</a>
        <button type="submit" class="btn-indigo">
            <i class="bi bi-check-lg me-1"></i> Save Timetable Availability
        </button>
    </div>
</form>

@push('scripts')
<script>
    function toggleCell(day, slotIdx) {
        const cell = document.getElementById(`cell_${day}_${slotIdx}`);
        const input = document.getElementById(`input_${day}_${slotIdx}`);
        const text = document.getElementById(`text_${day}_${slotIdx}`);

        if (input.value === '1') {
            input.value = '0';
            cell.className = 'avail-cell unavailable';
            text.textContent = '✕ Unavailable';
        } else {
            input.value = '1';
            cell.className = 'avail-cell available';
            text.textContent = '✓ Available';
        }
    }

    function setAllAvailable() {
        document.querySelectorAll('.avail-cell').forEach(cell => {
            const input = cell.querySelector('input');
            const text = cell.querySelector('span');
            input.value = '1';
            cell.className = 'avail-cell available';
            text.textContent = '✓ Available';
        });
    }
</script>
@endpush

@endsection
