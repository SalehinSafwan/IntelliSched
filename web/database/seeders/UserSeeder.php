<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Dr. Ahmed Rahman',
                'email' => 'ahmed.rahman@example.com',
                'password' => Hash::make('password123'),
                'role' => 'TEACHER',
                'is_active' => 1,
            ],
            [
                'name' => 'Dr. Farhana Islam',
                'email' => 'farhana.islam@example.com',
                'password' => Hash::make('password123'),
                'role' => 'TEACHER',
                'is_active' => 1,
            ],
            [
                'name' => 'Mr. Tanvir Hasan',
                'email' => 'tanvir.hasan@example.com',
                'password' => Hash::make('password123'),
                'role' => 'TEACHER',
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
    }
}