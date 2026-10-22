<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherExpertise;
use App\Models\TeacherPreference;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Scheduling\TimetableGridBuilder;
use App\Services\Teacher\TeacherCourseScoringService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    // Availability grid states and how they are stored.
    private const STATES = [
        'preferred' => ['is_available' => 1, 'preference' => 1],
        'available' => ['is_available' => 1, 'preference' => 0],
        'avoid' => ['is_available' => 1, 'preference' => -1],
        'unavailable' => ['is_available' => 0, 'preference' => 0],
    ];

    public function index(): View
    {
        $teachers = Teacher::with(['user', 'expertise.course'])
            ->orderBy('employee_code')
            ->get();

        // Published weekly periods per teacher (as main or co-teacher).
        $loads = DB::table('schedule_entries as se')
            ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
            ->where('sr.status', 'PUBLISHED')
            ->get(['se.teacher_id', 'se.co_teacher_id'])
            ->flatMap(fn ($row) => array_filter([$row->teacher_id, $row->co_teacher_id]))
            ->countBy();

        return view('teachers.index', compact('teachers', 'loads'));
    }

    public function create(): View
    {
        return view('teachers.form', [
            'teacher' => new Teacher(['department' => 'CSE', 'status' => 'ACTIVE', 'max_weekly_classes' => 12]),
            'user' => new User(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $teacher = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'teacher',
                'is_active' => $data['status'] === 'ACTIVE' ? 1 : 0,
            ]);

            return Teacher::create([
                'user_id' => $user->id,
                'employee_code' => $data['employee_code'],
                'department' => $data['department'],
                'max_weekly_classes' => $data['max_weekly_classes'],
                'status' => $data['status'],
            ]);
        });

        AuditLogger::log('TEACHER_CREATED', $teacher, null, $teacher->toArray());

        return redirect()->route('teachers.show', $teacher)
            ->with('success', "Teacher {$data['name']} created. Now set the courses they can teach.");
    }

    public function show(Teacher $teacher, TeacherCourseScoringService $scoring, TimetableGridBuilder $gridBuilder): View
    {
        $teacher->load(['user', 'expertise', 'preferences', 'teachingHistory', 'availability']);

        $courses = Course::orderBy('course_type')->orderBy('course_code')->get();

        $suitability = $courses->map(function ($course) use ($teacher, $scoring) {
            $expertise = $teacher->expertise->firstWhere('course_id', $course->id);

            return [
                'course' => $course,
                'eligible' => $expertise !== null,
                'expertise' => $expertise?->expertise_score,
                'preference' => $teacher->preferences->firstWhere('course_id', $course->id)?->preference_score,
                'history' => (int) $teacher->teachingHistory->where('course_id', $course->id)->sum('times_taught'),
                'score' => $expertise ? $scoring->calculate($teacher, $course) : null,
            ];
        });

        $availability = $this->availabilitySummary($teacher);
        $schedule = $this->scheduleGrid($teacher, $gridBuilder);

        return view('teachers.show', compact('teacher', 'suitability', 'availability', 'schedule'));
    }

    public function edit(Teacher $teacher): View
    {
        return view('teachers.form', ['teacher' => $teacher, 'user' => $teacher->user ?? new User()]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $this->validated($request, $teacher);
        $old = $teacher->toArray();

        DB::transaction(function () use ($teacher, $data) {
            $user = $teacher->user;
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['status'] === 'ACTIVE' ? 1 : 0,
            ]);

            if (!empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            $teacher->update([
                'employee_code' => $data['employee_code'],
                'department' => $data['department'],
                'max_weekly_classes' => $data['max_weekly_classes'],
                'status' => $data['status'],
            ]);
        });

        AuditLogger::log('TEACHER_UPDATED', $teacher, $old, $teacher->toArray());

        return redirect()->route('teachers.show', $teacher)
            ->with('success', "Teacher {$data['name']} updated.");
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $name = $teacher->display_name;

        try {
            DB::transaction(function () use ($teacher) {
                $user = $teacher->user;
                $teacher->delete();
                $user?->delete();
            });
        } catch (QueryException $e) {
            return back()->with('error', "{$name} appears in saved routines. Mark the teacher inactive instead.");
        }

        AuditLogger::log('TEACHER_DELETED', null, $teacher->toArray());

        return redirect()->route('teachers.index')->with('success', "Teacher {$name} deleted.");
    }

    /**
     * Save which courses a teacher can teach, with expertise and
     * preference scores (0-100). A teacher without an expertise row
     * for a course is never assigned to it.
     */
    public function updateSuitability(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'courses' => ['array'],
            'courses.*.eligible' => ['nullable', 'boolean'],
            'courses.*.expertise' => ['nullable', 'integer', 'min:0', 'max:100'],
            'courses.*.preference' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($teacher, $data) {
            foreach ($data['courses'] ?? [] as $courseId => $row) {
                $key = ['teacher_id' => $teacher->id, 'course_id' => (int) $courseId];

                if (empty($row['eligible'])) {
                    TeacherExpertise::where($key)->delete();
                    TeacherPreference::where($key)->delete();
                    continue;
                }

                TeacherExpertise::updateOrCreate($key, ['expertise_score' => (int) ($row['expertise'] ?? 50)]);
                TeacherPreference::updateOrCreate($key, ['preference_score' => (int) ($row['preference'] ?? 50)]);
            }
        });

        AuditLogger::log('TEACHER_SUITABILITY_UPDATED', $teacher, null, $data);

        return redirect()->route('teachers.show', $teacher)->with('success', 'Course suitability saved.');
    }

    /**
     * Teacher × course suitability heatmap.
     */
    public function preferences(TeacherCourseScoringService $scoring): View
    {
        $teachers = Teacher::with(['user', 'expertise', 'preferences', 'teachingHistory'])
            ->orderBy('employee_code')
            ->get();

        $courses = Course::where('status', 'ACTIVE')->orderBy('course_code')->get();

        $matrix = [];

        foreach ($teachers as $teacher) {
            foreach ($courses as $course) {
                $matrix[$teacher->id][$course->id] = $teacher->expertise->contains('course_id', $course->id)
                    ? $scoring->calculate($teacher, $course)
                    : null;
            }
        }

        return view('teachers.preferences', compact('teachers', 'courses', 'matrix'));
    }

    /**
     * Availability + time-of-day preference grid of one teacher.
     */
    public function availability(Teacher $teacher): View
    {
        return $this->availabilityView($teacher, false);
    }

    public function updateAvailability(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->saveAvailability($request, $teacher);

        return redirect()->route('teachers.availability', $teacher)
            ->with('success', 'Availability and time preferences saved.');
    }

    /**
     * The signed-in teacher edits their own grid.
     */
    public function myAvailability(Request $request): View
    {
        return $this->availabilityView($this->ownTeacher($request), true);
    }

    public function updateMyAvailability(Request $request): RedirectResponse
    {
        $this->saveAvailability($request, $this->ownTeacher($request));

        return redirect()->route('my.availability')
            ->with('success', 'Your availability and time preferences were saved.');
    }

    public function mySchedule(Request $request, TimetableGridBuilder $gridBuilder): View
    {
        $teacher = $this->ownTeacher($request);

        return view('teachers.my-schedule', [
            'teacher' => $teacher,
            'schedule' => $this->scheduleGrid($teacher, $gridBuilder),
        ]);
    }

    private function ownTeacher(Request $request): Teacher
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();

        abort_unless($teacher, 404, 'Your account is not linked to a teacher profile.');

        return $teacher;
    }

    private function availabilityView(Teacher $teacher, bool $self): View
    {
        $teacher->load('user');

        $slots = TimeSlot::all();
        $dayOrder = array_flip(config('intellisched.days'));

        $days = $slots->pluck('day')->map(fn ($d) => strtoupper($d))->unique()
            ->sortBy(fn ($d) => $dayOrder[$d] ?? 99)->values();

        $rows = $slots->groupBy(fn ($s) => (int) $s->slot_number)->sortKeys();

        $records = TeacherAvailability::where('teacher_id', $teacher->id)->get()->keyBy('time_slot_id');

        // Slots without a record are treated as available (neutral).
        $states = [];

        foreach ($slots as $slot) {
            $record = $records->get($slot->id);
            $states[$slot->id] = !$record ? 'available' : match (true) {
                !$record->is_available => 'unavailable',
                (int) $record->preference > 0 => 'preferred',
                (int) $record->preference < 0 => 'avoid',
                default => 'available',
            };
        }

        return view('teachers.availability', compact('teacher', 'days', 'rows', 'states', 'self'));
    }

    private function saveAvailability(Request $request, Teacher $teacher): void
    {
        $data = $request->validate([
            'cells' => ['array'],
            'cells.*' => [Rule::in(array_keys(self::STATES))],
        ]);

        $teachingSlotIds = TimeSlot::where('is_break', 0)->pluck('id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($teacher, $data, $teachingSlotIds) {
            foreach ($data['cells'] ?? [] as $slotId => $state) {
                if (!in_array((int) $slotId, $teachingSlotIds, true)) {
                    continue;
                }

                TeacherAvailability::updateOrCreate(
                    ['teacher_id' => $teacher->id, 'time_slot_id' => (int) $slotId],
                    self::STATES[$state] + ['reason' => null]
                );
            }
        });

        AuditLogger::log('TEACHER_AVAILABILITY_UPDATED', $teacher);
    }

    private function availabilitySummary(Teacher $teacher): array
    {
        $teaching = TimeSlot::where('is_break', 0)->count();
        $records = $teacher->availability;

        $unavailable = $records->where('is_available', 0)->count();

        return [
            'teaching' => $teaching,
            'available' => $teaching - $unavailable,
            'preferred' => $records->where('is_available', 1)->where('preference', '>', 0)->count(),
            'avoid' => $records->where('is_available', 1)->where('preference', '<', 0)->count(),
        ];
    }

    /**
     * The teacher's week from published routines, as one grid.
     */
    private function scheduleGrid(Teacher $teacher, TimetableGridBuilder $gridBuilder): array
    {
        $rows = DB::table('schedule_entries as se')
            ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
            ->join('batches as b', 'b.id', '=', 'sr.batch_id')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->join('rooms as r', 'r.id', '=', 'se.room_id')
            ->where('sr.status', 'PUBLISHED')
            ->where(fn ($query) => $query
                ->where('se.teacher_id', $teacher->id)
                ->orWhere('se.co_teacher_id', $teacher->id))
            ->orderBy('se.id')
            ->get([
                'se.id as entry_id', 'se.session_group_id', 'se.session_type', 'se.time_slot_id', 'se.co_teacher_id',
                'c.course_code', 'c.course_name', 'b.name as batch_name', 's.name as section_name', 'r.room_code',
            ]);

        $entries = $rows
            ->groupBy(fn ($row) => $row->session_group_id ? 'g:' . $row->session_group_id : 'e:' . $row->entry_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'section_id' => 0,
                    'time_slot_ids' => $group->pluck('time_slot_id')->map(fn ($id) => (int) $id)->all(),
                    'course_code' => $first->course_code,
                    'course_name' => $first->course_name,
                    'session_type' => $first->session_type,
                    'teacher_mode' => $first->co_teacher_id ? 'Joint' : 'Single',
                    'teachers' => [$first->batch_name . ' · Section ' . $first->section_name],
                    'room_code' => $first->room_code,
                ];
            })
            ->values();

        return [
            'periods' => $rows->count(),
            'grid' => $gridBuilder->build(
                DB::table('time_slots')->get(),
                [['id' => 0, 'name' => $teacher->display_name]],
                $entries
            ),
        ];
    }

    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($teacher?->user_id)],
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8'],
            'employee_code' => ['required', 'string', 'max:50',
                Rule::unique('teachers', 'employee_code')->ignore($teacher?->id)],
            'department' => ['required', 'string', 'max:255'],
            'max_weekly_classes' => ['required', 'integer', 'min:1', 'max:40'],
            'status' => ['required', Rule::in(array_keys(config('intellisched.statuses')))],
        ]);
    }
}
