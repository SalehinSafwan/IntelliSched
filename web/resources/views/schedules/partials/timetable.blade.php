{{-- Weekly timetable of one section.
     Expects $days, $rows and $section from TimetableGridBuilder. --}}
<div class="timetable-container mb-4">
    <table class="timetable-grid">
        <thead>
            <tr>
                <th style="width: 112px;">TIME</th>
                @foreach($days as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $period = 0; @endphp
            @foreach($rows as $row)
                @php
                    $rowCells = $section['cells'][$row['slot_number']];
                    $isBreakRow = collect($rowCells)->every(
                        fn ($cell) => in_array($cell['type'], ['break', 'none'], true)
                    );
                    if (!$isBreakRow) {
                        $period++;
                    }
                @endphp
                <tr class="{{ $isBreakRow ? 'break-row' : '' }}">
                    <td class="time-cell">
                        @unless($isBreakRow)
                            <span class="period-no">Period {{ $period }}</span>
                        @endunless
                        {{ $row['label'] }}
                    </td>

                    @foreach($days as $day)
                        @php $cell = $rowCells[$day]; @endphp

                        @if($cell['type'] === 'covered')
                            @continue
                        @elseif($cell['type'] === 'break')
                            <td class="break-cell">BREAK</td>
                        @elseif($cell['type'] === 'entry')
                            @php
                                $entry = $cell['entry'];
                                $isLab = strtolower($entry['session_type']) === 'lab';
                                $isJoint = strtolower($entry['teacher_mode']) === 'joint';
                                $cardType = $isLab ? 'lab-type' : ($isJoint ? 'combined-type' : '');
                                $label = $isLab ? 'Lab' : ($isJoint ? 'Combined' : 'Theory');
                            @endphp
                            <td rowspan="{{ $cell['rowspan'] }}">
                                <div class="timetable-card {{ $cardType }}"
                                     title="{{ $entry['course_name'] }} · {{ $entry['time_label'] }}">
                                    <div>
                                        <div class="course-code">
                                            {{ $entry['course_code'] }}
                                            <span class="type-pill">{{ $label }}</span>
                                        </div>
                                        <div class="course-title">{{ $entry['course_name'] }}</div>
                                    </div>
                                    <div class="teacher-name">
                                        <i class="bi {{ $isJoint ? 'bi-people-fill' : 'bi-person-fill' }} me-1"></i>{{ implode(' & ', $entry['teachers']) }}
                                    </div>
                                    <div class="room-badge">
                                        <i class="bi bi-geo-alt-fill"></i> {{ $entry['room_code'] }}
                                        @if($isLab)
                                            · {{ $entry['time_label'] }}
                                        @endif
                                    </div>
                                </div>
                            </td>
                        @elseif($cell['type'] === 'none')
                            <td style="opacity: 0.3;"></td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
