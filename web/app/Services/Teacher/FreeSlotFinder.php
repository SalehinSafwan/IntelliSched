<?php

namespace App\Services\Teacher;

use App\Models\Section;
use App\Models\Teacher;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class FreeSlotFinder
{
    /**
     * Overlay the teacher's week on the weekly routine of the chosen
     * sections and find the periods where everyone is free.
     *
     * A period is busy for the teacher when they teach a published
     * class or marked it unavailable; it is busy for a section when the
     * section has a published class. "Avoid" periods stay free but are
     * flagged, and every free period shows how many rooms are empty.
     */
    public function build(Teacher $teacher, array $sectionIds): array
    {
        $slots = TimeSlot::all();
        $dayOrder = array_flip(config('intellisched.days'));

        $days = $slots->pluck('day')
            ->map(fn ($day) => strtoupper($day))
            ->unique()
            ->sortBy(fn ($day) => $dayOrder[$day] ?? 99)
            ->values()
            ->all();

        $sections = Section::with('batch')->whereIn('id', $sectionIds)->get()->keyBy('id');

        $published = DB::table('schedule_entries as se')
            ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->join('batches as b', 'b.id', '=', 's.batch_id')
            ->join('rooms as r', 'r.id', '=', 'se.room_id')
            ->where('sr.status', 'PUBLISHED')
            ->get([
                'se.time_slot_id', 'se.teacher_id', 'se.co_teacher_id', 'se.room_id', 'se.session_type',
                'co.section_id', 'c.course_code', 'b.name as batch_name', 's.name as section_name', 'r.room_code',
            ]);

        $teacherBusy = [];
        $sectionBusy = [];
        $usedRooms = [];

        foreach ($published as $row) {
            $slotId = (int) $row->time_slot_id;
            $usedRooms[$slotId][(int) $row->room_id] = true;

            if ((int) $row->teacher_id === (int) $teacher->id || (int) $row->co_teacher_id === (int) $teacher->id) {
                $teacherBusy[$slotId] = "{$row->course_code} · {$row->batch_name}-{$row->section_name} · {$row->room_code}";
            }

            if ($sections->has((int) $row->section_id)) {
                $sectionBusy[$slotId][] = "{$row->section_name}: {$row->course_code}"
                    . (strtolower($row->session_type) === 'lab' ? ' (lab)' : '');
            }
        }

        $availability = DB::table('teacher_availability')
            ->where('teacher_id', $teacher->id)
            ->get(['time_slot_id', 'is_available', 'preference'])
            ->keyBy(fn ($row) => (int) $row->time_slot_id);

        $activeRooms = DB::table('rooms')->where('status', 'ACTIVE')->pluck('id')->map(fn ($id) => (int) $id)->all();

        // cells[slot_number][day]
        $cells = [];
        $rows = [];

        foreach ($slots->groupBy(fn ($slot) => (int) $slot->slot_number)->sortKeys() as $number => $group) {
            $byDay = $group->keyBy(fn ($slot) => strtoupper($slot->day));
            $first = $group->first();
            $rows[$number] = $first->start_time . ' – ' . $first->end_time;

            foreach ($days as $day) {
                $slot = $byDay->get($day);

                if (!$slot) {
                    $cells[$number][$day] = ['type' => 'none'];
                    continue;
                }

                if ($slot->is_break) {
                    $cells[$number][$day] = ['type' => 'break'];
                    continue;
                }

                $id = (int) $slot->id;
                $record = $availability->get($id);
                $unavailable = $record && !(int) $record->is_available;

                $teacherState = isset($teacherBusy[$id]) ? 'class' : ($unavailable ? 'unavailable' : null);
                $sectionLabels = $sectionBusy[$id] ?? [];

                $state = match (true) {
                    $teacherState && $sectionLabels => 'both',
                    (bool) $teacherState => 'teacher',
                    (bool) $sectionLabels => 'students',
                    default => 'free',
                };

                $cells[$number][$day] = [
                    'type' => 'slot',
                    'slot_id' => $id,
                    'state' => $state,
                    'time' => $slot->start_time . ' – ' . $slot->end_time,
                    'teacher' => $teacherState === 'class' ? $teacherBusy[$id] : ($teacherState ? 'You marked this period unavailable' : null),
                    'sections' => $sectionLabels,
                    'avoid' => $record && (int) $record->is_available && (int) $record->preference < 0,
                    'free_rooms' => count(array_diff($activeRooms, array_keys($usedRooms[$id] ?? []))),
                ];
            }
        }

        return [
            'days' => $days,
            'rows' => $rows,
            'cells' => $cells,
            'sections' => $sections->values(),
            'summary' => $this->summarise($days, $cells),
        ];
    }

    /**
     * Common free periods per day and the longest run of back-to-back
     * free periods (long enough for a make-up lab when it reaches 3).
     */
    private function summarise(array $days, array $cells): array
    {
        $perDay = [];
        $teaching = 0;
        $free = 0;

        foreach ($days as $day) {
            $count = 0;
            $run = 0;
            $longest = 0;

            foreach ($cells as $byDay) {
                $cell = $byDay[$day];

                if ($cell['type'] !== 'slot') {
                    // Breaks and missing periods interrupt a run.
                    $run = 0;
                    continue;
                }

                $teaching++;

                if ($cell['state'] === 'free') {
                    $count++;
                    $run++;
                    $longest = max($longest, $run);
                } else {
                    $run = 0;
                }
            }

            $free += $count;
            $perDay[$day] = ['free' => $count, 'longest' => $longest];
        }

        $bestDay = collect($perDay)
            ->filter(fn ($info) => $info['free'] > 0)
            ->sortByDesc(fn ($info) => $info['longest'] * 100 + $info['free'])
            ->keys()
            ->first();

        return [
            'teaching' => $teaching,
            'free' => $free,
            'per_day' => $perDay,
            'best_day' => $bestDay,
            'lab_ready_days' => collect($perDay)->filter(fn ($info) => $info['longest'] >= 3)->keys()->all(),
        ];
    }
}
