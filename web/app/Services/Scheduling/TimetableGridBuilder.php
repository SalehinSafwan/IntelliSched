<?php

namespace App\Services\Scheduling;

class TimetableGridBuilder
{
    private const DAY_ORDER = [
        'SATURDAY',
        'SUNDAY',
        'MONDAY',
        'TUESDAY',
        'WEDNESDAY',
        'THURSDAY',
        'FRIDAY',
    ];

    /**
     * Build one weekly grid per section: rows are slot numbers,
     * columns are days.
     *
     * $timeSlots: [id, day, slot_number, start_time, end_time, is_break]
     * $sections:  [id, name]
     * $entries:   [section_id, time_slot_ids[], course_code, course_name,
     *              session_type, teacher_mode, teachers[], room_code]
     *
     * Each cell is one of:
     *   ['type' => 'empty' | 'break' | 'none' | 'covered']
     *   ['type' => 'entry', 'entry' => [...], 'rowspan' => n]
     */
    public function build(
        iterable $timeSlots,
        iterable $sections,
        iterable $entries
    ): array {
        $slots = collect($timeSlots)->map(fn ($slot) => (array) $slot);

        $days = $slots
            ->pluck('day')
            ->map(fn ($day) => strtoupper((string) $day))
            ->unique()
            ->sortBy(function ($day) {
                $index = array_search($day, self::DAY_ORDER, true);

                return $index === false ? 99 : $index;
            })
            ->values()
            ->all();

        // slotGrid[slot_number][day] = slot
        $slotGrid = [];
        $slotsById = [];

        foreach ($slots as $slot) {
            $slotGrid[(int) $slot['slot_number']][strtoupper($slot['day'])] = $slot;
            $slotsById[(int) $slot['id']] = $slot;
        }

        ksort($slotGrid);

        // Label each row with the time of its first day.
        $rows = [];

        foreach ($slotGrid as $slotNumber => $byDay) {
            $first = reset($byDay);

            $rows[] = [
                'slot_number' => $slotNumber,
                'label' => $first['start_time'] . ' – ' . $first['end_time'],
            ];
        }

        $entriesBySection = collect($entries)
            ->map(fn ($entry) => (array) $entry)
            ->groupBy(fn ($entry) => (int) $entry['section_id']);

        $grids = [];

        foreach ($sections as $section) {
            $section = (array) $section;
            $cells = [];

            // Start with empty, break or missing cells.
            foreach ($slotGrid as $slotNumber => $byDay) {
                foreach ($days as $day) {
                    $slot = $byDay[$day] ?? null;

                    $cells[$slotNumber][$day] = [
                        'type' => $slot === null
                            ? 'none'
                            : ((bool) $slot['is_break'] ? 'break' : 'empty'),
                    ];
                }
            }

            // Place every class meeting at its first slot and let
            // multi-period meetings (labs) span the following rows.
            foreach ($entriesBySection->get((int) $section['id'], []) as $entry) {
                $occupied = collect($entry['time_slot_ids'])
                    ->map(fn ($id) => $slotsById[(int) $id] ?? null)
                    ->filter()
                    ->sortBy('slot_number')
                    ->values();

                if ($occupied->isEmpty()) {
                    continue;
                }

                $day = strtoupper($occupied[0]['day']);
                $start = (int) $occupied[0]['slot_number'];

                $entry['time_label'] = $occupied[0]['start_time']
                    . ' – ' . $occupied->last()['end_time'];

                $cells[$start][$day] = [
                    'type' => 'entry',
                    'entry' => $entry,
                    'rowspan' => $occupied->count(),
                ];

                foreach ($occupied->skip(1) as $slot) {
                    $cells[(int) $slot['slot_number']][$day] = ['type' => 'covered'];
                }
            }

            $grids[] = [
                'id' => (int) $section['id'],
                'name' => $section['name'],
                'cells' => $cells,
            ];
        }

        return [
            'days' => $days,
            'rows' => $rows,
            'sections' => $grids,
        ];
    }
}
