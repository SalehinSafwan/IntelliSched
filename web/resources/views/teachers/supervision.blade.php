@extends('layouts.app')

@section('title', 'Supervised Sections')

@section('content')

<x-page-header eyebrow="Teacher Portal" title="Supervised Sections"
               subtitle="Sections you advise or supervise. Use them to find periods when you and your students are all free, and to message their CR and ACR.">
    <a href="{{ route('my.free-slots') }}" class="btn-glass"><i class="bi bi-calendar2-check"></i> Free Slot Finder</a>
    <a href="{{ route('messages.index') }}" class="btn-glass"><i class="bi bi-chat-left-text"></i> CR/ACR Messages</a>
</x-page-header>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        @forelse($supervisions as $supervision)
            @php $section = $supervision->section; $sectionReps = $reps[$supervision->section_id] ?? collect(); @endphp
            <div class="glass-card mb-3">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div class="d-flex gap-3">
                        <div class="section-heading mb-0"><span class="section-chip">{{ $section?->name }}</span></div>
                        <div>
                            <div class="text-white fw-bold" style="font-size: 1.02rem;">{{ $section?->label }}</div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                {{ $section?->batch?->program }} · {{ (int) $section?->student_count }} students
                                @if($section && strtoupper($section->status) !== 'ACTIVE') · <x-status-badge :status="$section->status" />@endif
                            </div>
                            @if($supervision->note)
                                <div class="text-muted mt-1" style="font-size: 0.82rem;"><i class="bi bi-sticky me-1"></i>{{ $supervision->note }}</div>
                            @endif
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @forelse($sectionReps as $rep)
                                    <span class="badge-custom badge-gold"><i class="bi bi-person-badge me-1"></i>{{ $rep->class_rep }}: {{ $rep->name }}</span>
                                @empty
                                    <span class="badge-custom badge-inactive">No CR/ACR assigned</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <a href="{{ route('my.free-slots', ['sections' => [$supervision->section_id]]) }}" class="btn-glass btn-sm-custom"><i class="bi bi-calendar2-check"></i> Free slots</a>
                        @if($sectionReps->isNotEmpty())
                            <a href="{{ route('messages.create', ['section' => $supervision->section_id]) }}" class="btn-glass btn-sm-custom"><i class="bi bi-send"></i> Message CR</a>
                        @endif
                        <x-delete-button :action="route('my.supervision.destroy', $supervision)" label="Remove"
                                         :confirm="'Stop supervising ' . $section?->label . '?'" />
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card text-center py-5">
                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                <h4 class="brand-font fw-bold text-white mt-4">No supervised sections yet</h4>
                <p class="text-muted mb-0">Add the batch sections you supervise using the form.</p>
            </div>
        @endforelse
    </div>

    <div class="col-12 col-xl-4">
        <div class="glass-card">
            <div class="section-heading"><span class="section-chip"><i class="bi bi-plus-lg"></i></span><h5 class="brand-font fw-bold text-white mb-0">Add a section</h5></div>
            @if($available->isEmpty())
                <p class="text-muted mb-0" style="font-size: 0.86rem;">You already supervise every active section.</p>
            @else
                <form method="POST" action="{{ route('my.supervision.store') }}">
                    @csrf
                    <label class="form-label-custom" for="section_id">Batch and section *</label>
                    <select id="section_id" name="section_id" class="form-select form-select-custom @error('section_id') is-invalid @enderror" required>
                        <option value="">Choose…</option>
                        @foreach($available as $batchName => $sections)
                            <optgroup label="{{ $batchName }}">
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected((int) old('section_id') === (int) $section->id)>{{ $batchName }} · Section {{ $section->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('section_id')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror

                    <label class="form-label-custom mt-3" for="note">Note</label>
                    <input id="note" name="note" maxlength="255" class="form-control form-control-custom" placeholder="e.g. Thesis group, academic advisor" value="{{ old('note') }}">

                    <button class="btn-indigo w-100 mt-4 justify-content-center"><i class="bi bi-check2"></i> Add Section</button>
                </form>
            @endif
        </div>
    </div>
</div>

@endsection
