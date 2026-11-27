<?php

namespace App\Services\Chatbot;

use App\Models\AcademicTerm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class ScheduleQueryService
{
    private ?array $runIds = null;


    public function publishedRunIds(): array
    {
        if ($this->runIds !== null) {
            return $this->runIds;
        }

        $published = DB::table('schedule_runs')->where('status', 'PUBLISHED');
        $term = AcademicTerm::current();

        $ids = $term
            ? (clone $published)->where('academic_term_id', $term->id)->pluck('id')->all()
            : [];

        if ($ids === []) {
            $ids = $published->pluck('id')->all();
        }

        return $this->runIds = array_map('intval', $ids);
    }

    public function sessions(array $scope, array $filters = []): Collection
    {
        $runIds = $this->publishedRunIds();
        if ($runIds === []) {
            return collect();
        }

        $query = DB::table('schedule_entries as se')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->leftJoin('batches as b', 'b.id', '=', 's.batch_id')
            ->join('time_slots as ts', 'ts.id', '=', 'se.time_slot_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'se.room_id')
            ->leftJoin('teachers as t', 't.id', '=', 'se.teacher_id')
            ->leftJoin('users as tu', 'tu.id', '=', 't.user_id')
            ->leftJoin('teachers as ct', 'ct.id', '=', 'se.co_teacher_id')
            ->leftJoin('users as ctu', 'ctu.id', '=', 'ct.user_id')
            ->whereIn('se.schedule_run_id', $runIds)
            ->select([
                'se.id as entry_id',
                'se.session_type',
                'se.session_group_id',
                'se.teacher_id',
                'se.co_teacher_id',
                'c.id as course_id',
                'c.course_code',
                'c.course_name',
                's.id as section_id',
                's.name as section_name',
                'b.name as batch_name',
                'ts.day',
                'ts.slot_number',
                'ts.start_time',
                'ts.end_time',
                'r.room_code',
                'r.room_name',
                't.employee_code as teacher_code',
                'tu.name as teacher_name',
                'ct.employee_code as co_teacher_code',
                'ctu.name as co_teacher_name',
            ]);

        if (isset($scope['teacher'])) {
            $teacherId = (int) $scope['teacher'];
            $query->where(fn ($q) => $q->where('se.teacher_id', $teacherId)
                ->orWhere('se.co_teacher_id', $teacherId));
        } else {
            $query->whereIn('co.section_id', $scope['sections'] ?? [0]);
        }

        if (!empty($filters['course_ids'])) {
            $query->whereIn('c.id', $filters['course_ids']);
        }

        if (!empty($filters['teacher_ids'])) {
            $ids = $filters['teacher_ids'];
            $query->where(fn ($q) => $q->whereIn('se.teacher_id', $ids)
                ->orWhereIn('se.co_teacher_id', $ids));
        }

        if (!empty($filters['day'])) {
            $query->where('ts.day', strtoupper($filters['day']));
        }

        $order = array_flip(self::dayOrder());

        return $query->get()
            // A lab spans several periods: merge them into one meeting.
            ->groupBy(fn ($row) => $row->session_group_id
                ? 'g:' . $row->section_id . ':' . $row->session_group_id
                : 'e:' . $row->entry_id)
            ->map(function (Collection $rows) use ($order) {
                $rows = $rows->sortBy('slot_number')->values();
                $first = $rows->first();
                $day = strtoupper((string) $first->day);

                $teachers = [];
                $teacherIds = [];
                foreach ([['teacher_id', 'teacher_name', 'teacher_code'], ['co_teacher_id', 'co_teacher_name', 'co_teacher_code']] as [$idCol, $nameCol, $codeCol]) {
                    if ($first->{$idCol}) {
                        $teacherIds[] = (int) $first->{$idCol};
                        $teachers[] = $first->{$nameCol} ?: $first->{$codeCol};
                    }
                }

                return [
                    'day' => $day,
                    'day_index' => $order[$day] ?? 99,
                    'start' => (string) $first->start_time,
                    'end' => (string) $rows->last()->end_time,
                    'course_id' => (int) $first->course_id,
                    'course_code' => $first->course_code,
                    'course_name' => $first->course_name,
                    'session_type' => strtoupper((string) $first->session_type),
                    'teachers' => $teachers,
                    'teacher_ids' => $teacherIds,
                    'room' => $first->room_code ?: ($first->room_name ?: 'TBA'),
                    'section_id' => (int) $first->section_id,
                    'section' => trim(($first->batch_name ? $first->batch_name . ' · ' : '') . 'Section ' . $first->section_name),
                ];
            })
            ->sortBy(fn ($s) => sprintf('%02d %s', $s['day_index'], self::padTime($s['start'])))
            ->values();
    }

 
    public function workingDays(): array
    {
        $days = DB::table('time_slots')
            ->distinct()
            ->pluck('day')
            ->map(fn ($d) => strtoupper((string) $d))
            ->all();

        return array_values(array_filter(self::dayOrder(), fn ($d) => in_array($d, $days, true)));
    }

 
    public function teachingSlots(string $day): Collection
    {
        return DB::table('time_slots')
            ->where('day', strtoupper($day))
            ->where('is_break', 0)
            ->orderBy('slot_number')
            ->get(['slot_number', 'start_time', 'end_time']);
    }

    public static function dayOrder(): array
    {
        return config('intellisched.days', [
            'SATURDAY', 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY',
        ]);
    }

    /** "8:00" → "08:00" so times sort as strings. */
    public static function padTime(string $time): string
    {
        return strlen($time) === 4 ? '0' . $time : $time;
    }
}
