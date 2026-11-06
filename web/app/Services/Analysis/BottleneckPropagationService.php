<?php

namespace App\Services\Analysis;

use App\Models\AcademicTerm;
use Illuminate\Support\Facades\DB;

/**
 * Bottleneck Propagation Analysis.
 *
 * Takes one resource away from the published routines (a room, a
 * teacher, a single period or a whole day) and repairs the timetable
 * the way a coordinator would, one class at a time:
 *
 *   1. Every class that used the failed resource is displaced.
 *   2. Each displaced class takes the cheapest legal placement:
 *      another room at the same time, then another time on the same
 *      day, then another day.
 *   3. When no placement is free, it may take the place of up to
 *      MAX_BUMPS other classes. Those are displaced one level deeper
 *      and repaired the same way (an ejection chain).
 *
 * Every placement respects the solver's hard rules: room type,
 * capacity and lab eligibility; no room, teacher or section clash;
 * teacher unavailability; labs on 3 consecutive periods; and a theory
 * course at most once a day per section. The depth of the chain and
 * everything it touched give the impact. Nothing is saved.
 */
class BottleneckPropagationService
{
    // Chains deeper than this are cut off and reported as unresolved.
    private const MAX_DEPTH = 6;

    // Safety cap on repair steps in one simulation.
    private const MAX_STEPS = 500;

    // A class may be displaced at most this many times.
    private const MAX_MOVES = 2;

    // A placement may displace at most this many other classes.
    private const MAX_BUMPS = 2;

    // Shared, read-only data of the published routines.
    private ?array $data = null;

    // State of the simulation in progress.
    private array $meetings = [];
    private array $roomAt = [];
    private array $teacherAt = [];
    private array $sectionAt = [];
    private array $theoryDay = [];
    private array $moves = [];
    private array $parent = [];
    private array $blockedRooms = [];
    private array $blockedTeachers = [];
    private array $blockedSlots = [];
    private array $blockCache = [];

    /**
     * Analyse one failure:
     *   ['type' => 'room'|'teacher'|'slot'|'day', 'id' => ..., 'day' => ?string]
     * `day` narrows a room or teacher failure to one weekday.
     */
    public function analyze(array $failure): array
    {
        $data = $this->dataset();
        $this->reset();

        $scope = $this->scopeSlots($failure);
        $initial = $this->describe($failure);

        // Block the failed resource and find the classes it hits.
        $displaced = [];

        foreach ($this->meetings as $key => $meeting) {
            $hit = array_intersect($meeting['slots'], $scope);

            if (!$hit) {
                continue;
            }

            $isHit = match ($failure['type']) {
                'room' => $meeting['room'] === (int) $failure['id'],
                'teacher' => in_array((int) $failure['id'], $meeting['teachers'], true),
                default => true,
            };

            if ($isHit) {
                $displaced[] = $key;
            }
        }

        foreach ($scope as $slotId) {
            match ($failure['type']) {
                'room' => $this->blockedRooms[(int) $failure['id']][$slotId] = true,
                'teacher' => $this->blockedTeachers[(int) $failure['id']][$slotId] = true,
                default => $this->blockedSlots[$slotId] = true,
            };
        }

        // Breadth-first repair so the chain unfolds level by level.
        $queue = [];

        foreach ($displaced as $key) {
            $queue[] = [
                'key' => $key,
                'depth' => 1,
                'parent' => null,
                'reason' => $initial,
                'from' => $this->placementOf($key),
            ];
            $this->vacate($key);
        }

        $events = [];
        $unresolved = [];
        $steps = 0;

        while ($queue) {
            $item = array_shift($queue);
            $key = $item['key'];

            if (++$steps > self::MAX_STEPS) {
                $unresolved[$key] = $item + ['why' => 'Repair stopped: the chain grew too long.'];
                $events[] = $item + ['to' => null, 'bumped' => []];
                continue;
            }

            $this->parent[$key] = $item['parent'];
            $placement = $this->bestPlacement($key, $item['depth']);

            if (!$placement) {
                $unresolved[$key] = $item + ['why' => $item['depth'] >= self::MAX_DEPTH
                    ? 'The chain is too deep to continue safely.'
                    : 'No room and period satisfies every hard rule, even after moving other classes.'];
                $events[] = $item + ['to' => null, 'bumped' => []];
                continue;
            }

            // Displace the classes standing in the way.
            foreach ($placement['conflicts'] as $other => $conflict) {
                $queue[] = [
                    'key' => $other,
                    'depth' => $item['depth'] + 1,
                    'parent' => $key,
                    'reason' => $this->bumpReason($key, $conflict, $placement),
                    'from' => $this->placementOf($other),
                ];
                $this->vacate($other);
            }

            $this->meetings[$key]['room'] = $placement['room'];
            $this->meetings[$key]['slots'] = $placement['slots'];
            $this->meetings[$key]['day'] = $data['slots'][$placement['slots'][0]]['day'];
            $this->occupy($key);
            $this->moves[$key] = ($this->moves[$key] ?? 0) + 1;

            $events[] = $item + [
                'to' => ['room' => $placement['room'], 'slots' => $placement['slots']],
                'bumped' => array_keys($placement['conflicts']),
            ];
        }

        return $this->report($failure, $initial, $displaced, $events, $unresolved);
    }

    /**
     * Run the analysis for every room (whole week), every teacher
     * (worst single day) and every teaching day, and rank them by
     * impact. The highest scores are the timetable's bottlenecks.
     */
    public function scan(): array
    {
        $data = $this->dataset();
        $results = [];

        $usedRooms = array_unique(array_column($data['meetings'], 'room'));

        foreach ($usedRooms as $roomId) {
            $results[] = $this->scanRow(['type' => 'room', 'id' => $roomId, 'day' => null]);
        }

        $usedTeachers = array_unique(array_merge(...array_column($data['meetings'], 'teachers') ?: [[]]));

        foreach ($usedTeachers as $teacherId) {
            $teacherDays = collect($data['meetings'])
                ->filter(fn ($meeting) => in_array($teacherId, $meeting['teachers'], true))
                ->pluck('day')
                ->unique();

            $worst = null;

            foreach ($teacherDays as $day) {
                $row = $this->scanRow(['type' => 'teacher', 'id' => $teacherId, 'day' => $day]);

                if (!$worst || $row['score'] > $worst['score']) {
                    $worst = $row;
                }
            }

            if ($worst) {
                $results[] = $worst;
            }
        }

        foreach (array_unique(array_column($data['meetings'], 'day')) as $day) {
            $results[] = $this->scanRow(['type' => 'day', 'id' => $day, 'day' => null]);
        }

        usort($results, fn ($a, $b) => [$b['score'], $b['entries']] <=> [$a['score'], $a['entries']]);

        return $results;
    }

    /**
     * Options for the analysis form.
     */
    public function options(): array
    {
        $data = $this->dataset();

        $slotOptions = collect($data['slots'])
            ->reject(fn ($slot) => $slot['is_break'])
            ->sortBy(fn ($slot) => [$data['day_order'][$slot['day']] ?? 99, $slot['number']])
            ->map(fn ($slot) => ['id' => $slot['id'], 'label' => $this->slotLabel([$slot['id']])])
            ->values()
            ->all();

        return [
            'term' => $data['term'],
            'rooms' => collect($data['rooms'])->map(fn ($room) => [
                'id' => $room['id'],
                'label' => "{$room['code']} — {$room['name']} ({$room['type']})",
                'used' => collect($data['meetings'])->where('room', $room['id'])->sum('entries'),
            ])->values()->all(),
            'teachers' => collect($data['teachers'])->map(fn ($name, $id) => [
                'id' => $id,
                'label' => $name,
                'used' => collect($data['meetings'])->filter(fn ($m) => in_array($id, $m['teachers'], true))->sum('entries'),
            ])->values()->all(),
            'slots' => $slotOptions,
            'days' => $data['days'],
            'meetings' => count($data['meetings']),
            'entries' => array_sum(array_column($data['meetings'], 'entries')),
            'runs' => $data['runs'],
        ];
    }

    // ── Dataset ──────────────────────────────────────────────────────

    /**
     * Published routines of the active term, regrouped into class
     * meetings (a lab's 3 periods form one meeting).
     */
    public function dataset(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $term = AcademicTerm::where('status', 'ACTIVE')->orderByDesc('id')->first();
        $dayOrder = array_flip(config('intellisched.days'));

        $slots = [];

        foreach (DB::table('time_slots')->get() as $slot) {
            $slots[(int) $slot->id] = [
                'id' => (int) $slot->id,
                'day' => strtoupper($slot->day),
                'number' => (int) $slot->slot_number,
                'start' => $slot->start_time,
                'end' => $slot->end_time,
                'is_break' => (bool) (int) $slot->is_break,
            ];
        }

        // Teaching periods of each day, in order.
        $teachingDays = [];

        foreach ($slots as $slot) {
            if (!$slot['is_break']) {
                $teachingDays[$slot['day']][] = $slot;
            }
        }

        uksort($teachingDays, fn ($a, $b) => ($dayOrder[$a] ?? 99) <=> ($dayOrder[$b] ?? 99));

        foreach ($teachingDays as &$daySlots) {
            usort($daySlots, fn ($a, $b) => $a['number'] <=> $b['number']);
        }
        unset($daySlots);

        $eligibility = DB::table('room_course_eligibility')->get(['room_id', 'course_id'])
            ->groupBy(fn ($row) => (int) $row->room_id)
            ->map(fn ($group) => $group->pluck('course_id')->map(fn ($id) => (int) $id)->all());

        $rooms = [];

        foreach (DB::table('rooms')->orderBy('priority')->orderBy('id')->get() as $room) {
            $rooms[(int) $room->id] = [
                'id' => (int) $room->id,
                'code' => $room->room_code,
                'name' => $room->room_name,
                'type' => strtoupper((string) $room->room_type),
                'capacity' => (int) $room->capacity,
                'active' => strtoupper((string) $room->status) === 'ACTIVE',
                'eligible' => $eligibility->get((int) $room->id, []),
            ];
        }

        $teachers = DB::table('teachers as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->orderBy('u.name')
            ->get(['t.id', 't.employee_code', 'u.name'])
            ->mapWithKeys(fn ($row) => [(int) $row->id => $row->name ?? $row->employee_code])
            ->all();

        $teacherOff = [];

        foreach (DB::table('teacher_availability')->where('is_available', 0)->get(['teacher_id', 'time_slot_id']) as $row) {
            $teacherOff[(int) $row->teacher_id][(int) $row->time_slot_id] = true;
        }

        $runs = DB::table('schedule_runs as sr')
            ->join('batches as b', 'b.id', '=', 'sr.batch_id')
            ->where('sr.status', 'PUBLISHED')
            ->when($term, fn ($query) => $query->where('sr.academic_term_id', $term->id))
            ->orderBy('b.seniority_order')
            ->get(['sr.id', 'sr.version', 'b.name as batch_name']);

        $rows = DB::table('schedule_entries as se')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->join('batches as b', 'b.id', '=', 's.batch_id')
            ->whereIn('se.schedule_run_id', $runs->pluck('id')->all())
            ->orderBy('se.id')
            ->get([
                'se.id', 'se.schedule_run_id', 'se.teacher_id', 'se.co_teacher_id', 'se.room_id',
                'se.time_slot_id', 'se.session_type', 'se.session_group_id',
                'c.id as course_id', 'c.course_code', 'c.course_name',
                's.id as section_id', 's.name as section_name', 's.student_count',
                'b.id as batch_id', 'b.name as batch_name',
            ]);

        $meetings = [];

        $groups = $rows->groupBy(fn ($row) => $row->session_group_id
            ? $row->schedule_run_id . ':g:' . $row->session_group_id
            : 'e:' . $row->id);

        foreach ($groups as $key => $group) {
            $first = $group->first();

            $slotIds = $group->pluck('time_slot_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => isset($slots[$id]))
                ->sortBy(fn ($id) => $slots[$id]['number'])
                ->values()
                ->all();

            if (!$slotIds) {
                continue;
            }

            $teacherIds = array_values(array_unique(array_filter([
                (int) $first->teacher_id,
                (int) $first->co_teacher_id,
            ])));

            $isLab = strtolower((string) $first->session_type) === 'lab';

            $meetings[(string) $key] = [
                'key' => (string) $key,
                'entries' => count($slotIds),
                'course_id' => (int) $first->course_id,
                'course' => $first->course_code,
                'course_name' => $first->course_name,
                'lab' => $isLab,
                'kind' => $isLab ? 'Lab' : (count($teacherIds) > 1 ? 'Combined theory' : 'Theory'),
                'section_id' => (int) $first->section_id,
                'section' => "{$first->batch_name} · Sec {$first->section_name}",
                'batch_id' => (int) $first->batch_id,
                'students' => (int) $first->student_count,
                'teachers' => $teacherIds,
                'room' => (int) $first->room_id,
                'slots' => $slotIds,
                'day' => $slots[$slotIds[0]]['day'],
            ];
        }

        return $this->data = [
            'term' => $term,
            'runs' => $runs->map(fn ($run) => "{$run->batch_name} v{$run->version}")->all(),
            'slots' => $slots,
            'teaching_days' => $teachingDays,
            'days' => array_keys($teachingDays),
            'day_order' => $dayOrder,
            'rooms' => $rooms,
            'teachers' => $teachers,
            'teacher_off' => $teacherOff,
            'meetings' => $meetings,
        ];
    }

    // ── Simulation ───────────────────────────────────────────────────

    private function reset(): void
    {
        $this->meetings = $this->data['meetings'];
        $this->roomAt = [];
        $this->teacherAt = [];
        $this->sectionAt = [];
        $this->theoryDay = [];
        $this->moves = [];
        $this->parent = [];
        $this->blockedRooms = [];
        $this->blockedTeachers = [];
        $this->blockedSlots = [];

        foreach (array_keys($this->meetings) as $key) {
            $this->occupy($key);
        }
    }

    private function occupy(string $key): void
    {
        $meeting = $this->meetings[$key];

        foreach ($meeting['slots'] as $slotId) {
            $this->roomAt[$meeting['room']][$slotId] = $key;
            $this->sectionAt[$meeting['section_id']][$slotId] = $key;

            foreach ($meeting['teachers'] as $teacherId) {
                $this->teacherAt[$teacherId][$slotId] = $key;
            }
        }

        if (!$meeting['lab']) {
            $this->theoryDay[$this->theoryDayKey($meeting, $meeting['day'])][$key] = true;
        }
    }

    private function vacate(string $key): void
    {
        $meeting = $this->meetings[$key];

        foreach ($meeting['slots'] as $slotId) {
            if (($this->roomAt[$meeting['room']][$slotId] ?? null) === $key) {
                unset($this->roomAt[$meeting['room']][$slotId]);
            }

            if (($this->sectionAt[$meeting['section_id']][$slotId] ?? null) === $key) {
                unset($this->sectionAt[$meeting['section_id']][$slotId]);
            }

            foreach ($meeting['teachers'] as $teacherId) {
                if (($this->teacherAt[$teacherId][$slotId] ?? null) === $key) {
                    unset($this->teacherAt[$teacherId][$slotId]);
                }
            }
        }

        if (!$meeting['lab']) {
            unset($this->theoryDay[$this->theoryDayKey($meeting, $meeting['day'])][$key]);
        }
    }

    /**
     * The cheapest legal placement for a displaced class, or null.
     * Returns ['room', 'slots', 'cost', 'conflicts' => [key => why]].
     */
    private function bestPlacement(string $key, int $depth): ?array
    {
        $meeting = $this->meetings[$key];
        $original = $this->data['meetings'][$key];
        $originalStart = $this->data['slots'][$original['slots'][0]]['number'];
        $best = null;

        foreach ($this->blocksOf(count($meeting['slots'])) as $block) {
            $day = $this->data['slots'][$block[0]]['day'];

            // Fixed obstacles that moving other classes cannot clear.
            foreach ($block as $slotId) {
                if (isset($this->blockedSlots[$slotId])) {
                    continue 2;
                }

                foreach ($meeting['teachers'] as $teacherId) {
                    if (isset($this->data['teacher_off'][$teacherId][$slotId])
                        || isset($this->blockedTeachers[$teacherId][$slotId])) {
                        continue 3;
                    }
                }
            }

            if (!$meeting['lab'] && !empty($this->theoryDay[$this->theoryDayKey($meeting, $day)])) {
                continue;
            }

            // Teacher and section clashes do not depend on the room.
            $peopleConflicts = [];

            foreach ($block as $slotId) {
                foreach ($meeting['teachers'] as $teacherId) {
                    if (isset($this->teacherAt[$teacherId][$slotId])) {
                        $peopleConflicts[$this->teacherAt[$teacherId][$slotId]] ??= ['why' => 'teacher', 'teacher' => $teacherId];
                    }
                }

                if (isset($this->sectionAt[$meeting['section_id']][$slotId])) {
                    $peopleConflicts[$this->sectionAt[$meeting['section_id']][$slotId]] ??= ['why' => 'section'];
                }
            }

            $sameTime = $block === $original['slots'];
            $timeCost = $sameTime ? 0 : ($day === $original['day'] ? 3 : 4);
            $timeCost += abs($this->data['slots'][$block[0]]['number'] - $originalStart) * 0.05;

            foreach ($this->data['rooms'] as $room) {
                if (!$this->roomFits($room, $meeting)) {
                    continue;
                }

                $conflicts = $peopleConflicts;

                foreach ($block as $slotId) {
                    if (isset($this->blockedRooms[$room['id']][$slotId])) {
                        continue 2;
                    }

                    if (isset($this->roomAt[$room['id']][$slotId])) {
                        $conflicts[$this->roomAt[$room['id']][$slotId]] ??= ['why' => 'room', 'room' => $room['id']];
                    }
                }

                if ($conflicts && !$this->canBump($conflicts, $key, $depth)) {
                    continue;
                }

                $cost = $timeCost + ($room['id'] === $original['room'] ? 0 : 1);

                foreach (array_keys($conflicts) as $other) {
                    $cost += 10 + ($this->meetings[$other]['lab'] ? 4 : 0);
                }

                if ($best === null || $cost < $best['cost']) {
                    $best = ['room' => $room['id'], 'slots' => $block, 'cost' => $cost, 'conflicts' => $conflicts];
                }
            }
        }

        return $best;
    }

    private function canBump(array $conflicts, string $key, int $depth): bool
    {
        if ($depth >= self::MAX_DEPTH || count($conflicts) > self::MAX_BUMPS) {
            return false;
        }

        foreach (array_keys($conflicts) as $other) {
            // Never bounce back the class that displaced this one, and
            // stop moving classes that have already moved twice.
            if ($other === ($this->parent[$key] ?? null) || ($this->moves[$other] ?? 0) >= self::MAX_MOVES) {
                return false;
            }
        }

        return true;
    }

    // Same rule as the solver's PlacementCandidateBuilder::RoomCanHost.
    private function roomFits(array $room, array $meeting): bool
    {
        if (!$room['active'] || $room['capacity'] < $meeting['students']) {
            return false;
        }

        if ($meeting['lab']) {
            return in_array($room['type'], ['LAB', 'MULTIPURPOSE'], true)
                && in_array($meeting['course_id'], $room['eligible'], true);
        }

        return in_array($room['type'], ['THEORY', 'MULTIPURPOSE'], true);
    }

    /**
     * Runs of `n` consecutive teaching periods on one day.
     */
    private function blocksOf(int $n): array
    {
        if (isset($this->blockCache[$n])) {
            return $this->blockCache[$n];
        }

        $blocks = [];

        foreach ($this->data['teaching_days'] as $daySlots) {
            for ($i = 0; $i + $n <= count($daySlots); $i++) {
                $run = array_slice($daySlots, $i, $n);

                for ($j = 1; $j < $n; $j++) {
                    if ($run[$j]['number'] !== $run[$j - 1]['number'] + 1) {
                        continue 2;
                    }
                }

                $blocks[] = array_column($run, 'id');
            }
        }

        return $this->blockCache[$n] = $blocks;
    }

    private function scopeSlots(array $failure): array
    {
        $slots = $this->data['slots'];

        $ids = match ($failure['type']) {
            'slot' => [(int) $failure['id']],
            'day' => array_keys(array_filter($slots, fn ($slot) => $slot['day'] === $failure['id'])),
            default => array_keys(array_filter($slots, fn ($slot) => empty($failure['day']) || $slot['day'] === $failure['day'])),
        };

        return array_map('intval', $ids);
    }

    private function theoryDayKey(array $meeting, string $day): string
    {
        return $meeting['section_id'] . ':' . $meeting['course_id'] . ':' . $day;
    }

    private function placementOf(string $key): array
    {
        return ['room' => $this->meetings[$key]['room'], 'slots' => $this->meetings[$key]['slots']];
    }

    // ── Report ───────────────────────────────────────────────────────

    private function report(array $failure, string $initial, array $displaced, array $events, array $unresolved): array
    {
        $data = $this->data;
        $affected = [];

        foreach (array_unique(array_column($events, 'key')) as $key) {
            $before = $data['meetings'][$key];
            $isUnresolved = isset($unresolved[$key]);
            $after = $isUnresolved ? null : $this->meetings[$key];

            $roomChanged = $after && $after['room'] !== $before['room'];
            $timeChanged = $after && $after['slots'] !== $before['slots'];

            if (!$isUnresolved && !$roomChanged && !$timeChanged) {
                continue; // Moved and later moved back.
            }

            $affected[$key] = [
                'meeting' => $before,
                'label' => $this->meetingLabel($before),
                'from' => $this->placementLabel($before['room'], $before['slots']),
                'to' => $after ? $this->placementLabel($after['room'], $after['slots']) : null,
                'change' => $isUnresolved ? 'unresolved' : ($roomChanged && $timeChanged ? 'room+time' : ($roomChanged ? 'room' : 'time')),
                'rooms' => array_values(array_unique(array_filter([$before['room'], $after['room'] ?? null]))),
                'teachers' => $before['teachers'],
                'why' => $unresolved[$key]['why'] ?? null,
            ];
        }

        $allMeetings = $data['meetings'];
        $totalEntries = array_sum(array_column($allMeetings, 'entries'));
        $totalTeachers = count(array_unique(array_merge([], ...array_values(array_column($allMeetings, 'teachers')))));
        $totalSections = count(array_unique(array_column($allMeetings, 'section_id')));

        $entries = array_sum(array_map(fn ($a) => $a['meeting']['entries'], $affected));
        $courses = array_unique(array_map(fn ($a) => $a['meeting']['course_id'], $affected));
        $sections = array_unique(array_map(fn ($a) => $a['meeting']['section_id'], $affected));
        $teachers = array_unique(array_merge([], ...array_values(array_column($affected, 'teachers'))));
        $rooms = array_unique(array_merge([], ...array_values(array_column($affected, 'rooms'))));
        $unresolvedCount = count(array_filter($affected, fn ($a) => $a['change'] === 'unresolved'));

        $depth = 0;

        foreach ($events as $event) {
            if (isset($affected[$event['key']])) {
                $depth = max($depth, $event['depth']);
            }
        }

        // Impact score (0–100) from four transparent components.
        $disruption = 0.0;

        foreach ($affected as $a) {
            $disruption += $a['meeting']['entries'] * match ($a['change']) {
                'room' => 0.5,
                'unresolved' => 1.5,
                default => 1.0,
            };
        }

        $components = [
            'breadth' => ['label' => 'Schedule entries disrupted', 'weight' => 35,
                'value' => min(1, $disruption / max(1, 0.2 * $totalEntries))],
            'people' => ['label' => 'Teachers and sections reached', 'weight' => 25,
                'value' => 0.5 * count($teachers) / max(1, $totalTeachers) + 0.5 * count($sections) / max(1, $totalSections)],
            'depth' => ['label' => 'Propagation depth', 'weight' => 20,
                'value' => min(1, max(0, $depth - 1) / 4)],
            'feasibility' => ['label' => 'Classes left without a legal slot', 'weight' => 20,
                'value' => $unresolvedCount ? min(1, 0.5 + $unresolvedCount / max(1, count($affected))) : 0],
        ];

        foreach ($components as &$component) {
            $component['points'] = round($component['weight'] * $component['value'], 1);
        }
        unset($component);

        $score = (int) round(array_sum(array_column($components, 'points')));

        return [
            'failure' => $failure,
            'initial' => $initial,
            'score' => $score,
            'severity' => $this->severity($score),
            'components' => $components,
            'depth' => $depth,
            'counts' => [
                'courses' => count($courses),
                'entries' => $entries,
                'meetings' => count($affected),
                'teachers' => count($teachers),
                'rooms' => count($rooms),
                'sections' => count($sections),
                'unresolved' => $unresolvedCount,
                'displaced' => count($displaced),
            ],
            'totals' => [
                'entries' => $totalEntries,
                'meetings' => count($allMeetings),
                'teachers' => $totalTeachers,
                'sections' => $totalSections,
            ],
            'levels' => $this->levels($events, $affected),
            'affected' => array_values($affected),
            'teacher_names' => array_values(array_map(fn ($id) => $data['teachers'][$id] ?? "Teacher #{$id}", $teachers)),
            'section_names' => array_values(array_unique(array_map(fn ($a) => $a['meeting']['section'], $affected))),
            'room_codes' => array_values(array_map(fn ($id) => $data['rooms'][$id]['code'] ?? "Room #{$id}", $rooms)),
            'recommendations' => $this->recommendations($failure, $affected, $depth, $unresolvedCount),
        ];
    }

    /**
     * Chain events grouped by depth, for the propagation diagram.
     */
    private function levels(array $events, array $affected): array
    {
        $levels = [];

        foreach ($events as $event) {
            $meeting = $this->data['meetings'][$event['key']];

            $levels[$event['depth']][] = [
                'label' => $this->meetingLabel($meeting),
                'reason' => $event['reason'],
                'from' => $this->placementLabel($event['from']['room'], $event['from']['slots']),
                'to' => $event['to'] ? $this->placementLabel($event['to']['room'], $event['to']['slots']) : null,
                'change' => $event['to'] === null ? 'unresolved'
                    : ($event['to']['slots'] === $event['from']['slots'] ? 'room'
                        : ($event['to']['room'] === $event['from']['room'] ? 'time' : 'room+time')),
                'bumped' => array_map(fn ($key) => $this->meetingLabel($this->data['meetings'][$key]), $event['bumped']),
                'final' => isset($affected[$event['key']]),
            ];
        }

        ksort($levels);

        return $levels;
    }

    private function recommendations(array $failure, array $affected, int $depth, int $unresolved): array
    {
        $tips = [];

        if (!$affected) {
            return ['No published class depends on this resource in the chosen scope, so the routine absorbs the failure with no change.'];
        }

        if ($unresolved) {
            $tips[] = "{$unresolved} class(es) cannot be re-placed without breaking a hard rule. Regenerate the affected batch routine with this resource marked unavailable, or relax the constraint (extra room, substitute teacher, extra period).";
        }

        if ($failure['type'] === 'room') {
            $room = $this->data['rooms'][(int) $failure['id']] ?? null;
            $labCourses = collect($affected)->filter(fn ($a) => $a['meeting']['lab'])->pluck('meeting.course')->unique();

            if ($room && $labCourses->isNotEmpty()) {
                $tips[] = 'Make a second lab eligible for ' . $labCourses->implode(', ') . '. Lab eligibility is the tightest constraint here; one more eligible room would absorb this failure.';
            } elseif ($room) {
                $tips[] = "Keep a {$room['type']} room with capacity ≥ {$room['capacity']} as a standby for {$room['code']}.";
            }
        }

        if ($failure['type'] === 'teacher') {
            $tips[] = 'Pair-teacher rules mean a teacher cannot be replaced inside the routine. Arrange a substitute or make-up classes, and ask the CR/ACR of each affected section to inform students.';
        }

        if ($depth >= 3) {
            $tips[] = "The change ripples {$depth} levels deep. The timetable has little slack around this resource, so treat it as a bottleneck when planning capacity.";
        } elseif (collect($affected)->every(fn ($a) => $a['change'] === 'room')) {
            $tips[] = 'Only rooms change and every class keeps its time, so students and teachers are barely disrupted.';
        }

        return $tips;
    }

    private function scanRow(array $failure): array
    {
        $result = $this->analyze($failure);

        return [
            'failure' => $failure,
            'type' => $failure['type'],
            'label' => $result['initial'],
            'score' => $result['score'],
            'severity' => $result['severity'],
            'entries' => $result['counts']['entries'],
            'depth' => $result['depth'],
            'unresolved' => $result['counts']['unresolved'],
            'teachers' => $result['counts']['teachers'],
            'sections' => $result['counts']['sections'],
        ];
    }

    // ── Labels ───────────────────────────────────────────────────────

    public function describe(array $failure): string
    {
        $data = $this->dataset();
        $scope = empty($failure['day']) ? ' (whole week)' : ' on ' . ucfirst(strtolower($failure['day']));

        return match ($failure['type']) {
            'room' => ($data['rooms'][(int) $failure['id']]['code'] ?? 'Room') . ' unavailable' . $scope,
            'teacher' => ($data['teachers'][(int) $failure['id']] ?? 'Teacher') . ' unavailable' . $scope,
            'slot' => $this->slotLabel([(int) $failure['id']]) . ' closed',
            'day' => ucfirst(strtolower((string) $failure['id'])) . ' closed (no classes)',
            default => 'Unknown failure',
        };
    }

    private function bumpReason(string $byKey, array $conflict, array $placement): string
    {
        $by = $this->meetingLabel($this->meetings[$byKey]);
        $when = $this->slotLabel($placement['slots']);

        return match ($conflict['why']) {
            'room' => ($this->data['rooms'][$conflict['room']]['code'] ?? 'Room') . " conflict at {$when}: needed by {$by}",
            'teacher' => ($this->data['teachers'][$conflict['teacher']] ?? 'Teacher') . " clash at {$when}: needed by {$by}",
            default => "Section clash at {$when}: taken by {$by}",
        };
    }

    private function meetingLabel(array $meeting): string
    {
        return "{$meeting['course']} {$meeting['kind']} · {$meeting['section']}";
    }

    private function placementLabel(int $roomId, array $slotIds): string
    {
        return $this->slotLabel($slotIds) . ' · ' . ($this->data['rooms'][$roomId]['code'] ?? "Room #{$roomId}");
    }

    private function slotLabel(array $slotIds): string
    {
        $first = $this->data['slots'][$slotIds[0]] ?? null;
        $last = $this->data['slots'][end($slotIds)] ?? $first;

        if (!$first) {
            return 'Unknown period';
        }

        $periods = count($slotIds) > 1 ? "P{$first['number']}–P{$last['number']}" : "P{$first['number']}";

        return substr($first['day'], 0, 3) . " {$periods} ({$first['start']}–{$last['end']})";
    }

    private function severity(int $score): array
    {
        return match (true) {
            $score >= 75 => ['label' => 'Critical', 'tone' => 'danger'],
            $score >= 50 => ['label' => 'High', 'tone' => 'warning'],
            $score >= 25 => ['label' => 'Moderate', 'tone' => 'info'],
            default => ['label' => 'Low', 'tone' => 'success'],
        };
    }
}
