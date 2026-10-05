<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeacherPreference;

class TeacherPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::all();
        $courses = Course::all();

        foreach ($teachers as $teacher) {

            foreach ($courses as $course) {

                $score = match ($teacher->employee_code) {
                    'T001' => 85,
                    'T002' => 75,
                    'T003' => 65,
                    default => 50,
                };

                TeacherPreference::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'preference_score' => $score,
                    ]
                );
            }
        }
    }
}