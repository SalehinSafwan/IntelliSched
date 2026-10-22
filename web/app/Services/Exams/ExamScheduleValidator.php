<?php

namespace App\Services\Exams;

use App\Models\ExamSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamScheduleValidator
{
    /**
     * Problems that prevent publishing an exam schedule.
     * An empty list means it can be published.
     */
    public function validate(ExamSchedule $schedule): array
    {
        $schedule->loadMissing(['rooms', 'courses.course', 'batch.sections', 'academicTerm']);

        $errors = [];
        $term = $schedule->academicTerm;
        $rooms = $schedule->rooms;
        $courses = $schedule->courses;

        // 1. Rooms must be chosen and seat the whole batch.
        if ($rooms->isEmpty()) {
            $errors[] = 'Choose at least one exam room.';
        }

        $students = $schedule->batch->sections
            ->where('status', 'ACTIVE')
            ->sum(fn ($section) => (int) $section->student_count);
        $seats = $rooms->sum(fn ($room) => (int) $room->capacity);

        if ($rooms->isNotEmpty() && $seats < $students) {
            $errors[] = "The selected rooms seat {$seats} students, but {$schedule->batch->name} has {$students}.";
        }

        // 2. Every course needs a date inside the term, and the batch
        //    sits at most one exam per day.
        if ($courses->isEmpty()) {
            $errors[] = 'This batch has no courses in the term. Set its course list first.';
        }

        foreach ($courses as $examCourse) {
            $code = $examCourse->course?->course_code;

            if (!$examCourse->exam_date) {
                $errors[] = "{$code} has no exam date.";
                continue;
            }

            if ($term && ($examCourse->exam_date->lt(Carbon::parse($term->start_date))
                    || $examCourse->exam_date->gt(Carbon::parse($term->end_date)))) {
                $errors[] = "{$code}: {$examCourse->exam_date->format('d M Y')} is outside {$term->name}.";
            }
        }

        $courses->filter(fn ($c) => $c->exam_date)
            ->groupBy(fn ($c) => $c->exam_date->toDateString())
            ->filter(fn ($group) => $group->count() > 1)
            ->each(function ($group, $date) use (&$errors) {
                $codes = $group->map(fn ($c) => $c->course?->course_code)->implode(', ');
                $errors[] = 'Two exams on ' . Carbon::parse($date)->format('d M Y') . " ({$codes}). A batch sits one exam per day.";
            });

        if ($rooms->isEmpty()) {
            return $errors;
        }

        $roomIds = $rooms->pluck('id')->all();
        $roomCodes = $rooms->pluck('room_code', 'id');

        foreach ($courses->filter(fn ($c) => $c->exam_date) as $examCourse) {
            $date = $examCourse->exam_date;
            $label = "{$examCourse->course?->course_code} on {$date->format('D d M')}";

            // 3. No other published exam in the same room at the same time.
            $clashes = DB::table('exam_schedule_courses as esc')
                ->join('exam_schedules as es', 'es.id', '=', 'esc.exam_schedule_id')
                ->join('exam_schedule_rooms as esr', 'esr.exam_schedule_id', '=', 'es.id')
                ->join('batches as b', 'b.id', '=', 'es.batch_id')
                ->join('courses as c', 'c.id', '=', 'esc.course_id')
                ->where('es.id', '!=', $schedule->id)
                ->where('es.status', 'PUBLISHED')
                // exam_date is a DATE stored at midnight.
                ->where('esc.exam_date', $date->toDateString() . ' 00:00:00')
                ->whereIn('esr.room_id', $roomIds)
                ->where('es.start_time', '<', $schedule->end_time)
                ->where('es.end_time', '>', $schedule->start_time)
                ->get(['esr.room_id', 'b.name as batch_name', 'c.course_code']);

            foreach ($clashes as $clash) {
                $errors[] = "{$label}: room {$roomCodes[$clash->room_id]} already hosts the "
                    . "{$clash->batch_name} {$clash->course_code} exam at that time.";
            }

            // 4. No class or lab of a published routine may use the
            //    room during the exam window on that weekday.
            $classes = DB::table('schedule_entries as se')
                ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
                ->join('time_slots as ts', 'ts.id', '=', 'se.time_slot_id')
                ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
                ->join('courses as c', 'c.id', '=', 'co.course_id')
                ->join('sections as s', 's.id', '=', 'co.section_id')
                ->join('batches as b', 'b.id', '=', 's.batch_id')
                ->where('sr.status', 'PUBLISHED')
                ->where('sr.academic_term_id', $schedule->academic_term_id)
                ->whereIn('se.room_id', $roomIds)
                ->whereRaw('UPPER(ts.day) = ?', [strtoupper($date->format('l'))])
                ->where('ts.start_time', '<', $schedule->end_time)
                ->where('ts.end_time', '>', $schedule->start_time)
                ->get(['se.room_id', 'ts.start_time', 'ts.end_time', 'c.course_code', 'b.name as batch_name', 's.name as section_name']);

            foreach ($classes as $class) {
                $errors[] = "{$label}: room {$roomCodes[$class->room_id]} has a {$class->batch_name} "
                    . "(Section {$class->section_name}) {$class->course_code} class {$class->start_time}–{$class->end_time}.";
            }
        }

        return array_values(array_unique($errors));
    }
}
