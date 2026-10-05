<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Section;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $term = AcademicTerm::where('status', 'ACTIVE')->firstOrFail();

        $course = Course::first();

        $section = Section::first();

        if (!$course || !$section) {
            return;
        }

        Exam::updateOrCreate(
            [
                'course_id' => $course->id,
                'section_id' => $section->id,
                'academic_term_id' => $term->id,
                'exam_type' => 'MIDTERM',
            ],
            [
                'exam_date' => '2026-11-15',
                'room_id' => null,
                'time_slot_id' => null,
                'status' => 'SCHEDULED',
            ]
        );
    }
}