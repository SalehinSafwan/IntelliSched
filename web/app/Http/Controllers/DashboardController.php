<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $role = strtolower((string) $request->user()->role);
        $term = AcademicTerm::current();

        $data = [
            'role' => $role,
            'term' => $term,
            'publishedRuns' => $this->publishedRuns($term),
            'publishedExams' => ExamSchedule::with(['batch', 'courses.course'])
                ->where('status', 'PUBLISHED')
                ->when($term, fn ($q) => $q->where('academic_term_id', $term->id))
                ->get(),
        ];

        if (in_array($role, ['admin', 'coordinator'], true)) {
            $data += $this->managerData($term);
        }

        if ($role === 'teacher') {
            $data['teacher'] = Teacher::where('user_id', $request->user()->id)->first();
            $data['teacherPeriods'] = $data['teacher']
                ? DB::table('schedule_entries as se')
                    ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
                    ->where('sr.status', 'PUBLISHED')
                    ->where(fn ($q) => $q->where('se.teacher_id', $data['teacher']->id)
                        ->orWhere('se.co_teacher_id', $data['teacher']->id))
                    ->count()
                : 0;
        }

        return view('dashboard.index', $data);
    }

    private function publishedRuns(?AcademicTerm $term)
    {
        return DB::table('schedule_runs as sr')
            ->join('batches as b', 'b.id', '=', 'sr.batch_id')
            ->where('sr.status', 'PUBLISHED')
            ->when($term, fn ($q) => $q->where('sr.academic_term_id', $term->id))
            ->orderBy('b.seniority_order')
            ->get(['sr.id', 'sr.version', 'sr.batch_id', 'b.name as batch_name', 'sr.generated_at']);
    }

    private function managerData(?AcademicTerm $term): array
    {
        $courses = Course::where('status', 'ACTIVE')->get();
        $batches = Batch::with('sections')->where('status', 'ACTIVE')->orderBy('seniority_order')->get();
        $rooms = Room::where('status', 'ACTIVE')->get();

        // Readiness checks that would make the generator fail.
        $issues = [];

        $eligible = DB::table('teacher_expertise as te')
            ->join('teachers as t', 't.id', '=', 'te.teacher_id')
            ->where('t.status', 'ACTIVE')
            ->select('te.course_id', DB::raw('COUNT(*) as total'))
            ->groupBy('te.course_id')
            ->pluck('total', 'course_id');

        foreach ($courses as $course) {
            if ((int) ($eligible[$course->id] ?? 0) < 2) {
                $issues[] = [
                    'text' => "{$course->course_code} has fewer than 2 eligible teachers (needs a teacher pair).",
                    'url' => route('teachers.preferences'),
                ];
            }
        }

        $labRooms = DB::table('room_course_eligibility as rce')
            ->join('rooms as r', 'r.id', '=', 'rce.room_id')
            ->where('r.status', 'ACTIVE')
            ->pluck('rce.course_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        foreach ($courses->where('course_type', 'LAB') as $course) {
            if (!$labRooms->contains((int) $course->id)) {
                $issues[] = [
                    'text' => "Lab course {$course->course_code} has no eligible lab room.",
                    'url' => route('rooms.index'),
                ];
            }
        }

        if ($term) {
            $batchesWithCourses = CourseOffering::join('sections', 'sections.id', '=', 'course_offerings.section_id')
                ->where('course_offerings.academic_term_id', $term->id)
                ->distinct()
                ->pluck('sections.batch_id')
                ->map(fn ($id) => (int) $id);

            foreach ($batches as $batch) {
                if (!$batchesWithCourses->contains((int) $batch->id)) {
                    $issues[] = [
                        'text' => "{$batch->name} has no course list for {$term->name}.",
                        'url' => route('batches.show', $batch),
                    ];
                }
            }
        } else {
            $issues[] = ['text' => 'No academic term is marked as current.', 'url' => route('terms.index')];
        }

        return [
            'stats' => [
                'teachers' => Teacher::where('status', 'ACTIVE')->count(),
                'courses' => $courses->count(),
                'theory' => $courses->where('course_type', 'THEORY')->count(),
                'lab' => $courses->where('course_type', 'LAB')->count(),
                'rooms' => $rooms->count(),
                'seats' => $rooms->sum(fn ($r) => (int) $r->capacity),
                'batches' => $batches->count(),
                'sections' => $batches->sum(fn ($b) => $b->sections->count()),
                'students' => $batches->sum(fn ($b) => $b->sections->sum(fn ($s) => (int) $s->student_count)),
            ],
            'batches' => $batches,
            'issues' => $issues,
            'activity' => AuditLog::with('user')->orderByDesc('id')->limit(6)->get(),
            'draftExams' => ExamSchedule::where('status', 'DRAFT')->count(),
        ];
    }
}
