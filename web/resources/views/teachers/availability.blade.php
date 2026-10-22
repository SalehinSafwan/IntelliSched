@extends('layouts.app')

@section('title', $self ? 'My Availability' : 'Availability — ' . $teacher->display_name)

@section('content')

@php
    $labels = ['preferred' => 'Preferred', 'available' => 'Available', 'avoid' => 'Avoid', 'unavailable' => 'Unavailable'];
@endphp

<x-page-header :back="$self ? route('dashboard') : route('teachers.show', $teacher)"
               :back-label="$self ? 'Dashboard' : $teacher->display_name"
               eyebrow="Availability & time preferences"
               :title="$self ? 'My Weekly Availability' : $teacher->display_name"
               subtitle="Click a period to cycle: Preferred → Available → Avoid → Unavailable. Unavailable is never scheduled; Preferred and Avoid guide the generator.">
</x-page-header>

<form method="POST" action="{{ $self ? route('my.availability.update') : route('teachers.availability.update', $teacher) }}">
    @csrf
    @method('PUT')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="pref-legend">
            <span><i style="background: rgba(16,185,129,0.55);"></i> Preferred</span>
            <span><i style="background: rgba(99,102,241,0.45);"></i> Available</span>
            <span><i style="background: rgba(245,158,11,0.5);"></i> Avoid if possible</span>
            <span><i style="background: rgba(239,68,68,0.45);"></i> Unavailable</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="text-muted align-self-center" style="font-size: 0.8rem;">Fill all:</span>
            @foreach($labels as $state => $label)
                <button type="button" class="btn-glass btn-sm-custom js-fill" data-state="{{ $state }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="timetable-container mb-4">
        <table class="pref-grid">
            <thead>
                <tr>
                    <th style="width: 120px;">TIME</th>
                    @foreach($days as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $number => $slots)
                    @php $byDay = $slots->keyBy(fn ($s) => strtoupper($s->day)); $first = $slots->first(); @endphp
                    <tr>
                        <td class="time-cell">{{ $first->start_time }} – {{ $first->end_time }}</td>
                        @foreach($days as $day)
                            @php $slot = $byDay->get($day); @endphp
                            @if(!$slot)
                                <td style="opacity: 0.2;"></td>
                            @elseif($slot->is_break)
                                <td class="pref-cell is-break">BREAK</td>
                            @else
                                @php $state = $states[$slot->id]; @endphp
                                <td class="pref-cell js-cell" data-state="{{ $state }}" title="{{ $day }} {{ $slot->start_time }}–{{ $slot->end_time }}">
                                    <span class="js-label">{{ $labels[$state] }}</span>
                                    <input type="hidden" name="cells[{{ $slot->id }}]" value="{{ $state }}">
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ $self ? route('dashboard') : route('teachers.show', $teacher) }}" class="btn-glass">Cancel</a>
        <button class="btn-indigo"><i class="bi bi-check2"></i> Save Availability</button>
    </div>
</form>

@push('scripts')
<script>
    const order = ['preferred', 'available', 'avoid', 'unavailable'];
    const labels = @json($labels);

    function setState(cell, state) {
        cell.dataset.state = state;
        cell.querySelector('input').value = state;
        cell.querySelector('.js-label').textContent = labels[state];
    }

    document.querySelectorAll('.js-cell').forEach(function (cell) {
        cell.addEventListener('click', function () {
            const next = order[(order.indexOf(cell.dataset.state) + 1) % order.length];
            setState(cell, next);
        });
    });

    document.querySelectorAll('.js-fill').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.js-cell').forEach(cell => setState(cell, button.dataset.state));
        });
    });
</script>
@endpush

@endsection
