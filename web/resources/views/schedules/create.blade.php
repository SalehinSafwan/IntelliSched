@extends('layouts.app')

@section('title', 'Generate Routine')

@section('content')

<div class="mb-4">
    <a href="{{ route('schedules.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to Routines</a>
    <div class="page-eyebrow mt-3">Google OR-Tools · CP-SAT</div>
    <h2 class="brand-font fw-bold text-gradient mt-1 mb-1">Routine Generator</h2>
    <p class="text-muted mb-0" style="font-size: 0.88rem;">
        Generates a conflict-free weekly routine. You will review the result first — nothing is saved until you approve it.
    </p>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="glass-card h-100">
            <div class="section-heading mb-4">
                <span class="stat-icon-wrapper stat-icon-primary" style="width: 38px; height: 38px; font-size: 1.1rem; border-radius: 11px;"><i class="bi bi-sliders2"></i></span>
                <h5 class="brand-font fw-bold text-white mb-0">Choose what to schedule</h5>
            </div>

            <form method="POST" action="{{ route('schedules.generate') }}" id="generateForm">
                @csrf

                <div class="mb-4">
                    <label class="form-label-custom" for="academic_term_id">Academic Term <span class="text-danger">*</span></label>
                    <select id="academic_term_id" name="academic_term_id" class="form-select form-select-custom" required>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" @selected(old('academic_term_id') == $term->id)>
                                {{ $term->name }}{{ $term->status === 'ACTIVE' ? ' — Active Term' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label-custom" for="batch_id">Target Academic Batch <span class="text-danger">*</span></label>
                    <select id="batch_id" name="batch_id" class="form-select form-select-custom" required>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>
                                {{ $batch->name }} — {{ $batch->program }}
                                ({{ $batch->sections->count() }} {{ Str::plural('section', $batch->sections->count()) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-indigo w-100 py-3" id="generateButton">
                    <i class="bi bi-cpu"></i> Generate Routine
                </button>
                <p class="text-dim text-center mt-3 mb-0" style="font-size: 0.78rem;">
                    Solving usually takes a few seconds and never more than 30.
                </p>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="glass-card h-100">
            <div class="section-heading mb-3">
                <span class="stat-icon-wrapper stat-icon-success" style="width: 38px; height: 38px; font-size: 1.1rem; border-radius: 11px;"><i class="bi bi-shield-check"></i></span>
                <h5 class="brand-font fw-bold text-white mb-0">Hard constraints</h5>
            </div>
            <ul class="check-list">
                <li><i class="bi bi-check-circle-fill"></i> No teacher, room or section is double-booked — including against other batches' published routines.</li>
                <li><i class="bi bi-check-circle-fill"></i> Each course is taught by a pair of teachers, shared by every section of the batch.</li>
                <li><i class="bi bi-check-circle-fill"></i> Theory: each teacher takes one class; the third is combined, keeping both teachers free.</li>
                <li><i class="bi bi-check-circle-fill"></i> Labs run three consecutive periods in an eligible lab room, conducted jointly by the pair.</li>
                <li><i class="bi bi-check-circle-fill"></i> Availability and weekly period limits are respected; a theory course meets a section at most once a day.</li>
            </ul>

            <div class="info-panel mt-4">
                <div class="fw-semibold text-white mb-1" style="font-size: 0.86rem;"><i class="bi bi-stars text-gold me-1"></i> Optimised for</div>
                <div class="text-muted" style="font-size: 0.82rem;">
                    Suitable teacher pairs, balanced workload, preferred rooms, compact days without gaps, and morning or afternoon labs.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Prevent duplicate submissions while the solver runs.
    document.getElementById('generateForm').addEventListener('submit', function () {
        const button = document.getElementById('generateButton');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Solving… please wait';
    });
</script>
@endpush

@endsection
