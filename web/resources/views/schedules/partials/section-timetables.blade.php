{{-- All section timetables with a colour legend. Expects $grid. --}}
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
    <h4 class="brand-font fw-bold text-white mb-0">Weekly Routine</h4>
    <div class="tt-legend">
        <span><i class="lg-theory"></i> Theory</span>
        <span><i class="lg-combined"></i> Combined (both teachers)</span>
        <span><i class="lg-lab"></i> Lab (joint, 3 periods)</span>
    </div>
</div>

@foreach($grid['sections'] as $section)
    <div class="section-block">
        <div class="section-heading">
            <span class="section-chip">{{ $section['name'] }}</span>
            <h5 class="brand-font fw-bold text-white mb-0">Section {{ $section['name'] }}</h5>
        </div>
        @include('schedules.partials.timetable', [
            'days' => $grid['days'],
            'rows' => $grid['rows'],
            'section' => $section,
        ])
    </div>
@endforeach
