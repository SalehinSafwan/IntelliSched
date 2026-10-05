<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'course_code' => 'CSE-3201',
                'course_name' => 'Sample Theory Course',
                'department' => 'CSE',
                'course_type' => 'THEORY',
                'credit_hours' => 3,
                'weekly_theory_classes' => 3,
                'weekly_lab_classes' => 0,
                'status' => 'ACTIVE',
            ],
            [
                'course_code' => 'CSE-3202',
                'course_name' => 'Sample Lab Course',
                'department' => 'CSE',
                'course_type' => 'LAB',
                'credit_hours' => 1,
                'weekly_theory_classes' => 0,
                'weekly_lab_classes' => 1,
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($courses as $data) {
            Course::firstOrCreate(
                ['course_code' => $data['course_code']],
                $data
            );
        }
    }
}