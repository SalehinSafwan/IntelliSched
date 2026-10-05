<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'room_code' => 'R-101',
                'room_name' => 'Classroom 101',
                'room_type' => 'THEORY',
                'capacity' => 60,
                'priority' => 1,
                'building' => 'Academic Building',
                'floor' => '1',
                'status' => 'ACTIVE',
            ],
            [
                'room_code' => 'LAB-01',
                'room_name' => 'Computer Lab 01',
                'room_type' => 'LAB',
                'capacity' => 30,
                'priority' => 10,
                'building' => 'Academic Building',
                'floor' => '2',
                'status' => 'ACTIVE',
            ],
            [
                'room_code' => 'LAB-T1',
                'room_name' => 'Theory and Lab Room',
                'room_type' => 'MULTIPURPOSE',
                'capacity' => 60,
                'priority' => 5,
                'building' => 'Academic Building',
                'floor' => '1',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($rooms as $data) {
            Room::firstOrCreate(
                ['room_code' => $data['room_code']],
                $data
            );
        }
    }
}