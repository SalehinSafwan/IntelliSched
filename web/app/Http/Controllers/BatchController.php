<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Section;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(): View
    {
        $current = AcademicTerm::current();

        $batches = Batch::with('sections')
            ->orderBy('seniority_order')
            ->get();

        // Courses offered to each batch in the current term.
        $courseCounts = $current
            ? CourseOffering::join('sections', 'sections.id', '=', 'course_offerings.section_id')
                ->where('course_offerings.academic_term_id', $current->id)
                ->select('sections.batch_id', DB::raw('COUNT(DISTINCT course_offerings.course_id) as total'))
                ->groupBy('sections.batch_id')
                ->pluck('total', 'batch_id')
            : collect();

        $published = DB::table('schedule_runs')
            ->where('status', 'PUBLISHED')
            ->when($current, fn ($query) => $query->where('academic_term_id', $current->id))
            ->pluck('id', 'batch_id');

        return view('batches.index', compact('batches', 'current', 'courseCounts', 'published'));
    }

    public function create(): View
    {
        return view('batches.form', [
            'batch' => new Batch([
                'department' => 'CSE',
                'status' => 'ACTIVE',
                'admission_year' => now()->year,
                'seniority_order' => Batch::max('seniority_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $extra = $request->validate([
            'section_count' => ['required', 'integer', 'min:1', 'max:10'],
            'student_count' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $batch = DB::transaction(function () use ($data, $extra) {
            $batch = Batch::create($data);

            // Sections are named A, B, C, ...
            for ($i = 0; $i < $extra['section_count']; $i++) {
                $batch->sections()->create([
                    'name' => chr(ord('A') + $i),
                    'student_count' => $extra['student_count'],
                    'status' => 'ACTIVE',
                ]);
            }

            return $batch;
        });

        AuditLogger::log('BATCH_CREATED', $batch, null, $batch->toArray());

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch {$batch->name} created. Now choose the courses it studies this term.");
    }

    public function show(Request $request, Batch $batch): View
    {
        $batch->load(['sections' => fn ($query) => $query->orderBy('name')]);

        $terms = AcademicTerm::orderByDesc('start_date')->get();
        $term = $terms->firstWhere('id', (int) $request->query('term'))
            ?? AcademicTerm::current()
            ?? $terms->first();

        $courses = Course::orderBy('course_type')->orderBy('course_code')->get();

        $offeredCourseIds = $term
            ? CourseOffering::where('academic_term_id', $term->id)
                ->whereIn('section_id', $batch->sections->pluck('id'))
                ->distinct()
                ->pluck('course_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $runs = DB::table('schedule_runs as sr')
            ->join('academic_terms as atm', 'atm.id', '=', 'sr.academic_term_id')
            ->where('sr.batch_id', $batch->id)
            ->orderByDesc('sr.created_at')
            ->get(['sr.id', 'sr.version', 'sr.status', 'atm.name as term_name']);

        $examSchedules = $batch->examSchedules()->with('academicTerm')->latest()->get();

        return view('batches.show', compact(
            'batch', 'terms', 'term', 'courses', 'offeredCourseIds', 'runs', 'examSchedules'
        ));
    }

    public function edit(Batch $batch): View
    {
        return view('batches.form', compact('batch'));
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        $old = $batch->toArray();
        $batch->update($this->validated($request, $batch));

        AuditLogger::log('BATCH_UPDATED', $batch, $old, $batch->toArray());

        return redirect()->route('batches.show', $batch)
            ->with('success', "Batch {$batch->name} updated.");
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        // Deleting cascades to sections, offerings and routines.
        if ($batch->scheduleRuns()->exists() || $batch->examSchedules()->exists()) {
            return back()->with('error',
                "{$batch->name} has saved routines or exam schedules. Mark it inactive instead."
            );
        }

        $batch->delete();

        AuditLogger::log('BATCH_DELETED', null, $batch->toArray());

        return redirect()->route('batches.index')
            ->with('success', "Batch {$batch->name} deleted.");
    }

    /**
     * Set the courses a batch studies in a term. Every section of
     * the batch gets one offering per selected course.
     */
    public function updateOfferings(Request $request, Batch $batch): RedirectResponse
    {
        $data = $request->validate([
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'course_ids' => ['array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $termId = (int) $data['academic_term_id'];
        $courseIds = array_map('intval', $data['course_ids'] ?? []);
        $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');
        $sectionIds = $batch->sections()->pluck('id');
        $kept = [];

        DB::transaction(function () use ($termId, $courseIds, $courses, $sectionIds, &$kept) {
            foreach ($sectionIds as $sectionId) {
                foreach ($courseIds as $courseId) {
                    CourseOffering::updateOrCreate(
                        [
                            'course_id' => $courseId,
                            'section_id' => $sectionId,
                            'academic_term_id' => $termId,
                        ],
                        [
                            'weekly_theory_classes' => $courses[$courseId]->weekly_theory_classes,
                            'weekly_lab_classes' => $courses[$courseId]->weekly_lab_classes,
                            'status' => 'ACTIVE',
                        ]
                    );
                }
            }

            // Remove deselected courses, but never one that a saved
            // routine still uses (that would delete routine entries).
            $removed = CourseOffering::with('course')
                ->where('academic_term_id', $termId)
                ->whereIn('section_id', $sectionIds)
                ->whereNotIn('course_id', $courseIds ?: [0])
                ->get();

            foreach ($removed as $offering) {
                if ($offering->scheduleEntries()->exists()) {
                    $kept[] = $offering->course->course_code;
                    continue;
                }

                $offering->delete();
            }
        });

        AuditLogger::log('BATCH_COURSES_UPDATED', $batch, null, [
            'academic_term_id' => $termId,
            'course_ids' => $courseIds,
        ]);

        $message = 'Course list saved.';

        if ($kept) {
            $message .= ' Kept ' . implode(', ', array_unique($kept))
                . ' because a saved routine uses it.';
        }

        return redirect()->route('batches.show', [$batch, 'term' => $termId])
            ->with('success', $message);
    }

    private function validated(Request $request, ?Batch $batch = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('batches', 'name')->ignore($batch?->id)],
            'department' => ['required', 'string', 'max:255'],
            'program' => ['required', 'string', 'max:255'],
            'admission_year' => ['required', 'integer', 'min:1990', 'max:2100'],
            'seniority_order' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['required', Rule::in(array_keys(config('intellisched.statuses')))],
        ]);
    }
}
