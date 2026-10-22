@extends('layouts.app')

@section('title', 'My Schedule')

@section('content')

<x-page-header eyebrow="Teacher Portal" title="My Weekly Schedule"
               :subtitle="$schedule['periods'] . ' teaching periods per week from published routines.'">
    <a href="{{ route('my.availability') }}" class="btn-glass"><i class="bi bi-calendar-week"></i> My Availability</a>
    <button class="btn-indigo" onclick="window.print();"><i class="bi bi-printer"></i> Print</button>
</x-page-header>

@if($schedule['periods'] === 0)
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-calendar2-week"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No classes yet</h4>
        <p class="text-muted mb-0">Your classes appear here once a routine that includes you is published.</p>
    </div>
@else
    <div class="tt-legend mb-3">
        <span><i class="lg-theory"></i> Theory</span>
        <span><i class="lg-combined"></i> Combined (with your pair teacher)</span>
        <span><i class="lg-lab"></i> Lab (joint, 3 periods)</span>
    </div>
    @include('schedules.partials.timetable', [
        'days' => $schedule['grid']['days'],
        'rows' => $schedule['grid']['rows'],
        'section' => $schedule['grid']['sections'][0],
    ])
@endif

@endsection
