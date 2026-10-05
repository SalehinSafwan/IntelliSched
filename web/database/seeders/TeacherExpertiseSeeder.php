<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeacherExpertise;

class TeacherExpertiseSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::all();
        $courses = Course::all();

        foreach ($teachers as $teacher) {

            foreach ($courses as $course) {

                $score = match ($teacher->employee_code) {
                    'T001' => 90,
                    'T002' => 80,
                    'T003' => 70,
                    default => 50,
                };

                TeacherExpertise::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'expertise_score' => $score,
                    ]
                );
            }
        }
    }
}