<?php

namespace App\Services\Scheduling;

use Illuminate\Support\Facades\DB;

class RoutineViewService
{
    public function __construct(
        private TimetableGridBuilder $gridBuilder
    ) {
    }

    /**
     * Base query for schedule runs with batch, term and approver names.
     */
    public function runQuery()
    {
        return DB::table('schedule_runs as sr')
            ->join('batches as b', 'b.id', '=', 'sr.batch_id')
            ->join(
                'academic_terms as atm',
                'atm.id',
                '=',
                'sr.academic_term_id'
            )
            ->leftJoin('users as u', 'u.id', '=', 'sr.generated_by')
            ->select([
                'sr.id',
                'sr.batch_id',
                'b.name as batch_name',
                'sr.academic_term_id',
                'atm.name as term_name',
                'sr.version',
                'sr.status',
                'sr.objective_score',
                'sr.generated_at',
                'sr.generated_by',
                'u.name as generated_by_name',
                'sr.created_at',
                'sr.updated_at',
            ]);
    }

    /**
     * A saved run with its per-section grid and course teacher pairs,
     * or null when the run does not exist.
     */
    public function forRun(int $scheduleRunId): ?array
    {
        $run = $this->runQuery()
            ->where('sr.id', $scheduleRunId)
            ->first();

        if (!$run) {
            return null;
        }

        $rows = DB::table('schedule_entries as se')
            ->join(
                'course_offerings as co',
                'co.id',
                '=',
                'se.course_offering_id'
            )
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->join('teachers as t', 't.id', '=', 'se.teacher_id')
            ->leftJoin('users as tu', 'tu.id', '=', 't.user_id')
            ->leftJoin('teachers as ct', 'ct.id', '=', 'se.co_teacher_id')
            ->leftJoin('users as ctu', 'ctu.id', '=', 'ct.user_id')
            ->join('rooms as r', 'r.id', '=', 'se.room_id')
            ->where('se.schedule_run_id', $scheduleRunId)
            ->select([
                'se.id as entry_id',
                'se.course_offering_id',
                'se.session_type',
                'se.session_group_id',
                'se.time_slot_id',
                'c.id as course_id',
                'c.course_code',
                'c.course_name',
                's.id as section_id',
                's.name as section_name',
                't.employee_code as teacher_code',
                'tu.name as teacher_name',
                'ct.employee_code as co_teacher_code',
                'ctu.name as co_teacher_name',
                'r.room_code',
            ])
            ->orderBy('se.id')
            ->get();

        // One row per occupied slot: regroup lab periods into one
        // meeting using session_group_id.
        $entries = $rows
            ->groupBy(fn ($row) => $row->session_group_id
                ? 'group:' . $row->session_group_id
                : 'entry:' . $row->entry_id)
            ->map(function ($group) {
                $first = $group->first();

                $teachers = array_values(array_filter([
                    $first->teacher_name ?? $first->teacher_code,
                    $first->co_teacher_name ?? $first->co_teacher_code,
                ]));

                return [
                    'section_id' => (int) $first->section_id,
                    'time_slot_ids' => $group->pluck('time_slot_id')
                        ->map(fn ($id) => (int) $id)
                        ->all(),
                    'course_id' => (int) $first->course_id,
                    'course_code' => $first->course_code,
                    'course_name' => $first->course_name,
                    'session_type' => $first->session_type,
                    'teacher_mode' => count($teachers) > 1 ? 'Joint' : 'Single',
                    'teachers' => $teachers,
                    'room_code' => $first->room_code,
                ];
            })
            ->values();

        $sections = DB::table('sections')
            ->where('batch_id', $run->batch_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $grid = $this->gridBuilder->build(
            DB::table('time_slots')->get(),
            $sections,
            $entries
        );

        // Teachers of each course, across all sections.
        $coursePairs = $entries
            ->groupBy('course_id')
            ->map(fn ($group) => [
                'course_code' => $group->first()['course_code'],
                'course_name' => $group->first()['course_name'],
                'teachers' => $group->pluck('teachers')
                    ->flatten()
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->sortBy('course_code')
            ->values();

        return [
            'run' => $run,
            'grid' => $grid,
            'coursePairs' => $coursePairs,
            'periodCount' => $rows->count(),
        ];
    }
}
