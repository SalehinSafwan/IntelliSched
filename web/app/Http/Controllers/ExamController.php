<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\CourseOffering;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Services\AuditLogger;
use App\Services\Exams\ExamScheduleValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = $this->canManage($request);

        $schedules = ExamSchedule::with(['batch', 'academicTerm', 'rooms', 'courses'])
            ->when(!$canManage, fn ($query) => $query->where('status', 'PUBLISHED'))
            ->orderByDesc('created_at')
            ->get();

        return view('exams.index', compact('schedules', 'canManage'));
    }

    public function create(): View
    {
        return view('exams.create', [
            'terms' => AcademicTerm::orderByDesc('start_date')->get(),
            'current' => AcademicTerm::current(),
            'batches' => Batch::where('status', 'ACTIVE')->orderBy('seniority_order')->get(),
        ]);
    }

    /**
     * Start an exam schedule for a batch; its courses come from the
     * batch's course list in that term.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'exam_type' => ['required', Rule::in(array_keys(config('intellisched.exam_types')))],
        ]);

        $existing = ExamSchedule::where($data)->first();

        if ($existing) {
            return redirect()->route('exams.edit', $existing)
                ->with('success', 'This exam schedule already exists — continue editing it.');
        }

        if ($this->offeredCourseIds($data['batch_id'], $data['academic_term_id'])->isEmpty()) {
            return back()->withInput()->with('error',
                'This batch has no courses in that term. Set its course list on the batch page first.'
            );
        }

        $session = config('intellisched.exam_sessions.MORNING');

        $schedule = ExamSchedule::create($data + [
            'session' => 'MORNING',
            'start_time' => $session['start'],
            'end_time' => $session['end'],
            'status' => 'DRAFT',
            'created_by' => $request->user()->id,
        ]);

        $this->syncCourses($schedule);

        AuditLogger::log('EXAM_SCHEDULE_CREATED', $schedule, null, $schedule->toArray());

        return redirect()->route('exams.edit', $schedule)
            ->with('success', 'Exam schedule created. Pick the time, rooms and a date for each course.');
    }

    public function show(Request $request, ExamSchedule $exam): View
    {
        abort_if(!$exam->isPublished() && !$this->canManage($request), 404);

        $exam->load(['batch.sections', 'academicTerm', 'rooms', 'courses.course', 'createdBy']);

        return view('exams.show', [
            'exam' => $exam,
            'canManage' => $this->canManage($request),
        ]);
    }

    public function edit(ExamSchedule $exam): View|RedirectResponse
    {
        if ($exam->isPublished()) {
            return redirect()->route('exams.show', $exam)
                ->with('error', 'Unpublish the exam schedule before editing it.');
        }

        // Pick up changes to the batch's course list.
        $this->syncCourses($exam);

        $exam->load(['batch.sections', 'academicTerm', 'rooms', 'courses.course']);

        $rooms = Room::where('status', 'ACTIVE')->orderBy('room_type')->orderBy('room_code')->get();
        $students = $exam->batch->sections->where('status', 'ACTIVE')->sum(fn ($s) => (int) $s->student_count);

        return view('exams.edit', compact('exam', 'rooms', 'students'));
    }

    public function update(Request $request, ExamSchedule $exam): RedirectResponse
    {
        abort_if($exam->isPublished(), 403, 'Unpublish the exam schedule before editing it.');

        $data = $request->validate([
            'session' => ['required', Rule::in(array_keys(config('intellisched.exam_sessions')))],
            'room_ids' => ['array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'dates' => ['array'],
            'dates.*' => ['nullable', 'date'],
        ]);

        $session = config('intellisched.exam_sessions')[$data['session']];

        DB::transaction(function () use ($exam, $data, $session) {
            $exam->update([
                'session' => $data['session'],
                'start_time' => $session['start'],
                'end_time' => $session['end'],
            ]);

            $exam->rooms()->sync(array_map('intval', $data['room_ids'] ?? []));

            foreach ($exam->courses as $examCourse) {
                $examCourse->update(['exam_date' => $data['dates'][$examCourse->id] ?? null]);
            }
        });

        AuditLogger::log('EXAM_SCHEDULE_UPDATED', $exam, null, $data);

        if ($request->input('action') === 'publish') {
            return $this->publish($exam->fresh(), app(ExamScheduleValidator::class));
        }

        return redirect()->route('exams.edit', $exam)->with('success', 'Draft saved.');
    }

    public function publish(ExamSchedule $exam, ExamScheduleValidator $validator): RedirectResponse
    {
        $errors = $validator->validate($exam);

        if ($errors) {
            return redirect()->route('exams.edit', $exam)
                ->with('error', 'The exam schedule cannot be published yet.')
                ->with('exam_conflicts', $errors);
        }

        $exam->update(['status' => 'PUBLISHED', 'published_at' => now()]);

        AuditLogger::log('EXAM_SCHEDULE_PUBLISHED', $exam);

        return redirect()->route('exams.show', $exam)
            ->with('success', 'Exam schedule published. Its rooms are now reserved during the exam window.');
    }

    public function unpublish(ExamSchedule $exam): RedirectResponse
    {
        $exam->update(['status' => 'DRAFT', 'published_at' => null]);

        AuditLogger::log('EXAM_SCHEDULE_UNPUBLISHED', $exam);

        return redirect()->route('exams.edit', $exam)->with('success', 'Exam schedule moved back to draft.');
    }

    public function destroy(ExamSchedule $exam): RedirectResponse
    {
        $exam->delete();

        AuditLogger::log('EXAM_SCHEDULE_DELETED', null, $exam->toArray());

        return redirect()->route('exams.index')->with('success', 'Exam schedule deleted.');
    }

    private function canManage(Request $request): bool
    {
        return in_array(strtolower((string) $request->user()?->role), ['admin', 'coordinator'], true);
    }

    private function offeredCourseIds(int $batchId, int $termId)
    {
        return CourseOffering::join('sections', 'sections.id', '=', 'course_offerings.section_id')
            ->where('sections.batch_id', $batchId)
            ->where('course_offerings.academic_term_id', $termId)
            ->distinct()
            ->pluck('course_offerings.course_id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * One exam per course the batch studies in the term.
     */
    private function syncCourses(ExamSchedule $exam): void
    {
        $courseIds = $this->offeredCourseIds((int) $exam->batch_id, (int) $exam->academic_term_id);

        foreach ($courseIds as $courseId) {
            $exam->courses()->firstOrCreate(['course_id' => $courseId]);
        }

        $exam->courses()->whereNotIn('course_id', $courseIds->all() ?: [0])->delete();
    }
}
