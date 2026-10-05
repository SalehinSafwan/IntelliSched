<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batch;
use App\Models\Section;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $batches = Batch::all();

        foreach ($batches as $batch) {
            foreach (['A', 'B'] as $sectionName) {
                Section::firstOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'name' => $sectionName,
                    ],
                    [
                        'student_count' => 60,
                        'status' => 'ACTIVE',
                    ]
                );
            }
        }
    }
}