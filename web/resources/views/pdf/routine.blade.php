@extends('pdf.layout')

@push('pdf-styles')
<style>
    @page { margin: 80px 26px 50px; }
    .tt th { text-align: center; }
    .tt td { height: 38px; padding: 3px 4px; font-size: 8.5px; }
    .tt td.time { width: 72px; background: #f5f3ff !important; font-weight: bold; text-align: center; vertical-align: middle; color: #4338ca; }
    .tt td.brk { background: #f3f4f6 !important; color: #9ca3af; text-align: center; vertical-align: middle; height: 14px; letter-spacing: 2px; font-size: 7.5px; }
    .cls { border-left: 3px solid #6366f1; padding-left: 4px; }
    .cls.lab { border-color: #db2777; }
    .cls.joint { border-color: #0d9488; }
    .cls b { font-size: 9.5px; color: #111827; }
    .section-page { page-break-after: always; }
    .section-page:last-child { page-break-after: auto; }
</style>
@endpush

@section('content')

@foreach($grid['sections'] as $section)
    <div class="section-page">
        <h2>{{ $run->batch_name }} · Section {{ $section['name'] }}
            <span class="muted" style="font-size: 10px; font-weight: normal;">— {{ $run->term_name }}, version {{ $run->version }} ({{ $run->status }})</span></h2>

        <table class="tt">
            <thead>
                <tr>
                    <th>Time</th>
                    @foreach($grid['days'] as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($grid['rows'] as $row)
                    @php
                        $cells = $section['cells'][$row['slot_number']];
                        $isBreak = collect($cells)->every(fn ($c) => in_array($c['type'], ['break', 'none'], true));
                    @endphp
                    <tr>
                        <td class="time">{{ $row['label'] }}</td>
                        @foreach($grid['days'] as $day)
                            @php $cell = $cells[$day]; @endphp
                            @if($cell['type'] === 'covered')
                                @continue
                            @elseif($cell['type'] === 'break')
                                <td class="brk">BREAK</td>
                            @elseif($cell['type'] === 'entry')
                                @php
                                    $e = $cell['entry'];
                                    $isLab = strtolower($e['session_type']) === 'lab';
                                    $isJoint = strtolower($e['teacher_mode']) === 'joint';
                                @endphp
                                <td rowspan="{{ $cell['rowspan'] }}">
                                    <div class="cls {{ $isLab ? 'lab' : ($isJoint ? 'joint' : '') }}">
                                        <b>{{ $e['course_code'] }}</b> {{ $isLab ? '(Lab)' : ($isJoint ? '(Combined)' : '') }}<br>
                                        {{ implode(' & ', $e['teachers']) }}<br>
                                        <span class="muted">{{ $e['room_code'] }}{{ $isLab ? ' · ' . $e['time_label'] : '' }}</span>
                                    </div>
                                </td>
                            @else
                                <td></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Course teacher pairs</h3>
        <table>
            <thead><tr><th>Course</th><th>Title</th><th>Teachers</th></tr></thead>
            <tbody>
                @foreach($coursePairs as $pair)
                    <tr><td><strong>{{ $pair['course_code'] }}</strong></td><td>{{ $pair['course_name'] }}</td><td>{{ implode(' & ', $pair['teachers']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach

@endsection
