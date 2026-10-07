@extends('layouts.app')

@section('title', 'Batches & Sections')

@section('content')

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">Academic Batches & Sections</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage academic student batches, section splitting, and student group counts.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-glass" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-lg"></i> Add Section
        </button>
        <button class="btn-indigo" data-bs-toggle="modal" data-bs-target="#addBatchModal">
            <i class="bi bi-collection-fill"></i> Create New Batch
        </button>
    </div>
</div>

<!-- Batches Grid Tree -->
<div class="row g-4">
    @php
        $batches = [
            [
                'name' => 'CSE 47th Batch',
                'term' => 'Fall 2026',
                'sem'  => '6th Semester',
                'sections' => [
                    ['name' => 'Section A', 'students' => 60, 'rep' => 'Sabbir Ahmed (CR)', 'routine' => 'Generated'],
                    ['name' => 'Section B', 'students' => 60, 'rep' => 'Nusrat Jahan (CR)', 'routine' => 'Generated'],
                ]
            ],
            [
                'name' => 'CSE 48th Batch',
                'term' => 'Fall 2026',
                'sem'  => '5th Semester',
                'sections' => [
                    ['name' => 'Section A', 'students' => 58, 'rep' => 'Tariq Islam (CR)', 'routine' => 'Generated'],
                    ['name' => 'Section B', 'students' => 62, 'rep' => 'Sumaiya Akter (CR)', 'routine' => 'Generated'],
                ]
            ],
            [
                'name' => 'CSE 49th Batch',
                'term' => 'Fall 2026',
                'sem'  => '4th Semester',
                'sections' => [
                    ['name' => 'Section A', 'students' => 65, 'rep' => 'Rakib Hossain (CR)', 'routine' => 'Pending'],
                    ['name' => 'Section B', 'students' => 65, 'rep' => 'Ayesha Siddiqua (CR)', 'routine' => 'Pending'],
                ]
            ]
        ];
    @endphp

    @foreach($batches as $b)
        <div class="col-12 col-lg-6">
            <div class="glass-card">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                    <div>
                        <h4 class="brand-font fw-bold text-white mb-0">{{ $b['name'] }}</h4>
                        <span class="text-muted" style="font-size: 0.82rem;">{{ $b['sem'] }} · Term: {{ $b['term'] }}</span>
                    </div>
                    <span class="badge bg-indigo" style="font-size: 0.8rem; background: var(--primary);">
                        {{ count($b['sections']) }} Sections
                    </span>
                </div>

                <div class="d-flex flex-column gap-3">
                    @foreach($b['sections'] as $sec)
                        <div class="p-3 rounded-4 d-flex align-items-center justify-content-between" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold text-white mb-0">{{ $b['name'] }} — {{ $sec['name'] }}</h6>
                                    @if($sec['routine'] === 'Generated')
                                        <span class="badge-custom badge-active" style="font-size: 0.68rem;">✓ Routine Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">Routine Pending</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <i class="bi bi-people-fill text-indigo me-1"></i> {{ $sec['students'] }} Students · CR: {{ $sec['rep'] }}
                                </div>
                            </div>

                            <a href="{{ route('sections.show', ['section' => 1]) }}" class="btn btn-sm btn-glass text-indigo py-1 px-3" style="font-size: 0.82rem;">
                                Manage Section <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Add Batch Modal -->
<x-modal id="addBatchModal" title="Create Academic Batch">
    <form action="{{ route('batches.index') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label-custom">Batch Name</label>
            <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. CSE 50th Batch" required>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">Current Semester</label>
            <input type="text" name="semester" class="form-control form-control-custom" placeholder="e.g. 1st Semester" required>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-indigo">Create Batch</button>
        </div>
    </form>
</x-modal>

<!-- Add Section Modal -->
<x-modal id="addSectionModal" title="Add Section to Batch">
    <form action="{{ route('batches.index') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label-custom">Select Batch</label>
            <select name="batch_id" class="form-select form-select-custom">
                <option value="1">CSE 47th Batch</option>
                <option value="2">CSE 48th Batch</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">Section Name</label>
            <input type="text" name="section_name" class="form-control form-control-custom" placeholder="e.g. Section C" required>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">Student Count</label>
            <input type="number" name="student_count" class="form-control form-control-custom" value="60" required>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-indigo">Add Section</button>
        </div>
    </form>
</x-modal>

@endsection
