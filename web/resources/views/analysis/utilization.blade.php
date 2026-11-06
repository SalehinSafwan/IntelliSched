@extends('layouts.app')

@section('title', 'Utilization Heatmaps')

@section('content')

@php
    $dayShort = fn ($d) => ucfirst(strtolower(substr($d, 0, 3)));
    $dayLabel = fn ($d) => ucfirst(strtolower((string) $d));
    $binRanges = ['0%', '1–20%', '21–40%', '41–60%', '61–80%', '81–100%'];
    $tabs = [
        'rooms' => ['Rooms', 'bi-building', 'Share of each room\'s teaching periods that hold a class.'],
        'teachers' => ['Teachers', 'bi-person-video3', 'Share of each teacher\'s available periods spent teaching. Unavailable periods are not counted as capacity.'],
        'sections' => ['Sections', 'bi-people', 'Share of each section\'s teaching periods with a class.'],
    ];

    $roomRows = collect($data['heatmaps']['rooms']);
    $roomUsed = $roomRows->sum('week.used');
    $roomCapacity = $roomRows->sum('week.capacity');
    $workload = $data['workload'];
    $campus = $data['campus'];
    $spare = collect($data['room_types'])->sum('spare');
@endphp

<x-page-header eyebrow="Intelligence & Optimization" title="Utilization Heatmaps"
               subtitle="How full every room, teacher and section is across the week, and where the timetable still has slack. The opposite question to bottleneck analysis.">
    <a href="{{ route('analysis.bottleneck') }}" class="btn-glass"><i class="bi bi-diagram-3"></i> Bottleneck Analysis</a>
</x-page-header>

<div class="info-panel mb-4 d-flex flex-wrap gap-3 align-items-center" style="font-size: 0.84rem;">
    <span class="text-muted"><i class="bi bi-database me-1"></i>
        <span class="text-white fw-semibold">{{ $data['totals']['meetings'] }} classes</span>
        ({{ $data['totals']['entries'] }} schedule entries) from published routines
        @if($data['term']) of <span class="text-indigo fw-semibold">{{ $data['term']->name }}</span>@endif:
    </span>
    @forelse($data['runs'] as $run)
        <span class="badge-custom badge-active">{{ $run }}</span>
    @empty
        <span class="badge-custom badge-inactive">No published routine</span>
    @endforelse
</div>

<div class="viz" id="utilization">

    <!-- Headline figures -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-tile">
                <div class="metric-label">Room utilization</div>
                <div class="metric-value">{{ $roomCapacity ? round(100 * $roomUsed / $roomCapacity) : 0 }}%</div>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $roomUsed }} of {{ $roomCapacity }} room-periods</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-tile">
                <div class="metric-label">Spare room-periods</div>
                <div class="metric-value">{{ $spare }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $campus['idle_periods'] }} periods with every room empty</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-tile">
                <div class="metric-label">Average teacher load</div>
                <div class="metric-value">{{ $workload['mean'] }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">periods a week · range {{ $workload['min'] }}–{{ $workload['max'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-tile">
                <div class="metric-label">Workload fairness</div>
                <div class="metric-value">{{ $workload['jain'] }}%</div>
                <div class="text-muted" style="font-size: 0.75rem;">Jain's index · 100% = perfectly even</div>
            </div>
        </div>
    </div>

    @if($insights)
        <div class="glass-card mb-4">
            <div class="section-heading"><span class="section-chip"><i class="bi bi-lightbulb"></i></span><h5 class="brand-font fw-bold text-white mb-0">Where is the slack?</h5></div>
            <ul class="check-list">
                @foreach($insights as $insight)
                    <li><i class="bi {{ $insight['icon'] }}"></i> {{ $insight['text'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Resource heatmaps -->
    <div class="glass-card mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <div class="section-heading mb-0"><span class="section-chip"><i class="bi bi-grid-3x3-gap"></i></span>
                <div>
                    <h5 class="brand-font fw-bold text-white mb-0">Weekly utilization by day</h5>
                    <div class="text-muted" style="font-size: 0.8rem;">Select a name to see its week period by period.</div>
                </div>
            </div>
            <ul class="nav filter-pills list-unstyled mb-0 ps-0 align-self-lg-center" role="tablist">
                @foreach($tabs as $key => $tab)
                    <li role="presentation">
                        <a href="#heat-{{ $key }}" class="{{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" role="tab"
                           aria-controls="heat-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <i class="bi {{ $tab[1] }}"></i> {{ $tab[0] }} · {{ count($data['heatmaps'][$key]) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
            <div class="heat-legend" aria-label="Colour scale">
                <span>Less used</span>
                <span class="steps">
                    @foreach($binRanges as $bin => $range)
                        <span class="swatch heat-cell" data-bin="{{ $bin }}" title="{{ $range }}" style="height: 12px; min-width: 34px;"></span>
                    @endforeach
                </span>
                <span>More used</span>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input js-show-values" type="checkbox" role="switch" id="showValues">
                <label class="form-check-label text-muted" for="showValues" style="font-size: 0.8rem;">Show values</label>
            </div>
        </div>

        <div class="tab-content">
            @foreach($tabs as $key => $tab)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="heat-{{ $key }}" role="tabpanel">
                    <p class="text-dim mb-2" style="font-size: 0.78rem;">{{ $tab[2] }}</p>
                    @if(empty($data['heatmaps'][$key]))
                        <p class="text-muted">Nothing to show.</p>
                    @else
                        <div class="timetable-container">
                            <table class="heat-table">
                                <thead>
                                    <tr>
                                        <th class="row-head">{{ $tab[0] }}</th>
                                        @foreach($data['days'] as $day)<th>{{ $dayShort($day) }}</th>@endforeach
                                        <th class="text-start ps-2">Week</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['heatmaps'][$key] as $row)
                                        <tr>
                                            <td class="row-head">
                                                <button type="button" class="heat-row-btn js-drill" data-target="{{ $row['key'] }}" aria-pressed="false">{{ $row['label'] }}</button>
                                                <span class="heat-row-sub">{{ $row['sub'] }}</span>
                                            </td>
                                            @foreach($data['days'] as $day)
                                                @php $c = $row['days'][$day]; @endphp
                                                <td class="heat-cell" data-bin="{{ $c['bin'] }}" tabindex="0"
                                                    data-tip="{{ $row['label'] }} · {{ $dayLabel($day) }}&#10;{{ $c['used'] }} of {{ $c['capacity'] }} periods used ({{ $c['pct'] }}%)"
                                                    aria-label="{{ $row['label'] }}, {{ $dayLabel($day) }}: {{ $c['pct'] }} percent">
                                                    <span class="heat-val">{{ $c['pct'] }}%</span>
                                                </td>
                                            @endforeach
                                            <td class="heat-week">
                                                {{ $row['week']['pct'] }}% <span class="text-dim">· {{ $row['week']['used'] }}/{{ $row['week']['capacity'] }}</span>
                                                <div class="meter"><span style="width: {{ $row['week']['pct'] }}%;"></span></div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Drill-down: one week grid per resource, shown on demand -->
        @foreach($data['heatmaps'] as $rows)
            @foreach($rows as $row)
                <div class="js-drill-panel mt-4" data-key="{{ $row['key'] }}" hidden>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="text-white fw-bold">Week of {{ $row['label'] }} <span class="text-muted fw-normal" style="font-size: 0.8rem;">· {{ $row['week']['used'] }} busy of {{ $row['week']['capacity'] }} available periods</span></div>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="viz-key"><i class="key-used"></i> Busy</span>
                            <span class="viz-key"><i class="key-spare"></i> Free</span>
                            @if(str_starts_with($row['key'], 'teacher-'))<span class="viz-key"><i class="week-cell" data-state="off" style="width: 14px; height: 10px;"></i> Unavailable</span>@endif
                        </div>
                    </div>
                    <div class="timetable-container">
                        <table class="heat-table">
                            <thead><tr><th class="row-head">Period</th>@foreach($data['days'] as $day)<th>{{ $dayShort($day) }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach($data['slot_grid'] as $number => $byDay)
                                    @php $first = collect($byDay)->first(); @endphp
                                    <tr>
                                        <td class="row-head text-muted" style="font-size: 0.72rem;">P{{ $number }} · {{ $first['start'] }}</td>
                                        @foreach($data['days'] as $day)
                                            @php $slot = $byDay[$day] ?? null; @endphp
                                            @if(!$slot)
                                                <td></td>
                                            @elseif($slot['is_break'])
                                                <td class="week-cell" data-state="break">BREAK</td>
                                            @else
                                                @php $cell = $row['cells'][$slot['id']] ?? ['state' => 'free', 'label' => 'Free']; @endphp
                                                <td class="week-cell" data-state="{{ $cell['state'] }}" tabindex="0"
                                                    data-tip="{{ $dayLabel($day) }} P{{ $number }} ({{ $slot['start'] }}–{{ $slot['end'] }})&#10;{{ $cell['label'] }}">
                                                    {{ $cell['state'] === 'busy' ? \Illuminate\Support\Str::before($cell['label'], ' ') : '' }}
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- Campus occupancy -->
    <div class="glass-card mb-4">
        <div class="section-heading"><span class="section-chip"><i class="bi bi-clock-history"></i></span>
            <div>
                <h5 class="brand-font fw-bold text-white mb-0">Campus occupancy by period</h5>
                <div class="text-muted" style="font-size: 0.8rem;">
                    Share of the {{ $campus['room_count'] }} active rooms in use at each period.
                    @if($campus['peak'] && $campus['peak']['used'])
                        Peak: {{ $dayLabel($campus['peak']['day']) }} P{{ $campus['peak']['number'] }} at {{ $campus['peak']['pct'] }}%.
                    @endif
                </div>
            </div>
        </div>
        <div class="timetable-container">
            <table class="heat-table" id="campusTable">
                <thead><tr><th class="row-head">Period</th>@foreach($data['days'] as $day)<th>{{ $dayShort($day) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($campus['cells'] as $number => $byDay)
                        <tr>
                            <td class="row-head text-muted" style="font-size: 0.72rem;">P{{ $number }} · {{ collect($byDay)->first()['time'] }}</td>
                            @foreach($data['days'] as $day)
                                @php $c = $byDay[$day] ?? null; @endphp
                                @if(!$c)
                                    <td class="heat-cell is-none"></td>
                                @else
                                    <td class="heat-cell" data-bin="{{ $c['bin'] }}" tabindex="0"
                                        data-tip="{{ $dayLabel($day) }} P{{ $number }} ({{ $c['time'] }})&#10;{{ $c['used'] }} of {{ $c['capacity'] }} rooms in use ({{ $c['pct'] }}%){{ $c['rooms'] ? "\n" . implode(', ', $c['rooms']) : '' }}"
                                        aria-label="{{ $dayLabel($day) }} period {{ $number }}: {{ $c['pct'] }} percent of rooms in use">
                                        <span class="heat-val">{{ $c['pct'] }}%</span>
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Workload fairness -->
        <div class="col-12 col-xl-7">
            <div class="glass-card h-100">
                <div class="section-heading"><span class="section-chip"><i class="bi bi-bar-chart-steps"></i></span>
                    <div>
                        <h5 class="brand-font fw-bold text-white mb-0">Teaching periods per week</h5>
                        <div class="text-muted" style="font-size: 0.8rem;">Labs count 3 periods; joint sessions count for both teachers.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3 mb-3">
                    <span class="viz-key"><i class="key-line"></i> Average ({{ $workload['mean'] }})</span>
                    <span class="viz-key"><i class="key-cap"></i> Teacher's weekly cap</span>
                    @if($workload['over_cap'])
                        <span class="viz-key"><i class="bi bi-exclamation-triangle-fill" style="color: var(--viz-serious);"></i> Over cap</span>
                    @endif
                </div>

                @php $scale = $workload['scale']; $tickStep = max(1, (int) ceil($scale / 5)); @endphp
                @forelse($workload['teachers'] as $t)
                    <div class="workload-row">
                        <div class="workload-name">{{ $t['name'] }}<small>{{ $t['theory'] }} theory · {{ $t['labs'] }} {{ \Illuminate\Support\Str::plural('lab', $t['labs']) }}</small></div>
                        <div class="workload-track" tabindex="0"
                             data-tip="{{ $t['name'] }}&#10;{{ $t['periods'] }} periods a week ({{ $t['theory'] }} theory classes, {{ $t['labs'] }} labs)&#10;Cap {{ $t['cap'] ?: '—' }} · {{ $t['available'] }} available periods{{ $t['over_cap'] ? "\nOver the weekly cap" : '' }}"
                             aria-label="{{ $t['name'] }}: {{ $t['periods'] }} periods, cap {{ $t['cap'] }}">
                            <span class="workload-bar {{ $t['over_cap'] ? 'is-over' : '' }}" style="width: {{ round(100 * $t['periods'] / $scale, 2) }}%;"></span>
                            @if($t['cap'])<span class="workload-cap" style="left: {{ round(100 * $t['cap'] / $scale, 2) }}%;"></span>@endif
                            <span class="workload-mean" style="left: {{ round(100 * $workload['mean'] / $scale, 2) }}%;"></span>
                        </div>
                        <div class="workload-value">
                            {{ $t['periods'] }}<small> / {{ $t['cap'] ?: '—' }}</small>
                            @if($t['over_cap'])<i class="bi bi-exclamation-triangle-fill ms-1" style="color: var(--viz-serious);" title="Over cap"></i><span class="visually-hidden">over cap</span>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No teachers to show.</p>
                @endforelse

                @if($workload['teachers'])
                    <div class="workload-axis">
                        <span></span>
                        <div class="ticks">
                            @for($v = 0; $v <= $scale; $v += $tickStep)
                                <span style="left: {{ round(100 * $v / $scale, 2) }}%;">{{ $v }}</span>
                            @endfor
                        </div>
                        <span>periods</span>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Fairness (Jain)</div><div class="metric-value">{{ $workload['jain'] }}%</div></div></div>
                        <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Std. deviation</div><div class="metric-value">{{ $workload['sd'] }}</div></div></div>
                        <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Variation (CV)</div><div class="metric-value">{{ $workload['cv'] }}%</div></div></div>
                        <div class="col-6 col-md-3"><div class="metric-tile"><div class="metric-label">Spread</div><div class="metric-value">{{ $workload['max'] - $workload['min'] }}</div></div></div>
                    </div>
                    <p class="form-hint mt-2 mb-0">Jain's index is 100% when every teacher has the same load and falls towards 1/n as the work concentrates on fewer people. CV is the standard deviation as a share of the average.</p>
                @endif
            </div>
        </div>

        <!-- Spare capacity per room type -->
        <div class="col-12 col-xl-5">
            <div class="glass-card h-100">
                <div class="section-heading"><span class="section-chip"><i class="bi bi-box-seam"></i></span>
                    <div>
                        <h5 class="brand-font fw-bold text-white mb-0">Spare capacity by room type</h5>
                        <div class="text-muted" style="font-size: 0.8rem;">Room-periods used and still free each week.</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <span class="viz-key"><i class="key-used"></i> Used</span>
                    <span class="viz-key"><i class="key-spare"></i> Spare</span>
                </div>

                @forelse($data['room_types'] as $type)
                    @php $usedPct = $type['pct']; $sparePct = 100 - $usedPct; @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <span class="text-white fw-semibold" style="font-size: 0.88rem;">{{ $type['label'] }}</span>
                            <span class="text-muted" style="font-size: 0.75rem;">{{ $type['rooms'] }} {{ \Illuminate\Support\Str::plural('room', $type['rooms']) }} · {{ implode(', ', $type['codes']) }}</span>
                        </div>
                        <div class="capacity-bar" tabindex="0"
                             data-tip="{{ $type['label'] }}&#10;Used {{ $type['used'] }} of {{ $type['capacity'] }} room-periods ({{ $usedPct }}%)&#10;Spare {{ $type['spare'] }} ({{ $sparePct }}%)"
                             aria-label="{{ $type['label'] }}: {{ $usedPct }} percent used, {{ $type['spare'] }} spare room-periods">
                            @if($type['used'] > 0)
                                <span class="used" style="flex: {{ $type['used'] }} 1 0;">{{ $usedPct >= 22 ? $usedPct . '% used' : '' }}</span>
                            @endif
                            @if($type['spare'] > 0)
                                <span class="spare" style="flex: {{ $type['spare'] }} 1 0;">{{ $sparePct >= 22 ? $sparePct . '% spare' : '' }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-2" style="font-size: 0.78rem;">
                            <span class="text-muted"><span class="text-white fw-bold">{{ $type['spare'] }}</span> spare room-periods</span>
                            @if($type['more_theory'] !== null)
                                <span class="text-muted">fits <span class="text-white fw-bold">{{ $type['more_theory'] }}</span> more theory classes</span>
                            @endif
                            @if($type['more_labs'] !== null)
                                <span class="text-muted"><span class="text-white fw-bold">{{ $type['more_labs'] }}</span> free 3-period lab blocks</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No active rooms.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="viz-tip" id="vizTip" role="tooltip"></div>

@push('scripts')
<script>
    (function () {
        const root = document.getElementById('utilization');
        const tip = document.getElementById('vizTip');

        // Fixed positioning must not be relative to the animated content.
        document.body.appendChild(tip);

        // One tooltip for every chart: hover or keyboard focus.
        function show(el, x, y) {
            tip.textContent = el.dataset.tip;
            tip.classList.add('is-visible');
            const box = tip.getBoundingClientRect();
            const left = Math.min(x + 14, window.innerWidth - box.width - 8);
            const top = y + 16 + box.height > window.innerHeight ? y - box.height - 12 : y + 16;
            tip.style.left = Math.max(8, left) + 'px';
            tip.style.top = Math.max(8, top) + 'px';
        }
        function hide() { tip.classList.remove('is-visible'); }

        root.addEventListener('mousemove', function (e) {
            const el = e.target.closest('[data-tip]');
            el ? show(el, e.clientX, e.clientY) : hide();
        });
        root.addEventListener('mouseleave', hide);
        root.addEventListener('focusin', function (e) {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            const r = el.getBoundingClientRect();
            show(el, r.left + r.width / 2, r.bottom);
        });
        root.addEventListener('focusout', hide);

        // Values toggle: the heatmaps double as a table.
        root.querySelector('.js-show-values').addEventListener('change', function () {
            const on = this.checked;
            root.querySelectorAll('.heat-table').forEach(function (t) { t.classList.toggle('show-values', on); });
        });

        // Drill-down into one resource's week.
        root.querySelectorAll('.js-drill').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = btn.dataset.target;
                const wasOpen = btn.getAttribute('aria-pressed') === 'true';
                root.querySelectorAll('.js-drill').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
                root.querySelectorAll('.js-drill-panel').forEach(function (p) { p.hidden = true; });
                if (!wasOpen) {
                    btn.setAttribute('aria-pressed', 'true');
                    const panel = root.querySelector('.js-drill-panel[data-key="' + key + '"]');
                    panel.hidden = false;
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });

        // Switching tabs closes an open drill-down.
        root.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                root.querySelectorAll('.js-drill').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
                root.querySelectorAll('.js-drill-panel').forEach(function (p) { p.hidden = true; });
            });
        });
    })();
</script>
@endpush

@endsection
