<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\TeacherSupervision;
use App\Services\AuditLogger;
use App\Services\Messaging\ClassMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The signed-in teacher's supervised batch sections.
 */
class SupervisionController extends Controller
{
    public function index(Request $request, ClassMessageService $messages): View
    {
        $teacher = $this->ownTeacher($request);

        $supervisions = $teacher->supervisions()
            ->with('section.batch')
            ->get()
            ->sortBy(fn ($supervision) => [
                (int) ($supervision->section?->batch?->seniority_order ?? 99),
                $supervision->section?->name,
            ])
            ->values();

        $reps = $supervisions->mapWithKeys(fn ($supervision) => [
            $supervision->section_id => $messages->recipients((int) $supervision->section_id),
        ]);

        $supervised = $supervisions->pluck('section_id')->map(fn ($id) => (int) $id)->all();

        // Sections still available to add, grouped by batch.
        $available = Section::with('batch')
            ->where('status', 'ACTIVE')
            ->whereNotIn('id', $supervised ?: [0])
            ->get()
            ->sortBy(fn ($section) => [(int) ($section->batch?->seniority_order ?? 99), $section->name])
            ->groupBy(fn ($section) => $section->batch?->name ?? 'Other');

        return view('teachers.supervision', compact('teacher', 'supervisions', 'reps', 'available'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);

        $data = $request->validate([
            'section_id' => [
                'required', 'integer',
                Rule::exists('sections', 'id'),
                Rule::unique('teacher_supervisions', 'section_id')->where('teacher_id', $teacher->id),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'section_id.unique' => 'You already supervise this section.',
        ]);

        $supervision = $teacher->supervisions()->create($data);
        $supervision->load('section.batch');

        AuditLogger::log('SUPERVISION_ADDED', $supervision, null, $supervision->only(['teacher_id', 'section_id', 'note']));

        return back()->with('success', "Added {$supervision->section->label} to your supervised sections.");
    }

    public function destroy(Request $request, TeacherSupervision $supervision): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);

        abort_unless((int) $supervision->teacher_id === (int) $teacher->id, 403, 'This is not your supervision.');

        $label = $supervision->section?->label ?? 'the section';
        $old = $supervision->only(['teacher_id', 'section_id', 'note']);
        $supervision->delete();

        AuditLogger::log('SUPERVISION_REMOVED', null, $old);

        return back()->with('success', "Removed {$label} from your supervised sections.");
    }

    private function ownTeacher(Request $request): Teacher
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        abort_unless($teacher, 404, 'Your account is not linked to a teacher profile.');

        return $teacher;
    }
}
