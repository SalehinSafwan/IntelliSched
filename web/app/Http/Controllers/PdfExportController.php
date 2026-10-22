<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Services\Scheduling\RoutineViewService;
use App\Services\Teacher\TeacherCourseScoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Instant PDF descriptions of the managed records.
 */
class PdfExportController extends Controller
{
    public function rooms(): Response
    {
        $rooms = Room::with('eligibleCourses')->orderBy('room_code')->get();

        return $this->download('pdf.rooms', compact('rooms'), 'Rooms & Labs', 'rooms');
    }

    public function teachers(TeacherCourseScoringService $scoring): Response
    {
        $teachers = Teacher::with(['user', 'expertise.course', 'preferences', 'availability'])
            ->orderBy('employee_code')
            ->get();

        $teachingSlots = TimeSlot::where('is_break', 0)->count();

        $profiles = $teachers->map(fn ($teacher) => [
            'teacher' => $teacher,
            'courses' => $teacher->expertise
                ->filter(fn ($e) => $e->course)
                ->sortBy(fn ($e) => $e->course->course_code)
                ->map(fn ($e) => [
                    'code' => $e->course->course_code,
                    'name' => $e->course->course_name,
                    'expertise' => $e->expertise_score,
                    'preference' => $teacher->preferences->firstWhere('course_id', $e->course_id)?->preference_score,
                    'score' => $scoring->calculate($teacher, $e->course),
                ]),
            'unavailable' => $teacher->availability->where('is_available', 0)->count(),
            'preferred' => $teacher->availability->where('is_available', 1)->where('preference', '>', 0)->count(),
            'avoid' => $teacher->availability->where('is_available', 1)->where('preference', '<', 0)->count(),
        ]);

        return $this->download('pdf.teachers', compact('profiles', 'teachingSlots'), 'Teachers', 'teachers');
    }

    public function batches(): Response
    {
        $term = AcademicTerm::current();

        $batches = Batch::with('sections')->orderBy('seniority_order')->get();

        $coursesByBatch = $term
            ? CourseOffering::with('course')
                ->join('sections', 'sections.id', '=', 'course_offerings.section_id')
                ->where('course_offerings.academic_term_id', $term->id)
                ->get(['course_offerings.*', 'sections.batch_id as batch_id_ref'])
                ->groupBy('batch_id_ref')
                ->map(fn ($offerings) => $offerings->pluck('course')->filter()->unique('id')->sortBy('course_code'))
            : collect();

        return $this->download('pdf.batches', compact('batches', 'term', 'coursesByBatch'), 'Batches & Sections', 'batches');
    }

    public function courses(): Response
    {
        $courses = Course::with(['expertise.teacher.user', 'roomEligibility'])
            ->orderBy('course_code')
            ->get();

        $roomCodes = Room::pluck('room_code', 'id');

        return $this->download('pdf.courses', compact('courses', 'roomCodes'), 'Course Catalogue', 'courses');
    }

    public function routine(Request $request, int $scheduleRunId, RoutineViewService $routines): Response
    {
        $data = $routines->forRun($scheduleRunId);

        abort_if(!$data, 404);
        abort_if($data['run']->status !== 'PUBLISHED'
            && !in_array(strtolower((string) $request->user()?->role), ['admin', 'coordinator'], true), 404);

        $title = "Class Routine — {$data['run']->batch_name}";

        return $this->download('pdf.routine', $data, $title,
            'routine-' . str($data['run']->batch_name)->slug() . '-v' . $data['run']->version, 'landscape');
    }

    public function exam(Request $request, ExamSchedule $exam): Response
    {
        abort_if(!$exam->isPublished()
            && !in_array(strtolower((string) $request->user()?->role), ['admin', 'coordinator'], true), 404);

        $exam->load(['batch', 'academicTerm', 'rooms', 'courses.course']);

        $type = config('intellisched.exam_types')[$exam->exam_type] ?? $exam->exam_type;

        return $this->download('pdf.exam', compact('exam'), "{$type} Examination — {$exam->batch->name}",
            'exam-' . str($exam->batch->name . '-' . $exam->exam_type)->slug());
    }

    private function download(string $view, array $data, string $title, string $filename, string $orientation = 'portrait'): Response
    {
        return Pdf::loadView($view, $data + [
            'title' => $title,
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
        ])
            // Embed only the glyphs used, keeping files small.
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', $orientation)
            ->download($filename . '.pdf');
    }
}
