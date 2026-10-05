<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TimeSlot;

class TeacherAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::all();

        $timeSlots = TimeSlot::where('is_break', 0)->get();

        foreach ($teachers as $teacher) {

            foreach ($timeSlots as $timeSlot) {

                TeacherAvailability::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'time_slot_id' => $timeSlot->id,
                    ],
                    [
                        'is_available' => 1,
                        'reason' => null,
                    ]
                );
            }
        }
    }
}