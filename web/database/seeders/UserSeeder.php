<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@intellisched.edu',
                'password' => $password,
                'role' => 'admin',
                'is_active' => 1,
            ],
            [
                'name' => 'Academic Coordinator',
                'email' => 'coord@intellisched.edu',
                'password' => $password,
                'role' => 'coordinator',
                'is_active' => 1,
            ],
            [
                'name' => 'Dr. Ahmed Rahman',
                'email' => 'teacher@intellisched.edu',
                'password' => $password,
                'role' => 'teacher',
                'is_active' => 1,
            ],
            [
                'name' => 'Sabbir Ahmed (CR)',
                'email' => 'student@intellisched.edu',
                'password' => $password,
                'role' => 'student',
                'is_active' => 1,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                [
                    'email' => $user['email'],
                ],
                $user
            );
        }

        // Accounts linked to teacher profiles (see TeacherSeeder).
        // Dr. Ahmed Rahman uses the demo login teacher@intellisched.edu.
        // firstOrCreate keeps existing passwords and roles intact.
        $teacherUsers = [
            [
                'name' => 'Dr. Farhana Islam',
                'email' => 'farhana.islam@example.com',
            ],
            [
                'name' => 'Mr. Tanvir Hasan',
                'email' => 'tanvir.hasan@example.com',
            ],
        ];

        foreach ($teacherUsers as $teacherUser) {
            User::firstOrCreate(
                [
                    'email' => $teacherUser['email'],
                ],
                [
                    'name' => $teacherUser['name'],
                    'password' => $password,
                    'role' => 'TEACHER',
                    'is_active' => 1,
                ]
            );
        }

        // The demo student is CR of the first section, once sections
        // exist (run after SectionSeeder to link them).
        $section = Section::orderBy('id')->first();

        if ($section) {
            User::where('email', 'student@intellisched.edu')
                ->whereNull('section_id')
                ->update(['section_id' => $section->id, 'class_rep' => 'CR']);
        }
    }
}