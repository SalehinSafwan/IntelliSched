<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            // Academic foundation
            AcademicTermSeeder::class,
            BatchSeeder::class,
            SectionSeeder::class,
            CourseSeeder::class,

            // Infrastructure
            RoomSeeder::class,
            TimeSlotSeeder::class,

            // Users and teachers
            UserSeeder::class,
            TeacherSeeder::class,

            // Course-teacher data
            TeacherExpertiseSeeder::class,
            TeacherPreferenceSeeder::class,
            TeacherAvailabilitySeeder::class,
            TeachingHistorySeeder::class,

            // Course delivery
            CourseOfferingSeeder::class,

            // Room restrictions
            RoomCourseEligibilitySeeder::class,

            // Existing academic conflicts
            ExamSeeder::class,
        ]);
    }
}