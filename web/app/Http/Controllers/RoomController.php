<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');

        $rooms = Room::with('eligibleCourses')
            ->when(array_key_exists((string) $type, config('intellisched.room_types')),
                fn ($query) => $query->where('room_type', $type))
            ->orderBy('priority')
            ->orderBy('room_code')
            ->get();

        $all = Room::all();

        return view('rooms.index', [
            'rooms' => $rooms,
            'type' => $type,
            'stats' => [
                'total' => $all->count(),
                'seats' => $all->sum(fn ($room) => (int) $room->capacity),
                'byType' => $all->countBy('room_type'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('rooms.form', [
            'room' => new Room(['room_type' => 'THEORY', 'status' => 'ACTIVE', 'priority' => 10]),
            'labCourses' => $this->labCourses(),
            'selectedCourses' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $courseIds] = $this->validated($request);

        $room = DB::transaction(function () use ($data, $courseIds) {
            $room = Room::create($data);
            $room->eligibleCourses()->sync($courseIds);

            return $room;
        });

        AuditLogger::log('ROOM_CREATED', $room, null, $room->toArray());

        return redirect()->route('rooms.show', $room)
            ->with('success', "Room {$room->room_code} created.");
    }

    public function show(Room $room): View
    {
        $room->load('eligibleCourses');

        // Weekly use of this room in published routines.
        $usage = DB::table('schedule_entries as se')
            ->join('schedule_runs as sr', 'sr.id', '=', 'se.schedule_run_id')
            ->join('batches as b', 'b.id', '=', 'sr.batch_id')
            ->join('course_offerings as co', 'co.id', '=', 'se.course_offering_id')
            ->join('courses as c', 'c.id', '=', 'co.course_id')
            ->join('sections as s', 's.id', '=', 'co.section_id')
            ->join('time_slots as ts', 'ts.id', '=', 'se.time_slot_id')
            ->where('se.room_id', $room->id)
            ->where('sr.status', 'PUBLISHED')
            ->select('ts.day', 'ts.slot_number', 'ts.start_time', 'ts.end_time',
                'c.course_code', 'b.name as batch_name', 's.name as section_name')
            ->get();

        $teachingSlots = DB::table('time_slots')->where('is_break', 0)->count();

        return view('rooms.show', compact('room', 'usage', 'teachingSlots'));
    }

    public function edit(Room $room): View
    {
        return view('rooms.form', [
            'room' => $room,
            'labCourses' => $this->labCourses(),
            'selectedCourses' => $room->eligibleCourses()->pluck('courses.id')->all(),
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        [$data, $courseIds] = $this->validated($request, $room);
        $old = $room->toArray();

        DB::transaction(function () use ($room, $data, $courseIds) {
            $room->update($data);
            $room->eligibleCourses()->sync($courseIds);
        });

        AuditLogger::log('ROOM_UPDATED', $room, $old, $room->toArray());

        return redirect()->route('rooms.show', $room)
            ->with('success', "Room {$room->room_code} updated.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        try {
            DB::transaction(function () use ($room) {
                $room->eligibleCourses()->detach();
                $room->delete();
            });
        } catch (QueryException $e) {
            return back()->with('error',
                "{$room->room_code} is used by saved routines or exams. Mark it inactive instead."
            );
        }

        AuditLogger::log('ROOM_DELETED', null, $room->toArray());

        return redirect()->route('rooms.index')
            ->with('success', "Room {$room->room_code} deleted.");
    }

    private function labCourses()
    {
        return Course::where('course_type', 'LAB')->orderBy('course_code')->get();
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'room_code' => ['required', 'string', 'max:50',
                Rule::unique('rooms', 'room_code')->ignore($room?->id)],
            'room_name' => ['required', 'string', 'max:255'],
            'room_type' => ['required', Rule::in(array_keys(config('intellisched.room_types')))],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'building' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys(config('intellisched.statuses')))],
            'eligible_courses' => ['array'],
            'eligible_courses.*' => ['integer', 'exists:courses,id'],
        ]);

        // Only lab-capable rooms carry lab course eligibility.
        $courseIds = $data['room_type'] === 'THEORY'
            ? []
            : array_map('intval', $data['eligible_courses'] ?? []);

        unset($data['eligible_courses']);

        return [$data, $courseIds];
    }
}
