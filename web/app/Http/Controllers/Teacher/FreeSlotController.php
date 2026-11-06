<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Teacher\FreeSlotFinder;
use App\Services\Teacher\TeacherSectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Find periods when the teacher and their students are all free.
 */
class FreeSlotController extends Controller
{
    public function index(Request $request, TeacherSectionService $sectionService, FreeSlotFinder $finder): View
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        abort_unless($teacher, 404, 'Your account is not linked to a teacher profile.');

        $sections = $sectionService->reachableSections($teacher);
        $allowed = $sections->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Only sections the teacher supervises or teaches can be chosen.
        $selected = collect((array) $request->query('sections', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $allowed, true))
            ->unique()
            ->values()
            ->all();

        if (!$selected && $sections->isNotEmpty()) {
            $selected = [(int) $sections->first()->id];
        }

        $result = $selected ? $finder->build($teacher, $selected) : null;

        return view('teachers.free-slots', compact('teacher', 'sections', 'selected', 'result'));
    }
}
