<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeSlot;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            'SUNDAY',
            'MONDAY',
            'TUESDAY',
            'WEDNESDAY',
            'THURSDAY',
        ];


        $dailySlots = [
            [
                'slot_number' => 1,
                'start_time' => '08:00',
                'end_time' => '08:50',
                'is_break' => 0,
            ],

            [
                'slot_number' => 2,
                'start_time' => '08:50',
                'end_time' => '09:40',
                'is_break' => 0,
            ],

            [
                'slot_number' => 3,
                'start_time' => '09:40',
                'end_time' => '10:30',
                'is_break' => 0,
            ],

            [
                'slot_number' => 4,
                'start_time' => '10:30',
                'end_time' => '10:40',
                'is_break' => 1,
            ],

            [
                'slot_number' => 5,
                'start_time' => '10:40',
                'end_time' => '11:30',
                'is_break' => 0,
            ],

            [
                'slot_number' => 6,
                'start_time' => '11:30',
                'end_time' => '12:20',
                'is_break' => 1,
            ],

            [
                'slot_number' => 7,
                'start_time' => '12:20',
                'end_time' => '13:10',
                'is_break' => 0,
            ],

            [
                'slot_number' => 8,
                'start_time' => '13:10',
                'end_time' => '14:30',
                'is_break' => 1,
            ],

            [
                'slot_number' => 9,
                'start_time' => '14:30',
                'end_time' => '15:20',
                'is_break' => 0,
            ],

            [
                'slot_number' => 10,
                'start_time' => '15:20',
                'end_time' => '16:10',
                'is_break' => 0,
            ],

            [
                'slot_number' => 11,
                'start_time' => '16:10',
                'end_time' => '17:00',
                'is_break' => 0,
            ],
        ];

        

        foreach ($days as $day) {
            foreach ($dailySlots as $slot) {

                TimeSlot::updateOrCreate(
                    [
                        'day' => $day,
                        'slot_number' => $slot['slot_number'],
                    ],
                    [
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'is_break' => $slot['is_break'],
                    ]
                );
            }
        }
    }
}