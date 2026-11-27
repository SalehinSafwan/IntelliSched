<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A CR account for every active section of every batch.
 *
 * Login: cr.<batch>.<section>@intellisched.edu / password123,
 * e.g. cr.cse47.b@intellisched.edu. A section that already has an
 * active CR (such as the demo student) keeps it. Safe to re-run.
 */
class ClassRepSeeder extends Seeder
{
    private const NAMES = [
        'Tanjim Hossain', 'Nafisa Rahman', 'Arif Chowdhury', 'Sadia Akter', 'Mahir Faisal',
        'Raisa Karim', 'Fahim Shahriar', 'Lamia Sultana', 'Rakib Hasan', 'Tasnim Jahan',
        'Imran Kabir', 'Nusaiba Islam', 'Shuvo Das', 'Mehjabin Noor', 'Ayaan Siddique',
    ];

    public function run(): void
    {
        $password = Hash::make('password123');

        $sections = Section::with('batch')
            ->where('status', 'ACTIVE')
            ->get()
            ->sortBy(fn ($section) => [(int) ($section->batch?->seniority_order ?? 99), $section->name])
            ->values();

        // UserSeeder runs before sections exist on a fresh seed, so
        // link the demo CR here (first section) if still unlinked.
        if ($first = $sections->first()) {
            User::where('email', 'student@intellisched.edu')
                ->whereNull('section_id')
                ->update(['section_id' => $first->id, 'class_rep' => 'CR']);
        }

        foreach ($sections as $index => $section) {
            $hasCr = User::where('section_id', $section->id)
                ->where('class_rep', 'CR')
                ->where('is_active', 1)
                ->exists();

            if ($hasCr) {
                continue;
            }

            $batch = Str::slug($section->batch?->name ?? 'batch' . $section->batch_id, '');
            $email = 'cr.' . $batch . '.' . Str::slug($section->name, '') . '@intellisched.edu';

            User::updateOrCreate(['email' => strtolower($email)], [
                'name' => self::NAMES[$index % count(self::NAMES)],
                'password' => $password,
                'role' => 'student',
                'section_id' => $section->id,
                'class_rep' => 'CR',
                'is_active' => 1,
            ]);
        }
    }
}
