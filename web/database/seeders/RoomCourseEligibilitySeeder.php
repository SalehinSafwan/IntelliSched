<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Room;
use App\Models\RoomCourseEligibility;

class RoomCourseEligibilitySeeder extends Seeder
{
    public function run(): void
    {
        $labRooms = Room::whereIn('room_type', [
            'LAB',
            'MULTIPURPOSE'
        ])->get();

        $labCourses = Course::whereIn('course_type', [
            'LAB',
            'BOTH'
        ])->get();

        foreach ($labRooms as $room) {

            foreach ($labCourses as $course) {

                RoomCourseEligibility::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'course_id' => $course->id,
                    ],
                    []
                );
            }
        }
    }
}