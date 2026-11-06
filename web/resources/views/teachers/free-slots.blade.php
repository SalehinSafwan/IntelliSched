@extends('layouts.app')

@section('title', 'Free Slot Finder')

@section('content')

<x-page-header :back="route('my.supervision')" back-label="Supervised Sections" eyebrow="Teacher Portal" title="Free Slot Finder"
               subtitle="Periods when you and every selected section are free in the published routines. Use them for consultations, make-up classes or project meetings." />

@if($sections->isEmpty())
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-calendar2-x"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No sections to compare with</h4>
        <p class="text-muted">Add a supervised section first. Sections you teach in a published routine also appear here.</p>
        <a href="{{ route('my.supervision') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> Add Supervised Section</a>
    </div>
@else
    <form method="GET" action="{{ route('my.free-slots') }}" class="glass-card mb-4">
        <div class="form-section-title mb-2">Compare my week with</div>
        <div class="choice-grid">
            @foreach($sections as $section)
                <label class="choice-card">
                    <input type="checkbox" name="sections[]" value="{{ $section->id }}" @checked(in_array((int) $section->id, $selected, true))>
                    <span class="choice-body">
                        <span class="choice-title">{{ $section->label }}</span>
                        <span class="choice-sub">
                            {{ $section->supervises ? 'Supervised' : '' }}{{ $section->supervises && $section->teaches ? ' · ' : '' }}{{ $section->teaches ? 'You teach here' : '' }}
                            · {{ (int) $section->student_count }} students
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn-indigo"><i class="bi bi-search"></i> Find Common Free Periods</button>
        </div>
    </form>

    @if($result)
        @php $summary = $result['summary']; @endphp
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="metric-tile"><div class="metric-label">Common free periods</div><div class="metric-value text-emerald">{{ $summary['free'] }} <span class="text-muted" style="font-size: 0.85rem;">/ {{ $summary['teaching'] }}</span></div></div></div>
            <div class="col-6 col-lg-3"><div class="metric-tile"><div class="metric-label">Best day</div><div class="metric-value">{{ $summary['best_day'] ? ucfirst(strtolower($summary['best_day'])) : '—' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="metric-tile"><div class="metric-label">Longest free run</div><div class="metric-value">{{ collect($summary['per_day'])->max('longest') ?? 0 }} <span class="text-muted" style="font-size: 0.85rem;">periods</span></div></div></div>
            <div class="col-6 col-lg-3"><div class="metric-tile"><div class="metric-label">Days fit for a 3-period lab</div><div class="metric-value">{{ count($summary['lab_ready_days']) }}</div></div></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div class="pref-legend">
                <span><i class="legend-free"></i> Everyone free</span>
                <span><i class="legend-teacher"></i> You are busy</span>
                <span><i class="legend-students"></i> Students are busy</span>
                <span><i class="legend-both"></i> Both busy</span>
                <span><i class="bi bi-exclamation-circle" style="width: auto; height: auto; color: #fcd34d;"></i> A period you prefer to avoid</span>
            </div>
            <span class="text-dim" style="font-size: 0.78rem;">Click a free period to tell the CR/ACR about an extra class.</span>
        </div>

        <div class="timetable-container mb-4">
            <table class="pref-grid">
                <thead>
                    <tr>
                        <th style="width: 120px;">TIME</th>
                        @foreach($result['days'] as $day)
                            <th>
                                {{ $day }}
                                <div style="font-size: 0.62rem; letter-spacing: 0.04em; font-weight: 600; color: #6ee7b7;">{{ $summary['per_day'][$day]['free'] }} free</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($result['cells'] as $number => $byDay)
                        <tr>
                            <td class="time-cell">{{ $result['rows'][$number] }}</td>
                            @foreach($result['days'] as $day)
                                @php $cell = $byDay[$day]; @endphp
                                @if($cell['type'] === 'none')
                                    <td style="opacity: 0.2;"></td>
                                @elseif($cell['type'] === 'break')
                                    <td class="slot-cell is-break">BREAK</td>
                                @else
                                    @php
                                        $tip = collect([
                                            $cell['time'],
                                            $cell['teacher'] ? 'You: ' . $cell['teacher'] : 'You: free',
                                            $cell['sections'] ? 'Students: ' . implode(', ', $cell['sections']) : 'Students: free',
                                            $cell['state'] === 'free' ? $cell['free_rooms'] . ' room(s) empty' : null,
                                            $cell['avoid'] ? 'You prefer to avoid this period' : null,
                                        ])->filter()->implode("\n");
                                    @endphp
                                    <td class="slot-cell" data-state="{{ $cell['state'] }}" title="{{ $tip }}">
                                        @if($cell['avoid'])<i class="bi bi-exclamation-circle slot-flag"></i>@endif
                                        @if($cell['state'] === 'free')
                                            @if(count($selected) === 1)
                                                <a class="slot-action" href="{{ route('messages.create', ['section' => $selected[0], 'category' => 'EXTRA_CLASS', 'slot' => $cell['slot_id']]) }}">
                                                    FREE<span class="slot-sub">{{ $cell['free_rooms'] }} room(s) empty</span>
                                                </a>
                                            @else
                                                FREE<span class="slot-sub">{{ $cell['free_rooms'] }} room(s) empty</span>
                                            @endif
                                        @elseif($cell['state'] === 'teacher')
                                            {{ $cell['teacher'] && str_starts_with($cell['teacher'], 'You marked') ? 'UNAVAILABLE' : 'YOUR CLASS' }}
                                            <span class="slot-sub">{{ \Illuminate\Support\Str::limit($cell['teacher'], 26) }}</span>
                                        @elseif($cell['state'] === 'students')
                                            STUDENTS BUSY<span class="slot-sub">{{ \Illuminate\Support\Str::limit(implode(', ', $cell['sections']), 26) }}</span>
                                        @else
                                            BOTH BUSY
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($summary['lab_ready_days'])
            <div class="info-panel">
                <i class="bi bi-lightbulb text-gold me-2"></i>
                <span class="text-muted" style="font-size: 0.86rem;">
                    Three or more back-to-back common free periods (enough for a make-up lab) on:
                    <span class="text-white fw-semibold">{{ collect($summary['lab_ready_days'])->map(fn ($d) => ucfirst(strtolower($d)))->implode(', ') }}</span>.
                </span>
            </div>
        @endif
    @endif
@endif

@endsection
