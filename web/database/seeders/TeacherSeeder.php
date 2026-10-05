<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'email' => 'ahmed.rahman@example.com',
                'employee_code' => 'T001',
                'department' => 'CSE',
                'max_weekly_classes' => 12,
                'status' => 'ACTIVE',
            ],
            [
                'email' => 'farhana.islam@example.com',
                'employee_code' => 'T002',
                'department' => 'CSE',
                'max_weekly_classes' => 12,
                'status' => 'ACTIVE',
            ],
            [
                'email' => 'tanvir.hasan@example.com',
                'employee_code' => 'T003',
                'department' => 'CSE',
                'max_weekly_classes' => 12,
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($teachers as $data) {

            $user = User::where('email', $data['email'])->firstOrFail();

            Teacher::updateOrCreate(
                [
                    'employee_code' => $data['employee_code'],
                ],
                [
                    'user_id' => $user->id,
                    'department' => $data['department'],
                    'max_weekly_classes' => $data['max_weekly_classes'],
                    'status' => $data['status'],
                ]
            );
        }
    }
}