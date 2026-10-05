<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicTerm;

class AcademicTermSeeder extends Seeder
{
    public function run(): void
    {
        AcademicTerm::firstOrCreate(
            [
                'academic_year' => '2026',
                'term' => 'FALL',
            ],
            [
                'name' => 'Fall 2026',
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-31',
                'status' => 'ACTIVE',
            ]
        );
    }
}