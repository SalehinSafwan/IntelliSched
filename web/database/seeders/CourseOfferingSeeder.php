<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Section;

class CourseOfferingSeeder extends Seeder
{
    public function run(): void
    {
        $term = AcademicTerm::where('status', 'ACTIVE')->firstOrFail();

        $courses = Course::all();

        $sections = Section::all();

        foreach ($sections as $section) {

            foreach ($courses as $course) {

                CourseOffering::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'section_id' => $section->id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'weekly_theory_classes' =>
                            $course->weekly_theory_classes,

                        'weekly_lab_classes' =>
                            $course->weekly_lab_classes,

                        'status' => 'ACTIVE',
                    ]
                );
            }
        }
    }
}