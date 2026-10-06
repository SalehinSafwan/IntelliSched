@extends('layouts.app')

@section('title', 'Teacher Preferences')

@section('content')

<div class="mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h2 class="brand-font fw-bold text-white mb-1">Teacher Course Preferences</h2>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">Configure preference ratings (0-100) used by the weighted scoring engine for teacher-course assignment suitability.</p>
        </div>
        <div>
            <button type="submit" form="preferencesForm" class="btn-indigo">
                <i class="bi bi-floppy-fill me-1"></i> Save Preferences
            </button>
        </div>
    </div>
</div>

<!-- Select Teacher Bar -->
<div class="glass-card mb-4 p-3">
    <div class="row align-items-center g-3">
        <div class="col-12 col-md-3">
            <label class="form-label-custom mb-0 text-white fw-bold">Select Teacher Profile:</label>
        </div>
        <div class="col-12 col-md-6">
            <select class="form-select form-select-custom" onchange="window.location.href='?teacher_id=' + this.value;">
                <option value="T001" selected>T001 — Dr. Ahmed Rahman (CSE Dept)</option>
                <option value="T002">T002 — Dr. Farhana Nusrat (CSE Dept)</option>
                <option value="T003">T003 — Tanvir Hasan (CSE Dept)</option>
                <option value="T004">T004 — Mahmudul Karim (CSE Dept)</option>
            </select>
        </div>
        <div class="col-12 col-md-3 text-md-end">
            <span class="badge bg-indigo p-2" style="font-size: 0.8rem;">
                <i class="bi bi-cpu me-1"></i> Weight Contribution: 25%
            </span>
        </div>
    </div>
</div>

<!-- Preference Form & Matrix -->
<div class="glass-card mb-4">
    <form id="preferencesForm" action="{{ route('teachers.preferences') }}" method="POST">
        @csrf
        
        <div class="table-custom-container border-0">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Type</th>
                        <th style="width: 250px;">Preference Rating (0 - 100)</th>
                        <th>Calculated Suitability</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $courses = [
                            ['code' => 'CSE3201', 'name' => 'Database Systems', 'type' => 'THEORY', 'pref' => 90],
                            ['code' => 'CSE3202', 'name' => 'Database Systems Lab', 'type' => 'LAB', 'pref' => 85],
                            ['code' => 'CSE3203', 'name' => 'Artificial Intelligence', 'type' => 'THEORY', 'pref' => 60],
                            ['code' => 'CSE3205', 'name' => 'Software Engineering', 'type' => 'THEORY', 'pref' => 80],
                            ['code' => 'CSE3206', 'name' => 'Compiler Design', 'type' => 'THEORY', 'pref' => 45],
                        ];
                    @endphp

                    @foreach($courses as $idx => $c)
                        <tr>
                            <td class="fw-bold text-white brand-font">{{ $c['code'] }}</td>
                            <td class="fw-semibold text-white">{{ $c['name'] }}</td>
                            <td>
                                @if($c['type'] === 'THEORY')
                                    <span class="badge-custom badge-theory">Theory</span>
                                @else
                                    <span class="badge-custom badge-lab">Lab</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range" min="0" max="100" value="{{ $c['pref'] }}" 
                                           id="slider_{{ $idx }}" 
                                           oninput="document.getElementById('val_{{ $idx }}').value = this.value; updateSuitability({{ $idx }});"
                                           style="accent-color: var(--accent);">
                                    <input type="number" name="preferences[{{ $c['code'] }}]" class="form-control form-control-custom py-1 px-2 text-center fw-bold text-indigo" 
                                           id="val_{{ $idx }}" value="{{ $c['pref'] }}" min="0" max="100" style="width: 65px !important;"
                                           oninput="document.getElementById('slider_{{ $idx }}').value = this.value; updateSuitability({{ $idx }});">
                                </div>
                            </td>
                            <td>
                                <span id="suitability_tag_{{ $idx }}" class="badge {{ $c['pref'] >= 80 ? 'bg-success' : ($c['pref'] >= 60 ? 'bg-info' : 'bg-warning') }}">
                                    {{ $c['pref'] >= 80 ? 'High Choice' : ($c['pref'] >= 60 ? 'Moderate' : 'Low Preference') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
            <div class="text-muted" style="font-size: 0.85rem;">
                <i class="bi bi-info-circle me-1 text-indigo"></i> Preference ratings higher than 80 increase teacher assignment weight during ILP optimization.
            </div>
            <button type="submit" class="btn-indigo">
                <i class="bi bi-check-circle-fill me-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function updateSuitability(idx) {
        const val = parseInt(document.getElementById('val_' + idx).value) || 0;
        const tag = document.getElementById('suitability_tag_' + idx);
        if (val >= 80) {
            tag.className = 'badge bg-success';
            tag.textContent = 'High Choice';
        } else if (val >= 60) {
            tag.className = 'badge bg-info';
            tag.textContent = 'Moderate';
        } else {
            tag.className = 'badge bg-warning';
            tag.textContent = 'Low Preference';
        }
    }
</script>
@endpush

@endsection
