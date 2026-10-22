<?php

namespace App\Services\Scheduling;

use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\CourseOffering;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Services\Teacher\TeacherCourseScoringService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SchedulerInputService
{
    public function __construct(
        private TeacherCourseScoringService $scoringService
    ) {
    }

    public function build(
        int $batchId,
        int $academicTermId
    ): array {
        $batch = Batch::with([
            'sections.courseOfferings.course',
        ])->findOrFail($batchId);

        $academicTerm = AcademicTerm::findOrFail($academicTermId);

        // Inactive teachers, rooms, sections and offerings are never
        // scheduled.
        $teachers = Teacher::with([
            'user',
            'expertise.course',
            'preferences',
            'availability',
            'teachingHistory',
        ])->where('status', 'ACTIVE')->get();

        $rooms = Room::with([
            'courseEligibility',
        ])->where('status', 'ACTIVE')->get();

        $timeSlots = TimeSlot::orderBy('day')
            ->orderBy('slot_number')
            ->get();

        $offerings = CourseOffering::with([
            'course',
            'section',
        ])
            ->whereHas('section', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId)
                    ->where('status', 'ACTIVE');
            })
            ->where('academic_term_id', $academicTermId)
            ->where('status', 'ACTIVE')
            ->get();

        $existingScheduleEntries = $this->buildExistingScheduleEntries(
            $batchId,
            $academicTermId
        );

        return [
            'academic_term' => [
                'id' => $academicTerm->id,
                'name' => $academicTerm->name,
                'academic_year' => $academicTerm->academic_year,
                'term' => $academicTerm->term,
                'start_date' => Carbon::parse($academicTerm->start_date)
                    ->format('Y-m-d\TH:i:s'),
                'end_date' => Carbon::parse($academicTerm->end_date)
                    ->format('Y-m-d\TH:i:s'),
                'status' => $academicTerm->status,
            ],

            
            'batch' => [
                'id' => (int) $batch->id,
                'name' => $batch->name,
                'department' => $batch->department,
                'program' => $batch->program,
                'admission_year' => (int) $batch->admission_year,
                'seniority_order' => (int) $batch->seniority_order,
                'status' => $batch->status,

                // With seniority_order (1 = most senior) this sets how
                // strongly the batch prefers morning classes.
                'active_batch_count' =>
                    max(1, Batch::where('status', 'ACTIVE')->count()),
            ],


            'sections' => $this->buildSections(
                $batch->sections->where('status', 'ACTIVE')
            ),

            'offerings' => $this->buildOfferings($offerings),

            'teachers' => $this->buildTeachers($teachers, $timeSlots),

            'rooms' => $this->buildRooms($rooms),

            'time_slots' => $this->buildTimeSlots($timeSlots),

            'exam_room_blocks' => $this->buildExamRoomBlocks($academicTermId),

            'existing_schedule_entries' => $existingScheduleEntries,
        ];
    }

    /**
     * Build the sections of the batch being scheduled.
     */
    private function buildSections(Collection $sections): array
    {
        return $sections->map(function ($section) {
            return [
                'id' => (int) $section->id,
                'batch_id' => (int) $section->batch_id,
                'name' => $section->name,
                'student_count' => (int) $section->student_count,
                'status' => $section->status,
            ];
        })->values()->toArray();
    }

    /**
     * Build the course offerings for the batch being scheduled.
     */
    private function buildOfferings(Collection $offerings): array
    {
        $result = [];

        foreach ($offerings as $offering) {
            $result[] = [
                'id' => (int) $offering->id,
                'course_id' => (int) $offering->course_id,
                'course_code' => $offering->course->course_code,
                'course_name' => $offering->course->course_name,
                'section_id' => (int) $offering->section_id,
                'section_name' => $offering->section->name,
                'weekly_theory_classes' =>
                    (int) $offering->weekly_theory_classes,
                'weekly_lab_classes' =>
                    (int) $offering->weekly_lab_classes,
            ];
        }

        return $result;
    }

    /**
     * Build teacher data and course suitability scores.
     */
    private function buildTeachers(Collection $teachers, Collection $timeSlots): array
    {
        $teachingSlotIds = $timeSlots
            ->filter(fn ($slot) => !$slot->is_break)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $result = [];

        foreach ($teachers as $teacher) {
            $scores = [];

            foreach ($teacher->expertise as $expertise) {
                $course = $expertise->course;

                if (!$course) {
                    continue;
                }

                $scores[(string) $course->id] =
                    $this->scoringService->calculate(
                        $teacher,
                        $course
                    );
            }

            $result[] = [
                'id' => (int) $teacher->id,
                'employee_code' => $teacher->employee_code,

                // This value is interpreted by the C# solver
                // as the maximum weekly teaching periods.
                'max_weekly_classes' =>
                    (int) $teacher->max_weekly_classes,

                // Always a JSON object: an empty PHP array would be
                // encoded as [] and rejected by the C# dictionary.
                'suitability_scores' => (object) $scores,

                // Every teaching period: a period without a record
                // counts as available with no preference.
                'availability' => $this->buildAvailability(
                    $teacher,
                    $teachingSlotIds
                ),
            ];
        }

        return $result;
    }

    private function buildAvailability(Teacher $teacher, Collection $teachingSlotIds): array
    {
        $records = $teacher->availability->keyBy(fn ($a) => (int) $a->time_slot_id);

        return $teachingSlotIds->map(function ($slotId) use ($records) {
            $record = $records->get($slotId);

            return [
                'time_slot_id' => $slotId,
                'is_available' => $record ? (bool) $record->is_available : true,
                'preference' => $record ? (int) $record->preference : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Rooms reserved by published exam schedules of this term. The
     * solver keeps classes out of a room on the exam's weekday while
     * the exam window overlaps the period.
     */
    private function buildExamRoomBlocks(int $academicTermId): array
    {
        return DB::table('exam_schedule_courses as esc')
            ->join('exam_schedules as es', 'es.id', '=', 'esc.exam_schedule_id')
            ->join('exam_schedule_rooms as esr', 'esr.exam_schedule_id', '=', 'es.id')
            ->join('batches as b', 'b.id', '=', 'es.batch_id')
            ->join('courses as c', 'c.id', '=', 'esc.course_id')
            ->where('es.status', 'PUBLISHED')
            ->where('es.academic_term_id', $academicTermId)
            ->whereNotNull('esc.exam_date')
            ->get([
                'esr.room_id', 'esc.exam_date', 'es.start_time', 'es.end_time',
                'b.name as batch_name', 'c.course_code',
            ])
            ->map(fn ($row) => [
                'room_id' => (int) $row->room_id,
                'day' => strtoupper(Carbon::parse($row->exam_date)->format('l')),
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'exam_date' => Carbon::parse($row->exam_date)->toDateString(),
                'label' => "{$row->batch_name} {$row->course_code}",
            ])
            ->values()
            ->toArray();
    }

    /**
     * Build rooms and their eligible course IDs.
     */
    private function buildRooms(Collection $rooms): array
    {
        return $rooms->map(function ($room) {
            return [
                'id' => (int) $room->id,
                'room_code' => $room->room_code,
                'room_type' => $room->room_type,
                'capacity' => (int) $room->capacity,
                'priority' => (int) $room->priority,

                'eligible_courses' => $room->courseEligibility
                    ->pluck('course_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Build the weekly time-slot list.
     */
    private function buildTimeSlots(Collection $timeSlots): array
    {
        return $timeSlots->map(function ($slot) {
            return [
                'id' => (int) $slot->id,
                'day' => $slot->day,
                'slot_number' => (int) $slot->slot_number,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_break' => (bool) $slot->is_break,
            ];
        })->values()->toArray();
    }

    /**
     * Fetch published timetable entries belonging to OTHER batches
     * in the requested academic term.
     *
     * schedule_entries stores one row per occupied time slot.
     * Lab rows are combined using schedule_run_id + session_group_id.
     * Joint sessions (labs, combined theory) also store co_teacher_id.
     *
     * Assumptions:
     * - schedule_runs has batch_id and status columns.
     * - PUBLISHED identifies a timetable that should block resources.
     * - course_offerings has academic_term_id.
     */
    private function buildExistingScheduleEntries(
        int $targetBatchId,
        int $academicTermId
    ): array {
        $rows = DB::table('schedule_entries as se')
            ->join(
                'schedule_runs as sr',
                'sr.id',
                '=',
                'se.schedule_run_id'
            )
            ->join(
                'course_offerings as co',
                'co.id',
                '=',
                'se.course_offering_id'
            )
            ->join(
                'sections as s',
                's.id',
                '=',
                'co.section_id'
            )
            ->where('co.academic_term_id', $academicTermId)
            ->where('s.batch_id', '!=', $targetBatchId)
            ->where('sr.status', 'PUBLISHED')
            ->select([
                'se.id as entry_id',
                'se.schedule_run_id',
                'se.course_offering_id',
                'se.teacher_id',
                'se.co_teacher_id',
                'se.room_id',
                'se.time_slot_id',
                'se.session_type',
                'se.session_group_id',
                'co.course_id',
                'co.section_id',
                's.batch_id',
            ])
            ->orderBy('se.schedule_run_id')
            ->orderBy('se.id')
            ->get();

        /*
         * A lab has multiple rows sharing session_group_id.
         * Theory rows without a group ID are kept as individual meetings.
         */
        $groups = $rows->groupBy(function ($row) {
            $sessionType = strtolower((string) $row->session_type);

            if (
                $sessionType === 'lab'
                && $row->session_group_id !== null
                && $row->session_group_id !== ''
            ) {
                return 'lab:'
                    . $row->schedule_run_id
                    . ':'
                    . $row->session_group_id;
            }

            // Each ungrouped entry is its own meeting.
            return 'entry:' . $row->entry_id;
        });

        $result = [];

        foreach ($groups as $group) {
            $first = $group->first();

            $slotIds = $group
                ->pluck('time_slot_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            sort($slotIds);

            $sessionType = ucfirst(
                strtolower((string) $first->session_type)
            );

            /*
             * A lab without session_group_id cannot reliably be
             * reconstructed as a three-period meeting.
             * Fail clearly rather than silently sending an incorrect
             * lab duration to the solver.
             */
            if (
                strtolower((string) $first->session_type) === 'lab'
                && (
                    $first->session_group_id === null
                    || $first->session_group_id === ''
                )
            ) {
                throw new RuntimeException(
                    "Published lab entry {$first->entry_id} has no "
                    . 'session_group_id. Group its three schedule rows '
                    . 'before generating another batch timetable.'
                );
            }

            $result[] = [
                'id' => (int) $first->entry_id,
                'batch_id' => (int) $first->batch_id,
                'section_id' => (int) $first->section_id,
                'course_id' => (int) $first->course_id,
                'teacher_ids' => array_values(array_filter([
                    (int) $first->teacher_id,
                    $first->co_teacher_id === null
                        ? null
                        : (int) $first->co_teacher_id,
                ])),
                'room_id' => (int) $first->room_id,
                'session_type' => $sessionType,
                'time_slot_ids' => $slotIds,
                'is_active' => true,
            ];
        }

        return $result;
    }
}
