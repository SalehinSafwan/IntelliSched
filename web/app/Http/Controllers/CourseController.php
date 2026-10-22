<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');

        $courses = Course::withCount('offerings')
            ->when(in_array($type, ['THEORY', 'LAB'], true),
                fn ($query) => $query->where('course_type', $type))
            ->orderBy('course_code')
            ->get();

        return view('courses.index', [
            'courses' => $courses,
            'type' => $type,
            'counts' => [
                'all' => Course::count(),
                'THEORY' => Course::where('course_type', 'THEORY')->count(),
                'LAB' => Course::where('course_type', 'LAB')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('courses.form', [
            'course' => new Course([
                'course_type' => 'THEORY',
                'status' => 'ACTIVE',
                'weekly_lab_classes' => 1,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $course = Course::create($this->validated($request));

        AuditLogger::log('COURSE_CREATED', $course, null, $course->toArray());

        return redirect()->route('courses.show', $course)
            ->with('success', "Course {$course->course_code} created.");
    }

    public function show(Course $course): View
    {
        $course->load([
            'expertise.teacher.user',
            'roomEligibility',
        ]);

        $offerings = CourseOffering::with(['section.batch', 'academicTerm'])
            ->where('course_id', $course->id)
            ->orderByDesc('academic_term_id')
            ->get();

        $eligibleRooms = \App\Models\Room::whereIn(
            'id', $course->roomEligibility->pluck('room_id')
        )->orderBy('room_code')->get();

        return view('courses.show', compact('course', 'offerings', 'eligibleRooms'));
    }

    public function edit(Course $course): View
    {
        return view('courses.form', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $old = $course->toArray();
        $course->update($this->validated($request, $course));

        // Keep the weekly class counts of the current term's
        // offerings in step with the course definition.
        $current = AcademicTerm::current();

        if ($current) {
            CourseOffering::where('course_id', $course->id)
                ->where('academic_term_id', $current->id)
                ->update([
                    'weekly_theory_classes' => $course->weekly_theory_classes,
                    'weekly_lab_classes' => $course->weekly_lab_classes,
                ]);
        }

        AuditLogger::log('COURSE_UPDATED', $course, $old, $course->toArray());

        return redirect()->route('courses.show', $course)
            ->with('success', "Course {$course->course_code} updated.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->offerings()->exists()) {
            return back()->with('error',
                "{$course->course_code} is offered to batches. Remove it from their course lists first, or mark it inactive."
            );
        }

        try {
            $course->delete();
        } catch (QueryException $e) {
            return back()->with('error', "{$course->course_code} is still in use and cannot be deleted.");
        }

        AuditLogger::log('COURSE_DELETED', null, $course->toArray());

        return redirect()->route('courses.index')
            ->with('success', "Course {$course->course_code} deleted.");
    }

    /**
     * Theory courses always meet 3 times a week; lab courses meet
     * in 3-period lab sessions, their count chosen per course.
     */
    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:50',
                Rule::unique('courses', 'course_code')->ignore($course?->id)],
            'course_name' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'course_type' => ['required', Rule::in(array_keys(config('intellisched.course_types')))],
            'credit_hours' => ['required', 'numeric', 'min:0', 'max:12'],
            'weekly_lab_classes' => ['nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['required', Rule::in(array_keys(config('intellisched.statuses')))],
        ]);

        $isTheory = $data['course_type'] === 'THEORY';

        $data['weekly_theory_classes'] = $isTheory
            ? config('intellisched.weekly_theory_classes')
            : 0;

        $data['weekly_lab_classes'] = $isTheory
            ? 0
            : (int) ($data['weekly_lab_classes'] ?? 1);

        return $data;
    }
}
