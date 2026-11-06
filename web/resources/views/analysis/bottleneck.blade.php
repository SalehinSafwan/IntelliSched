@extends('layouts.app')

@section('title', 'Bottleneck Analysis')

@section('content')

@php
    $toneColors = ['success' => '#10b981', 'info' => '#06b6d4', 'warning' => '#f59e0b', 'danger' => '#ef4444'];
    $changeBadges = [
        'room' => ['Room change', 'tone-info'],
        'time' => ['Time change', 'tone-warning'],
        'room+time' => ['Room + time change', 'tone-warning'],
        'unresolved' => ['No legal slot', 'tone-danger'],
    ];
    $type = old('type', request('type', 'room'));
    $failureTypes = [
        'room' => ['Room unavailable', 'bi-door-closed', 'Maintenance, a broken lab, an exam'],
        'teacher' => ['Teacher unavailable', 'bi-person-x', 'Leave, training, a meeting'],
        'slot' => ['Period closed', 'bi-clock-history', 'One period on one day'],
        'day' => ['Whole day closed', 'bi-calendar-x', 'Holiday or campus closure'],
    ];
    $dayLabel = fn ($d) => ucfirst(strtolower($d));
@endphp

<x-page-header eyebrow="Intelligence & Optimization" title="Bottleneck Propagation Analysis"
               subtitle="Take one resource away and IntelliSched repairs the published routines class by class, following the chain reaction across rooms, teachers and sections. Nothing is saved.">
    <a href="{{ route('analysis.utilization') }}" class="btn-glass"><i class="bi bi-grid-3x3-gap"></i> Where Is Slack?</a>
    <a href="{{ route('analysis.bottleneck', ['scan' => 1]) }}" class="btn-glass"><i class="bi bi-radar"></i> Scan All Resources</a>
</x-page-header>

<div class="info-panel mb-4 d-flex flex-wrap gap-3 align-items-center" style="font-size: 0.84rem;">
    <span class="text-muted"><i class="bi bi-database me-1"></i> Analysing
        <span class="text-white fw-semibold">{{ $options['meetings'] }} classes</span>
        ({{ $options['entries'] }} schedule entries) from published routines
        @if($options['term']) of <span class="text-indigo fw-semibold">{{ $options['term']->name }}</span>@endif:
    </span>
    @forelse($options['runs'] as $run)
        <span class="badge-custom badge-active">{{ $run }}</span>
    @empty
        <span class="badge-custom badge-inactive">No published routine</span>
    @endforelse
</div>

@if($options['meetings'] === 0)
    <div class="glass-card text-center py-5 mb-4">
        <div class="empty-state-icon"><i class="bi bi-diagram-3"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">Nothing to analyse yet</h4>
        <p class="text-muted">Generate and approve a routine first; the analysis runs on published routines.</p>
        <a href="{{ route('schedules.create') }}" class="btn-indigo"><i class="bi bi-stars"></i> Generate Routine</a>
    </div>
@endif

<!-- Failure scenario -->
<form method="GET" action="{{ route('analysis.bottleneck') }}" class="glass-card mb-4" id="failureForm">
    <div class="form-section-title mb-2">1 · What fails?</div>
    <div class="failure-types mb-4">
        @foreach($failureTypes as $value => $info)
            <label class="choice-card">
                <input type="radio" name="type" value="{{ $value }}" @checked($type === $value)>
                <span class="choice-body">
                    <span class="choice-title"><i class="bi {{ $info[1] }} me-1"></i> {{ $info[0] }}</span>
                    <span class="choice-sub">{{ $info[2] }}</span>
                </span>
            </label>
        @endforeach
    </div>

    <div class="form-section-title mb-2">2 · Which one, and when?</div>
    <div class="row g-3">
        <div class="col-12 col-md-7" data-type="room">
            <label class="form-label-custom" for="room">Room</label>
            <select id="room" name="room" class="form-select form-select-custom">
                @foreach($options['rooms'] as $room)
                    <option value="{{ $room['id'] }}" @selected((int) request('room') === $room['id'])>{{ $room['label'] }} · {{ $room['used'] }} entries</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-7" data-type="teacher">
            <label class="form-label-custom" for="teacher">Teacher</label>
            <select id="teacher" name="teacher" class="form-select form-select-custom">
                @foreach($options['teachers'] as $teacher)
                    <option value="{{ $teacher['id'] }}" @selected((int) request('teacher') === $teacher['id'])>{{ $teacher['label'] }} · {{ $teacher['used'] }} entries</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-5" data-type="room teacher">
            <label class="form-label-custom" for="on_day">Unavailable</label>
            <select id="on_day" name="on_day" class="form-select form-select-custom">
                <option value="">The whole week</option>
                @foreach($options['days'] as $day)
                    <option value="{{ $day }}" @selected(request('on_day') === $day)>Only on {{ $dayLabel($day) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-7" data-type="slot">
            <label class="form-label-custom" for="slot">Period</label>
            <select id="slot" name="slot" class="form-select form-select-custom">
                @foreach($options['slots'] as $slot)
                    <option value="{{ $slot['id'] }}" @selected((int) request('slot') === $slot['id'])>{{ $slot['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-7" data-type="day">
            <label class="form-label-custom" for="closed_day">Day</label>
            <select id="closed_day" name="closed_day" class="form-select form-select-custom">
                @foreach($options['days'] as $day)
                    <option value="{{ $day }}" @selected(request('closed_day') === $day)>{{ $dayLabel($day) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @if($errors->any())
        <ul class="conflict-list mt-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <div class="d-flex justify-content-end mt-4">
        <button class="btn-indigo" @disabled($options['meetings'] === 0)><i class="bi bi-diagram-3"></i> Run Propagation Analysis</button>
    </div>
</form>

@if($result)
    @php
        $c = $result['counts'];
        $tone = $result['severity']['tone'];
    @endphp

    <!-- Headline -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100 text-center">
                <div class="form-section-title mb-3">Impact score</div>
                <div class="impact-gauge" style="--score: {{ $result['score'] }}; --tone: {{ $toneColors[$tone] }};">
                    <div><span class="gauge-value">{{ $result['score'] }}</span><span class="gauge-max">/ 100</span></div>
                </div>
                <div class="mt-3"><span class="badge-custom tone-{{ $tone }}" style="font-size: 0.8rem;">{{ $result['severity']['label'] }} impact</span></div>
                <div class="text-muted mt-2" style="font-size: 0.82rem;"><i class="bi bi-lightning-charge text-gold"></i> {{ $result['initial'] }}</div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="glass-card h-100">
                <div class="form-section-title mb-3">Affected</div>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Courses</div><div class="metric-value">{{ $c['courses'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Schedule entries</div><div class="metric-value">{{ $c['entries'] }} <span class="text-muted" style="font-size: 0.8rem;">/ {{ $result['totals']['entries'] }}</span></div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Teachers</div><div class="metric-value">{{ $c['teachers'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Rooms</div><div class="metric-value">{{ $c['rooms'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Sections</div><div class="metric-value">{{ $c['sections'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Propagation depth</div><div class="metric-value text-indigo">{{ $result['depth'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Classes moved</div><div class="metric-value">{{ $c['meetings'] - $c['unresolved'] }}</div></div></div>
                    <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Unresolved</div><div class="metric-value {{ $c['unresolved'] ? 'text-danger' : 'text-emerald' }}">{{ $c['unresolved'] }}</div></div></div>
                </div>

                <div class="form-section-title mt-4 mb-2">How the score is built</div>
                @foreach($result['components'] as $component)
                    <div class="score-row">
                        <div>
                            <div class="d-flex justify-content-between"><span class="text-muted">{{ $component['label'] }}</span><span class="text-dim">max {{ $component['weight'] }}</span></div>
                            <div class="load-bar {{ $component['value'] >= 0.75 ? 'is-high' : '' }}"><span style="width: {{ round($component['value'] * 100) }}%;"></span></div>
                        </div>
                        <div class="text-end text-white fw-bold">{{ $component['points'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Chain reaction -->
        <div class="col-12 col-xl-7">
            <div class="glass-card h-100">
                <div class="section-heading"><span class="section-chip"><i class="bi bi-diagram-3"></i></span><h5 class="brand-font fw-bold text-white mb-0">Chain reaction</h5></div>
                <div class="chain">
                    <div class="chain-level is-origin">
                        <span class="chain-dot">0</span>
                        <div class="chain-title">Initial change</div>
                        <div class="chain-event"><div class="event-title">{{ $result['initial'] }}</div>
                            <div class="event-reason">{{ $c['displaced'] }} class(es) used this resource directly.</div></div>
                    </div>
                    @foreach($result['levels'] as $depth => $events)
                        <div class="chain-level">
                            <span class="chain-dot">{{ $depth }}</span>
                            <div class="chain-title">{{ $depth === 1 ? 'Directly displaced' : 'Knock-on effect · level ' . $depth }}</div>
                            @foreach($events as $event)
                                <div class="chain-event {{ $event['change'] === 'unresolved' ? 'is-unresolved' : '' }}">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span class="event-title">{{ $event['label'] }}</span>
                                        <span class="badge-custom {{ $changeBadges[$event['change']][1] }}">{{ $changeBadges[$event['change']][0] }}</span>
                                    </div>
                                    <div class="event-reason"><i class="bi bi-arrow-return-right"></i> {{ $event['reason'] }}</div>
                                    <div class="event-move">
                                        <span class="from">{{ $event['from'] }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-dim"></i>
                                        @if($event['to'])<span class="to">{{ $event['to'] }}</span>@else<span class="text-danger fw-semibold">no legal placement</span>@endif
                                    </div>
                                    @if($event['bumped'])
                                        <div class="event-reason mt-1"><i class="bi bi-exclamation-triangle text-gold"></i> Conflict: displaces {{ implode(', ', $event['bumped']) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    @if($result['affected'])
                        <div class="chain-level is-outcome">
                            <span class="chain-dot"><i class="bi bi-flag-fill"></i></span>
                            <div class="chain-title">Outcome</div>
                            <div class="chain-event">
                                <div class="event-move"><i class="bi bi-person-video3 me-1 text-indigo"></i> Teacher schedules change: <span class="text-white">{{ implode(', ', $result['teacher_names']) }}</span></div>
                                <div class="event-move"><i class="bi bi-people me-1 text-indigo"></i> Section schedules change: <span class="text-white">{{ implode(', ', $result['section_names']) }}</span></div>
                                <div class="event-move"><i class="bi bi-building me-1 text-indigo"></i> Rooms involved: <span class="text-white">{{ implode(', ', $result['room_codes']) }}</span></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Summary & recommendations -->
        <div class="col-12 col-xl-5">
            <div class="glass-card mb-4">
                <div class="form-section-title mb-2">Report</div>
                @php
                    $plural = fn ($n, $word) => $n . ' ' . \Illuminate\Support\Str::plural($word, $n);
                    $report = implode("\n", array_filter([
                        'Initial change:',
                        '  ' . $result['initial'],
                        '',
                        'Affected:',
                        '  ' . $plural($c['courses'], 'course'),
                        '  ' . $plural($c['entries'], 'schedule entry'),
                        '  ' . $plural($c['teachers'], 'teacher'),
                        '  ' . $plural($c['rooms'], 'room'),
                        '  ' . $plural($c['sections'], 'section'),
                        $c['unresolved'] ? '  ' . $c['unresolved'] . ' without a legal slot' : null,
                        '',
                        'Propagation depth: ' . $result['depth'],
                        'Impact score: ' . $result['score'] . '/100 (' . $result['severity']['label'] . ')',
                    ], fn ($line) => $line !== null));
                @endphp
                <pre class="info-panel mb-0" style="white-space: pre-wrap; font-size: 0.82rem; color: var(--text-main);">{{ $report }}</pre>
            </div>
            <div class="glass-card">
                <div class="form-section-title mb-2">Recommendations</div>
                <ul class="check-list">
                    @foreach($result['recommendations'] as $tip)
                        <li><i class="bi bi-lightbulb"></i> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @if($result['affected'])
        <div class="section-heading"><span class="section-chip"><i class="bi bi-list-check"></i></span><h5 class="brand-font fw-bold text-white mb-0">Affected classes (final state)</h5></div>
        <div class="table-custom-container mb-4">
            <table class="table-custom">
                <thead><tr><th>Class</th><th>Before</th><th>After</th><th>Change</th></tr></thead>
                <tbody>
                    @foreach($result['affected'] as $row)
                        <tr>
                            <td class="text-white fw-semibold">{{ $row['label'] }}</td>
                            <td class="text-muted">{{ $row['from'] }}</td>
                            <td>{!! $row['to'] ? '<span class="text-emerald">' . e($row['to']) . '</span>' : '<span class="text-danger">' . e($row['why']) . '</span>' !!}</td>
                            <td><span class="badge-custom {{ $changeBadges[$row['change']][1] }}">{{ $changeBadges[$row['change']][0] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif

@if($scan !== null)
    <div class="section-heading mt-2" id="scan"><span class="section-chip"><i class="bi bi-radar"></i></span>
        <div>
            <h5 class="brand-font fw-bold text-white mb-0">Resource criticality ranking</h5>
            <div class="text-muted" style="font-size: 0.8rem;">Each room (whole week), each teacher (their worst day) and each teaching day, simulated one at a time. The top rows are the timetable's bottlenecks.</div>
        </div>
    </div>
    <div class="table-custom-container mb-4">
        <table class="table-custom">
            <thead><tr><th>#</th><th>Scenario</th><th style="width: 200px;">Impact</th><th>Entries</th><th>Depth</th><th>Unresolved</th><th class="text-end"></th></tr></thead>
            <tbody>
                @forelse($scan as $row)
                    <tr>
                        <td class="text-dim">{{ $loop->iteration }}</td>
                        <td>
                            <div class="text-white fw-semibold">{{ $row['label'] }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ ucfirst($row['type']) }} · {{ $row['teachers'] }} teacher(s), {{ $row['sections'] }} section(s)</div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between"><span class="fw-bold text-white">{{ $row['score'] }}</span><span class="badge-custom tone-{{ $row['severity']['tone'] }}">{{ $row['severity']['label'] }}</span></div>
                            <div class="load-bar {{ $row['score'] >= 75 ? 'is-high' : '' }}"><span style="width: {{ $row['score'] }}%;"></span></div>
                        </td>
                        <td>{{ $row['entries'] }}</td>
                        <td>{{ $row['depth'] }}</td>
                        <td class="{{ $row['unresolved'] ? 'text-danger fw-bold' : 'text-muted' }}">{{ $row['unresolved'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('analysis.bottleneck', \App\Http\Controllers\Analysis\BottleneckController::queryFor($row['failure'])) }}" class="btn-glass btn-sm-custom"><i class="bi bi-zoom-in"></i> Inspect</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No published classes to scan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('failureForm');

        function refresh() {
            const checked = form.querySelector('input[name="type"]:checked');
            const type = checked ? checked.value : 'room';
            form.querySelectorAll('[data-type]').forEach(function (el) {
                const show = el.dataset.type.split(' ').includes(type);
                el.hidden = !show;
                // Hidden fields are not submitted, keeping the URL clean.
                el.querySelectorAll('select').forEach(function (select) { select.disabled = !show; });
            });
        }

        form.querySelectorAll('input[name="type"]').forEach(function (input) {
            input.addEventListener('change', refresh);
        });
        refresh();
    })();
</script>
@endpush

@endsection
