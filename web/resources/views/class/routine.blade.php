@extends('layouts.app')

@section('title', 'My Routine')

@section('content')

@php
    $isRep = $student->isClassRep();
    $stateLabels = ['done' => 'Done', 'now' => 'In progress', 'next' => 'Up next'];
    $time = fn ($t) => \Carbon\Carbon::createFromFormat('H:i', substr(strlen($t) === 4 ? '0' . $t : $t, 0, 5))->format('g:i A');
    $dayLabel = ucfirst(strtolower($plan['day']));
    $when = $plan['is_today'] ? 'Today' : ($plan['date']->isTomorrow() ? 'Tomorrow' : $dayLabel);
@endphp

<x-page-header :eyebrow="$student->section?->label ?? 'Student Portal'" title="My Routine"
               :subtitle="'Your section\'s classes from the published routine, with any approved changes.' . ($isRep ? ' Copy or share the routine with your class in one tap.' : '')">
    @if($isRep)
        <a href="{{ route('class.notices') }}" class="btn-glass"><i class="bi bi-megaphone"></i> Notice Board</a>
    @endif
    <a href="{{ route('schedules.index') }}" class="btn-glass"><i class="bi bi-calendar3"></i> Full Timetable</a>
</x-page-header>

@if(!$student->section_id)
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-person-exclamation"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No section linked</h4>
        <p class="text-muted mb-0">Your account is not linked to a section yet. Ask the administrator to set it.</p>
    </div>
@else

{{-- Day picker --}}
<nav class="day-tabs mb-4" aria-label="Choose a day">
    @foreach($plan['days'] as $d)
        <a href="{{ route('class.routine', ['day' => $d['day']]) }}"
           class="day-tab {{ $d['day'] === $plan['day'] ? 'active' : '' }}"
           @if($d['day'] === $plan['day']) aria-current="page" @endif>
            <span class="d-name"><span class="d-none d-md-inline">{{ $d['label'] }}</span><span class="d-md-none">{{ $d['short'] }}</span></span>
            <span class="d-meta">{{ $d['is_today'] ? 'Today' : $d['count'] . ' ' . \Illuminate\Support\Str::plural('class', $d['count']) }}</span>
        </a>
    @endforeach
</nav>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="glass-card">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3">
                <h5 class="brand-font fw-bold text-white mb-0">{{ $when }}'s classes</h5>
                <span class="message-meta">{{ $plan['date']->format('l, d M Y') }} · {{ $plan['sessions']->count() }} {{ \Illuminate\Support\Str::plural('class', $plan['sessions']->count()) }}</span>
            </div>

            @foreach($plan['changes'] as $change)
                @php $changeText = \App\Services\Messaging\ClassMessageService::formatNotice($change['text']); @endphp
                @if($isRep)
                    <a href="{{ route('messages.show', $change['message']) }}" class="routine-change tone-{{ $change['tone'] }}">
                        <i class="bi bi-exclamation-triangle-fill"></i><span>{{ $changeText }}</span>
                    </a>
                @else
                    <div class="routine-change tone-{{ $change['tone'] }}">
                        <i class="bi bi-exclamation-triangle-fill"></i><span>{{ $changeText }}</span>
                    </div>
                @endif
            @endforeach

            @forelse($plan['sessions'] as $s)
                <div class="routine-slot is-{{ $s['state'] }}">
                    <div class="slot-time">
                        <div class="t-start">{{ $time($s['start']) }}</div>
                        <div class="t-end">{{ $time($s['end']) }}</div>
                    </div>
                    <div class="slot-body">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="slot-code">{{ $s['course_code'] }}</span>
                            <span class="badge-custom {{ $s['session_type'] === 'LAB' ? 'tone-danger' : 'tone-neutral' }}">{{ $s['session_type'] === 'LAB' ? 'Lab' : 'Theory' }}</span>
                            @isset($stateLabels[$s['state']])
                                <span class="badge-custom slot-state">{{ $stateLabels[$s['state']] }}</span>
                            @endisset
                        </div>
                        <div class="slot-name">{{ $s['course_name'] }}</div>
                        <div class="message-meta mt-1">
                            <i class="bi bi-geo-alt"></i> {{ $s['room'] }}
                            @if($s['teachers']) · <i class="bi bi-person"></i> {{ implode(', ', $s['teachers']) }} @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <div class="empty-state-icon"><i class="bi bi-cup-hot"></i></div>
                    <p class="text-muted mt-3 mb-0">No classes on {{ $dayLabel }}{{ $plan['week']->isEmpty() ? ' — no routine has been published for your section yet' : '' }}.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="col-12 col-xl-5">
        {{-- Ready-made WhatsApp text --}}
        <div class="glass-card mb-4 share-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-whatsapp share-icon"></i>
                <h6 class="text-white fw-bold mb-0">Share with your class</h6>
            </div>
            <p class="message-meta mb-3">Ready to paste in the class group. You can edit it before copying.</p>

            <ul class="nav share-tabs mb-2" role="tablist">
                <li><button class="active" type="button" data-bs-toggle="tab" data-bs-target="#shareDay" role="tab" aria-selected="true">{{ $when }}</button></li>
                <li><button type="button" data-bs-toggle="tab" data-bs-target="#shareWeek" role="tab" aria-selected="false">Whole week</button></li>
            </ul>
            <div class="tab-content">
                @foreach(['shareDay' => $plan['day_text'], 'shareWeek' => $plan['week_text']] as $id => $text)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $id }}" role="tabpanel">
                        <label class="visually-hidden" for="{{ $id }}Text">Routine text</label>
                        <textarea id="{{ $id }}Text" rows="11" class="form-control form-control-custom notice-input">{{ $text }}</textarea>
                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button type="button" class="btn-glass btn-sm-custom" data-copy-target="#{{ $id }}Text"><i class="bi bi-clipboard"></i> <span>Copy</span></button>
                            <button type="button" class="btn-indigo btn-whatsapp btn-sm-custom" data-whatsapp-target="#{{ $id }}Text"><i class="bi bi-whatsapp"></i> <span>Share on WhatsApp</span></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- The week at a glance --}}
        <div class="glass-card">
            <div class="form-section-title mb-2">This week</div>
            @foreach($plan['days'] as $d)
                @php $daySessions = $plan['week']->where('day', $d['day']); @endphp
                <a href="{{ route('class.routine', ['day' => $d['day']]) }}" class="week-row {{ $d['day'] === $plan['day'] ? 'active' : '' }}">
                    <span class="w-day">{{ $d['short'] }}</span>
                    <span class="w-list">
                        @forelse($daySessions as $s)
                            <span class="w-pill {{ $s['session_type'] === 'LAB' ? 'is-lab' : '' }}" title="{{ $s['course_name'] }} · {{ $time($s['start']) }}">{{ $s['course_code'] }}</span>
                        @empty
                            <span class="message-meta">Free</span>
                        @endforelse
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@endsection
