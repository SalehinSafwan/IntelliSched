<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with role accounts.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Admin User
        User::updateOrCreate(
            ['email' => 'admin@intellisched.edu'],
            [
                'name' => 'System Administrator',
                'password' => $password,
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Coordinator User
        User::updateOrCreate(
            ['email' => 'coordinator@intellisched.edu'],
            [
                'name' => 'Academic Coordinator',
                'password' => $password,
                'role' => 'coordinator',
                'is_active' => true,
            ]
        );

        // Coordinator User Alias
        User::updateOrCreate(
            ['email' => 'coord@intellisched.edu'],
            [
                'name' => 'Academic Coordinator',
                'password' => $password,
                'role' => 'coordinator',
                'is_active' => true,
            ]
        );

        // Teacher User
        User::updateOrCreate(
            ['email' => 'teacher@intellisched.edu'],
            [
                'name' => 'Dr. Ahmed Rahman',
                'password' => $password,
                'role' => 'teacher',
                'is_active' => true,
            ]
        );

        // Student User
        User::updateOrCreate(
            ['email' => 'student@intellisched.edu'],
            [
                'name' => 'Sabbir Ahmed (CR)',
                'password' => $password,
                'role' => 'student',
                'is_active' => true,
            ]
        );
    }
}
