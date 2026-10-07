<?php

namespace App\Services\Scheduling;

use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\CourseOffering;
use App\Models\Exam;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Services\Teacher\TeacherCourseScoringService;

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

        $teachers = Teacher::with([
            'user',
            'expertise',
            'preferences',
            'availability',
            'teachingHistory',
        ])->get();

        $rooms = Room::with([
            'courseEligibility',
        ])->get();

        $timeSlots = TimeSlot::orderBy('day')
            ->orderBy('slot_number')
            ->get();

        $offerings = CourseOffering::with([
            'course',
            'section',
        ])
            ->whereHas('section', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            })
            ->where('academic_term_id', $academicTermId)
            ->get();

        $exams = Exam::with([
            'course',
            'section',
            'room',
            'timeSlot',
        ])
            ->where('academic_term_id', $academicTermId)
            ->whereHas('section', function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            })
            ->get();

        return [
            'academic_term' => $academicTerm,
            'batch' => $batch,
            'sections' => $batch->sections,
            'offerings' => $this->buildOfferings($offerings),
            'teachers' => $this->buildTeachers($teachers),
            'rooms' => $this->buildRooms($rooms),
            'time_slots' => $this->buildTimeSlots($timeSlots),
            'exams' => $exams,
        ];
    }

    private function buildOfferings($offerings): array
    {
        $result = [];

        foreach ($offerings as $offering) {
            $result[] = [
                'id' => $offering->id,
                'course_id' => $offering->course_id,
                'course_code' => $offering->course->course_code,
                'course_name' => $offering->course->course_name,
                'section_id' => $offering->section_id,
                'section_name' => $offering->section->name,
                'weekly_theory_classes' =>
                    $offering->weekly_theory_classes,
                'weekly_lab_classes' =>
                    $offering->weekly_lab_classes,
            ];
        }

        return $result;
    }

    private function buildTeachers($teachers): array
    {
        $result = [];

        foreach ($teachers as $teacher) {

            $scores = [];

            foreach ($teacher->expertise as $expertise) {

                $course = $expertise->course;

                if (!$course) {
                    continue;
                }

                $scores[$course->id] =
                    $this->scoringService->calculate(
                        $teacher,
                        $course
                    );
            }

            $result[] = [
                'id' => $teacher->id,
                'employee_code' => $teacher->employee_code,
                'max_weekly_classes' =>
                    $teacher->max_weekly_classes,

                'suitability_scores' => $scores,

                'availability' => $teacher->availability
                    ->map(function ($availability) {
                        return [
                            'time_slot_id' =>
                                $availability->time_slot_id,
                            'is_available' =>
                                (bool) $availability->is_available,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ];
        }

        return $result;
    }

    private function buildRooms($rooms): array
    {
        return $rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'room_type' => $room->room_type,
                'capacity' => $room->capacity,
                'priority' => $room->priority,

                'eligible_courses' =>
                    $room->courseEligibility
                        ->pluck('course_id')
                        ->values()
                        ->toArray(),
            ];
        })->values()->toArray();
    }

    private function buildTimeSlots($timeSlots): array
    {
        return $timeSlots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'day' => $slot->day,
                'slot_number' => $slot->slot_number,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_break' => (bool) $slot->is_break,
            ];
        })->values()->toArray();
    }
}