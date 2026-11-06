<?php

namespace App\Services\Teacher;

use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherSectionService
{
    /**
     * IDs of sections the teacher teaches in a published routine
     * (as the main or the pair teacher).
     */
    public function teachingSectionIds(Teacher $teacher): array
    {
        return DB::table('schedule_entries as se')
            ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->where('sr.status', 'PUBLISHED')
            ->where(fn ($query) => $query
                ->where('se.teacher_id', $teacher->id)
                ->orWhere('se.co_teacher_id', $teacher->id))
            ->distinct()
            ->pluck('co.section_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function supervisedSectionIds(Teacher $teacher): array
    {
        return $teacher->supervisions()
            ->pluck('section_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Every section the teacher may plan with and message: the ones
     * they supervise plus the ones they teach. Each section carries
     * `supervises` and `teaches` flags.
     */
    public function reachableSections(Teacher $teacher): Collection
    {
        $supervised = $this->supervisedSectionIds($teacher);
        $teaching = $this->teachingSectionIds($teacher);

        return Section::with('batch')
            ->whereIn('id', array_values(array_unique(array_merge($supervised, $teaching))))
            ->get()
            ->each(function (Section $section) use ($supervised, $teaching) {
                $section->supervises = in_array((int) $section->id, $supervised, true);
                $section->teaches = in_array((int) $section->id, $teaching, true);
            })
            ->sortBy(fn (Section $section) => [
                $section->supervises ? 0 : 1,
                (int) ($section->batch?->seniority_order ?? 99),
                $section->batch?->name,
                $section->name,
            ])
            ->values();
    }

    public function canReach(Teacher $teacher, int $sectionId): bool
    {
        return in_array($sectionId, $this->supervisedSectionIds($teacher), true)
            || in_array($sectionId, $this->teachingSectionIds($teacher), true);
    }
}
