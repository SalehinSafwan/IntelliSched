<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Services\Scheduling\RoutineViewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleViewController extends Controller
{
    public function __construct(
        private RoutineViewService $routines
    ) {
    }

    /**
     * List schedule runs, optionally filtered by batch, term and status.
     * Teachers and students only see published routines.
     *
     * GET /schedules?batch_id=1&academic_term_id=1&status=PUBLISHED
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'batch_id' => ['sometimes', 'integer', 'min:1'],
            'academic_term_id' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:DRAFT,PUBLISHED,ARCHIVED'],
        ]);

        $canManage = $this->canManage($request);

        $query = $this->routines->runQuery()
            ->selectRaw(
                '(SELECT COUNT(*) FROM schedule_entries se
                  WHERE se.schedule_run_id = sr.id) AS period_entry_count'
            );

        if (!$canManage) {
            $query->where('sr.status', 'PUBLISHED');
        }

        if (isset($validated['batch_id'])) {
            $query->where('sr.batch_id', $validated['batch_id']);
        }

        if (isset($validated['academic_term_id'])) {
            $query->where(
                'sr.academic_term_id',
                $validated['academic_term_id']
            );
        }

        if (isset($validated['status'])) {
            $query->where('sr.status', $validated['status']);
        }

        $runs = $query
            ->orderByDesc('sr.created_at')
            ->orderByDesc('sr.version')
            ->get();

        return view('schedules.index', compact('runs', 'canManage'));
    }

    /**
     * Show one saved schedule run as a weekly timetable.
     *
     * GET /schedules/{scheduleRunId}
     */
    public function show(Request $request, int $scheduleRunId): View
    {
        $data = $this->routines->forRun($scheduleRunId);

        abort_if(!$data, 404, 'Schedule run not found.');
        abort_if($data['run']->status !== 'PUBLISHED' && !$this->canManage($request), 404);

        return view('schedules.show', $data + ['canManage' => $this->canManage($request)]);
    }

    private function canManage(Request $request): bool
    {
        return in_array(strtolower((string) $request->user()?->role), ['admin', 'coordinator'], true);
    }
}
