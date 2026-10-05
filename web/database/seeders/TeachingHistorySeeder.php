<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeachingHistory;

class TeachingHistorySeeder extends Seeder
{
    public function run(): void
    {
        $term = AcademicTerm::firstOrFail();

        $batch = Batch::orderBy('seniority_order')->firstOrFail();

        $teachers = Teacher::all();
        $courses = Course::all();

        foreach ($teachers as $teacher) {

            foreach ($courses as $course) {

                TeachingHistory::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'course_id' => $course->id,
                        'academic_term_id' => $term->id,
                        'batch_id' => $batch->id,
                    ],
                    [
                        'times_taught' => 1,
                    ]
                );
            }
        }
    }
}