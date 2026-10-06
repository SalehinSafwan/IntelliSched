@extends('layouts.app')

@section('title', 'Time Slots Management')

@section('content')

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Academic Time Slots</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Define daily period durations, start/end times, and mandatory break intervals.</p>
    </div>
    <div>
        <button class="btn-indigo" data-bs-toggle="modal" data-bs-target="#addSlotModal">
            <i class="bi bi-clock-history me-1"></i> Add Time Slot
        </button>
    </div>
</div>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Period #</th>
                <th>Time Interval</th>
                <th>Duration</th>
                <th>Period Type</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $slots = [
                    ['period' => 1, 'time' => '08:00 AM - 08:50 AM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                    ['period' => 2, 'time' => '08:50 AM - 09:40 AM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                    ['period' => 3, 'time' => '09:40 AM - 10:25 AM', 'dur' => '45 Mins', 'type' => 'BREAK',   'status' => 'ACTIVE'],
                    ['period' => 4, 'time' => '10:25 AM - 11:15 AM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                    ['period' => 5, 'time' => '11:15 AM - 12:05 PM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                    ['period' => 6, 'time' => '12:05 PM - 12:50 PM', 'dur' => '45 Mins', 'type' => 'BREAK',   'status' => 'ACTIVE'],
                    ['period' => 7, 'time' => '12:50 PM - 01:40 PM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                    ['period' => 8, 'time' => '01:40 PM - 02:30 PM', 'dur' => '50 Mins', 'type' => 'LECTURE', 'status' => 'ACTIVE'],
                ];
            @endphp

            @foreach($slots as $s)
                <tr>
                    <td class="fw-bold text-white brand-font">Slot {{ $s['period'] }}</td>
                    <td class="fw-semibold text-white">{{ $s['time'] }}</td>
                    <td class="text-muted">{{ $s['dur'] }}</td>
                    <td>
                        @if($s['type'] === 'LECTURE')
                            <span class="badge-custom badge-theory">Regular Session</span>
                        @else
                            <span class="badge bg-warning text-dark">☕ Recess / Break</span>
                        @endif
                    </td>
                    <td><span class="badge-custom badge-active">Active</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-glass py-1 px-2 me-1"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Add Time Slot Modal -->
<x-modal id="addSlotModal" title="Add Academic Time Slot">
    <form action="{{ route('timeslots.index') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label-custom">Period Number</label>
            <input type="number" name="period_number" class="form-control form-control-custom" value="9" required>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label-custom">Start Time</label>
                <input type="time" name="start_time" class="form-control form-control-custom" required>
            </div>
            <div class="col-6">
                <label class="form-label-custom">End Time</label>
                <input type="time" name="end_time" class="form-control form-control-custom" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">Slot Type</label>
            <select name="type" class="form-select form-select-custom">
                <option value="LECTURE">Lecture Session</option>
                <option value="BREAK">Recess / Break</option>
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-indigo">Save Slot</button>
        </div>
    </form>
</x-modal>

@endsection
