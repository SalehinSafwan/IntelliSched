<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Batch;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Services\Scheduling\ScheduleRunService;
use App\Services\Scheduling\SchedulerApiService;
use App\Services\Scheduling\SchedulerInputService;
use App\Services\Scheduling\TimetableGridBuilder;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ScheduleGenerationController extends Controller
{
    // A generated routine waits this long for approval.
    private const PREVIEW_TTL_MINUTES = 120;

    public function __construct(
        private SchedulerInputService $inputService,
        private SchedulerApiService $schedulerApi,
        private ScheduleRunService $runService,
        private TimetableGridBuilder $gridBuilder
    ) {
    }

    /**
     * Select the academic term and batch to schedule.
     */
    public function create(): View
    {
        $terms = AcademicTerm::orderByDesc('start_date')->get();

        $batches = Batch::with('sections')
            ->orderBy('seniority_order')
            ->get();

        return view('schedules.create', compact('terms', 'batches'));
    }

    /**
     * Solve a timetable and keep it as an unsaved preview.
     * Nothing is written to Oracle until it is approved.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        // The solver may run for up to 30 seconds.
        set_time_limit(180);

        $batchId = (int) $validated['batch_id'];
        $termId = (int) $validated['academic_term_id'];

        try {
            // 1. Build the solver input from the database.
            $input = $this->inputService->build($batchId, $termId);

            // 2. Call C# POST /api/scheduler/solve. The API validates
            //    its own result before returning success.
            $result = $this->schedulerApi->generate($input);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', $this->errorMessage(
                    'Timetable generation failed.', $e
                ));
        }

        $token = (string) Str::uuid();

        $this->previewCache()->put($this->previewKey($token), [
            'user_id' => (int) $request->user()->id,
            'batch_id' => $batchId,
            'academic_term_id' => $termId,
            'generated_at' => now()->toDateTimeString(),
            'result' => $result,
        ], now()->addMinutes(self::PREVIEW_TTL_MINUTES));

        return redirect()->route('schedules.preview', $token);
    }

    /**
     * Show a generated routine awaiting approval.
     */
    public function preview(Request $request, string $token): View|RedirectResponse
    {
        $preview = $this->loadPreview($request, $token);

        if ($preview === null) {
            return $this->previewMissing();
        }

        $result = $preview['result'];
        $batch = Batch::with('sections')->findOrFail($preview['batch_id']);
        $term = AcademicTerm::findOrFail($preview['academic_term_id']);
        $teacherNames = $this->teacherNames();

        $entries = collect($result['schedule'])->map(fn ($session) => [
            'section_id' => (int) $session['section_id'],
            'time_slot_ids' => $session['time_slot_ids'],
            'course_code' => $session['course_code'],
            'course_name' => $session['course_name'],
            'session_type' => $session['session_type'],
            'teacher_mode' => $session['teacher_mode'],
            'teachers' => collect($session['teacher_ids'])
                ->map(fn ($id) => $teacherNames[(int) $id] ?? "Teacher {$id}")
                ->all(),
            'room_code' => $session['room_code'],
        ]);

        $grid = $this->gridBuilder->build(
            TimeSlot::all()->toArray(),
            $batch->sections->sortBy('name')->toArray(),
            $entries
        );

        return view('schedules.preview', [
            'token' => $token,
            'batch' => $batch,
            'term' => $term,
            'preview' => $preview,
            'result' => $result,
            'grid' => $grid,
            'teacherNames' => $teacherNames,
            'expiresInMinutes' => self::PREVIEW_TTL_MINUTES,
        ]);
    }

    /**
     * Approve the previewed routine: re-validate it against the
     * current data and save it to Oracle as PUBLISHED.
     */
    public function approve(Request $request, string $token): RedirectResponse
    {
        // Guard against a double click saving the routine twice.
        $lock = $this->previewCache()->lock('schedule-approve:' . $token, 60);

        if (!$lock->get()) {
            return redirect()
                ->route('schedules.preview', $token)
                ->with('error', 'This routine is already being saved.');
        }

        try {
            $preview = $this->loadPreview($request, $token);

            if ($preview === null) {
                return $this->previewMissing();
            }

            $batchId = $preview['batch_id'];
            $termId = $preview['academic_term_id'];
            $result = $preview['result'];

            // Data may have changed since the preview was generated
            // (e.g. another batch was published), so check again.
            $input = $this->inputService->build($batchId, $termId);

            $errors = $this->schedulerApi->validateSchedule(
                $input,
                $result['schedule']
            );

            if (count($errors) > 0) {
                return redirect()
                    ->route('schedules.preview', $token)
                    ->with('error',
                        'The routine is no longer valid for the current data. '
                        . 'Please generate it again. '
                        . implode(' ', array_slice($errors, 0, 5))
                    );
            }

            $runId = $this->runService->publish(
                $batchId,
                $termId,
                (int) $request->user()->id,
                $input,
                $result
            );

            $this->previewCache()->forget($this->previewKey($token));

            return redirect()
                ->route('schedules.show', $runId)
                ->with('success', 'Routine approved and saved as PUBLISHED.');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('schedules.preview', $token)
                ->with('error', $this->errorMessage(
                    'Saving the routine failed.', $e
                ));
        } finally {
            $lock->release();
        }
    }

    /**
     * Previews live in the file cache: the Oracle database cache
     * store upserts with MERGE, which cannot bind values longer
     * than 4000 bytes (ORA-01461), and a routine is larger.
     */
    private function previewCache(): Repository
    {
        return Cache::store('file');
    }

    private function previewKey(string $token): string
    {
        return 'schedule-preview:' . $token;
    }

    /**
     * Only the user who generated a preview may view or approve it.
     */
    private function loadPreview(Request $request, string $token): ?array
    {
        $preview = $this->previewCache()->get($this->previewKey($token));

        if (!is_array($preview)) {
            return null;
        }

        abort_if(
            $preview['user_id'] !== (int) $request->user()->id,
            403
        );

        return $preview;
    }

    private function previewMissing(): RedirectResponse
    {
        return redirect()
            ->route('schedules.create')
            ->with('error',
                'This routine preview has expired or was already saved. '
                . 'Please generate it again.'
            );
    }

    /**
     * teacher id => display name (users.name, else employee code).
     */
    private function teacherNames(): array
    {
        return Teacher::with('user')
            ->get()
            ->mapWithKeys(fn ($teacher) => [
                (int) $teacher->id =>
                    $teacher->user?->name ?? $teacher->employee_code,
            ])
            ->all();
    }

    private function errorMessage(string $summary, Throwable $e): string
    {
        return $summary . ' ' . (config('app.debug')
            ? $e->getMessage()
            : 'Check the Laravel application log for details.');
    }
}
