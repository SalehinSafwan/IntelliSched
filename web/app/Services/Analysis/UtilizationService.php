<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\DB;

/**
 * Utilization of rooms, teachers and sections in the published
 * routines: the opposite question to bottleneck analysis, namely
 * where the timetable still has slack.
 *
 * Capacity is counted in teaching periods (breaks excluded). A room
 * or section can use every teaching period; a teacher only the
 * periods they have not marked unavailable. A lab occupies 3 periods
 * and joint sessions count fully for both teachers, as in the solver.
 */
class UtilizationService
{
    // Heatmap bins: 0 = empty, then 1–5 for up to 20/40/60/80/100%.
    public const BINS = 5;

    public function __construct(
        private BottleneckPropagationService $propagation
    ) {
    }

    public function build(): array
    {
        $data = $this->propagation->dataset();
        $days = $data['days'];

        // Teaching periods per day, in order, and the period grid.
        $daySlots = [];

        foreach ($data['teaching_days'] as $day => $slots) {
            $daySlots[$day] = array_column($slots, 'id');
        }

        $periodNumbers = collect($data['teaching_days'])->flatten(1)->pluck('number')->unique()->sort()->values()->all();

        // Who uses what, slot by slot.
        $roomBusy = [];
        $teacherBusy = [];
        $sectionBusy = [];
        $teacherLoad = [];

        foreach ($data['meetings'] as $meeting) {
            $label = "{$meeting['course']} {$meeting['kind']} · {$meeting['section']}";

            foreach ($meeting['slots'] as $slotId) {
                $roomBusy[$meeting['room']][$slotId] = $label;
                $sectionBusy[$meeting['section_id']][$slotId] = "{$meeting['course']} {$meeting['kind']} · " . ($data['rooms'][$meeting['room']]['code'] ?? '');

                foreach ($meeting['teachers'] as $teacherId) {
                    $teacherBusy[$teacherId][$slotId] = $label;
                }
            }

            foreach ($meeting['teachers'] as $teacherId) {
                $teacherLoad[$teacherId]['periods'] = ($teacherLoad[$teacherId]['periods'] ?? 0) + $meeting['entries'];
                $kind = $meeting['lab'] ? 'labs' : 'theory';
                $teacherLoad[$teacherId][$kind] = ($teacherLoad[$teacherId][$kind] ?? 0) + 1;
            }
        }

        // ── Rooms ────────────────────────────────────────────────────
        $usedRoomIds = array_keys($roomBusy);
        $rooms = collect($data['rooms'])
            ->filter(fn ($room) => $room['active'] || in_array($room['id'], $usedRoomIds, true));

        $roomRows = $rooms->map(fn ($room) => $this->row(
            'room-' . $room['id'],
            $room['code'],
            "{$room['type']} · {$room['capacity']} seats",
            $daySlots,
            $roomBusy[$room['id']] ?? [],
            []
        ))->values()->all();

        // ── Teachers ─────────────────────────────────────────────────
        $teacherMeta = DB::table('teachers')->get(['id', 'employee_code', 'max_weekly_classes', 'status'])->keyBy(fn ($t) => (int) $t->id);

        $teacherIds = collect($data['teachers'])->keys()
            ->filter(fn ($id) => isset($teacherBusy[$id]) || strtoupper((string) ($teacherMeta[$id]->status ?? '')) === 'ACTIVE');

        $teacherRows = $teacherIds->map(fn ($id) => $this->row(
            'teacher-' . $id,
            $data['teachers'][$id],
            $teacherMeta[$id]->employee_code ?? '',
            $daySlots,
            $teacherBusy[$id] ?? [],
            $data['teacher_off'][$id] ?? []
        ))->values()->all();

        // ── Sections ─────────────────────────────────────────────────
        $sectionRows = DB::table('sections as s')
            ->join('batches as b', 'b.id', '=', 's.batch_id')
            ->where('s.status', 'ACTIVE')
            ->orderBy('b.seniority_order')
            ->orderBy('s.name')
            ->get(['s.id', 's.name', 's.student_count', 'b.name as batch_name'])
            ->map(fn ($section) => $this->row(
                'section-' . $section->id,
                "{$section->batch_name} · Sec {$section->name}",
                "{$section->student_count} students",
                $daySlots,
                $sectionBusy[(int) $section->id] ?? [],
                []
            ))
            ->values()
            ->all();

        return [
            'term' => $data['term'],
            'runs' => $data['runs'],
            'days' => $days,
            'periods' => $periodNumbers,
            'slot_grid' => $this->slotGrid($data),
            'heatmaps' => [
                'rooms' => $roomRows,
                'teachers' => $teacherRows,
                'sections' => $sectionRows,
            ],
            'campus' => $this->campus($data, $roomBusy, $rooms->where('active', true)->keys()->all()),
            'workload' => $this->workload($data, $teacherIds->all(), $teacherLoad, $teacherMeta, $teacherRows),
            'room_types' => $this->roomTypes($data, $rooms->where('active', true)->all(), $roomBusy),
            'totals' => [
                'meetings' => count($data['meetings']),
                'entries' => array_sum(array_column($data['meetings'], 'entries')),
            ],
        ];
    }

    /**
     * One heatmap row: busy / capacity per day and for the week, plus
     * the slot-by-slot detail for the drill-down grid.
     */
    private function row(string $key, string $label, string $sub, array $daySlots, array $busy, array $off): array
    {
        $perDay = [];
        $totalBusy = 0;
        $totalCapacity = 0;
        $cells = [];

        foreach ($daySlots as $day => $slotIds) {
            $capacity = 0;
            $used = 0;

            foreach ($slotIds as $slotId) {
                if (isset($busy[$slotId])) {
                    $cells[$slotId] = ['state' => 'busy', 'label' => $busy[$slotId]];
                    $used++;
                    $capacity++;
                } elseif (isset($off[$slotId])) {
                    $cells[$slotId] = ['state' => 'off', 'label' => 'Marked unavailable'];
                } else {
                    $cells[$slotId] = ['state' => 'free', 'label' => 'Free'];
                    $capacity++;
                }
            }

            $perDay[$day] = $this->cell($used, $capacity);
            $totalBusy += $used;
            $totalCapacity += $capacity;
        }

        return [
            'key' => $key,
            'label' => $label,
            'sub' => $sub,
            'days' => $perDay,
            'week' => $this->cell($totalBusy, $totalCapacity),
            'cells' => $cells,
        ];
    }

    private function cell(int $used, int $capacity): array
    {
        $pct = $capacity > 0 ? round(100 * $used / $capacity) : 0;

        return [
            'used' => $used,
            'capacity' => $capacity,
            'pct' => (int) $pct,
            'bin' => $used === 0 ? 0 : max(1, min(self::BINS, (int) ceil($pct / (100 / self::BINS)))),
        ];
    }

    /**
     * [period number][day] => slot id, for drill-down grids.
     */
    private function slotGrid(array $data): array
    {
        $grid = [];

        foreach ($data['slots'] as $slot) {
            $grid[$slot['number']][$slot['day']] = $slot;
        }

        ksort($grid);

        return $grid;
    }

    /**
     * Share of active rooms in use at every period of the week.
     */
    private function campus(array $data, array $roomBusy, array $activeRoomIds): array
    {
        $cells = [];
        $roomCount = count($activeRoomIds);

        foreach ($data['teaching_days'] as $day => $slots) {
            foreach ($slots as $slot) {
                $codes = [];

                foreach ($activeRoomIds as $roomId) {
                    if (isset($roomBusy[$roomId][$slot['id']])) {
                        $codes[] = $data['rooms'][$roomId]['code'];
                    }
                }

                $cells[$slot['number']][$day] = $this->cell(count($codes), $roomCount) + [
                    'time' => "{$slot['start']}–{$slot['end']}",
                    'rooms' => $codes,
                ];
            }
        }

        ksort($cells);

        // values() drops the day keys so days don't overwrite each other.
        $flat = collect($cells)->flatMap(fn ($byDay, $number) => collect($byDay)
            ->map(fn ($cell, $day) => $cell + ['day' => $day, 'number' => $number])
            ->values())->values();

        $byDay = collect($data['days'])->mapWithKeys(fn ($day) => [$day => $flat->where('day', $day)->sum('used')]);

        return [
            'cells' => $cells,
            'room_count' => $roomCount,
            'peak' => $flat->sortByDesc('pct')->first(),
            'idle_periods' => $flat->where('used', 0)->count(),
            'teaching_periods' => $flat->count(),
            'busiest_day' => $byDay->sortDesc()->keys()->first(),
            'quietest_day' => $byDay->sort()->keys()->first(),
        ];
    }

    /**
     * Weekly periods per teacher with fairness statistics.
     */
    private function workload(array $data, array $teacherIds, array $load, $meta, array $teacherRows): array
    {
        $rowsByKey = collect($teacherRows)->keyBy('key');

        $teachers = collect($teacherIds)->map(function ($id) use ($data, $load, $meta, $rowsByKey) {
            $periods = (int) ($load[$id]['periods'] ?? 0);
            $cap = (int) ($meta[$id]->max_weekly_classes ?? 0);
            $available = $rowsByKey->get('teacher-' . $id)['week']['capacity'] ?? 0;

            return [
                'id' => $id,
                'name' => $data['teachers'][$id],
                'periods' => $periods,
                'theory' => (int) ($load[$id]['theory'] ?? 0),
                'labs' => (int) ($load[$id]['labs'] ?? 0),
                'cap' => $cap,
                'available' => $available,
                'over_cap' => $cap > 0 && $periods > $cap,
                'spare' => $cap > 0 ? max(0, $cap - $periods) : null,
            ];
        })->sortByDesc('periods')->values();

        $values = $teachers->pluck('periods');
        $n = max(1, $values->count());
        $mean = $values->sum() / $n;
        $variance = $values->map(fn ($v) => ($v - $mean) ** 2)->sum() / $n;
        $sd = sqrt($variance);
        $sumSquares = $values->map(fn ($v) => $v ** 2)->sum();

        // Jain's fairness index: 1 = perfectly even, 1/n = one person does it all.
        $jain = $sumSquares > 0 ? ($values->sum() ** 2) / ($n * $sumSquares) : 1;

        return [
            'teachers' => $teachers->all(),
            'scale' => max(1, $values->max() ?? 0, $teachers->max('cap') ?? 0),
            'mean' => round($mean, 1),
            'sd' => round($sd, 1),
            'cv' => $mean > 0 ? (int) round(100 * $sd / $mean) : 0,
            'jain' => (int) round(100 * $jain),
            'min' => (int) ($values->min() ?? 0),
            'max' => (int) ($values->max() ?? 0),
            'over_cap' => $teachers->where('over_cap', true)->count(),
        ];
    }

    /**
     * Used and spare room-periods per room type, and how many more
     * classes of each kind would still fit.
     */
    private function roomTypes(array $data, array $rooms, array $roomBusy): array
    {
        $labels = config('intellisched.room_types');

        return collect($rooms)
            ->groupBy('type')
            ->map(function ($group, $type) use ($data, $roomBusy, $labels) {
                $capacity = 0;
                $used = 0;
                $labBlocks = 0;

                foreach ($group as $room) {
                    foreach ($data['teaching_days'] as $slots) {
                        $run = 0;
                        $previous = null;

                        foreach ($slots as $slot) {
                            $capacity++;
                            $isBusy = isset($roomBusy[$room['id']][$slot['id']]);
                            $used += $isBusy ? 1 : 0;

                            // Count non-overlapping free runs of 3 consecutive
                            // periods: each could host one more lab.
                            $consecutive = $previous !== null && $slot['number'] === $previous + 1;
                            $run = $isBusy ? 0 : ($consecutive ? $run + 1 : 1);
                            $previous = $slot['number'];

                            if ($run === 3) {
                                $labBlocks++;
                                $run = 0;
                            }
                        }
                    }
                }

                $canLab = in_array($type, ['LAB', 'MULTIPURPOSE'], true);
                $canTheory = in_array($type, ['THEORY', 'MULTIPURPOSE'], true);

                return [
                    'type' => $type,
                    'label' => $labels[$type] ?? ucfirst(strtolower($type)),
                    'rooms' => $group->count(),
                    'codes' => $group->pluck('code')->all(),
                    'capacity' => $capacity,
                    'used' => $used,
                    'spare' => $capacity - $used,
                    'pct' => $capacity ? (int) round(100 * $used / $capacity) : 0,
                    'more_theory' => $canTheory ? $capacity - $used : null,
                    'more_labs' => $canLab ? $labBlocks : null,
                ];
            })
            ->sortBy('type')
            ->values()
            ->all();
    }
}
