<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batch;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            [
                'name' => 'CSE 47',
                'department' => 'CSE',
                'program' => 'BSc CSE',
                'admission_year' => 2022,
                'seniority_order' => 1,
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'CSE 48',
                'department' => 'CSE',
                'program' => 'BSc CSE',
                'admission_year' => 2023,
                'seniority_order' => 2,
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($batches as $data) {
            Batch::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}