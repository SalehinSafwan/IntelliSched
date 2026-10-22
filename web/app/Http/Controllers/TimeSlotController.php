<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimeSlotController extends Controller
{
    public function index(): View
    {
        $slots = TimeSlot::all();
        $dayOrder = array_flip(config('intellisched.days'));

        $days = $slots->pluck('day')
            ->map(fn ($day) => strtoupper($day))
            ->unique()
            ->sortBy(fn ($day) => $dayOrder[$day] ?? 99)
            ->values();

        $slotNumbers = $slots->pluck('slot_number')
            ->map(fn ($n) => (int) $n)
            ->unique()
            ->sort()
            ->values();

        // grid[slot_number][day] = slot
        $grid = [];

        foreach ($slots as $slot) {
            $grid[(int) $slot->slot_number][strtoupper($slot->day)] = $slot;
        }

        $unusedDays = collect(config('intellisched.days'))->diff($days)->values();

        return view('timeslots.index', compact('days', 'slotNumbers', 'grid', 'unusedDays', 'slots'));
    }

    public function store(Request $request): RedirectResponse
    {
        $slot = TimeSlot::create($this->validated($request));

        AuditLogger::log('TIME_SLOT_CREATED', $slot, null, $slot->toArray());

        return back()->with('success', "Added period {$slot->slot_number} on {$slot->day}.");
    }

    public function update(Request $request, TimeSlot $timeslot): RedirectResponse
    {
        $old = $timeslot->toArray();
        $timeslot->update($this->validated($request, $timeslot));

        AuditLogger::log('TIME_SLOT_UPDATED', $timeslot, $old, $timeslot->toArray());

        return back()->with('success', "Updated period {$timeslot->slot_number} on {$timeslot->day}.");
    }

    public function destroy(TimeSlot $timeslot): RedirectResponse
    {
        try {
            DB::transaction(function () use ($timeslot) {
                DB::table('teacher_availability')->where('time_slot_id', $timeslot->id)->delete();
                $timeslot->delete();
            });
        } catch (QueryException $e) {
            return back()->with('error', 'This period is used by a saved routine and cannot be deleted.');
        }

        AuditLogger::log('TIME_SLOT_DELETED', null, $timeslot->toArray());

        return back()->with('success', "Deleted period {$timeslot->slot_number} on {$timeslot->day}.");
    }

    /**
     * Copy every period of one day to another (e.g. add Saturday
     * with the same timings as Sunday).
     */
    public function copyDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_day' => ['required', 'string'],
            'to_day' => ['required', 'different:from_day', Rule::in(config('intellisched.days'))],
        ]);

        $source = TimeSlot::where('day', strtoupper($data['from_day']))->get();
        $created = 0;

        foreach ($source as $slot) {
            $copy = TimeSlot::firstOrCreate(
                ['day' => $data['to_day'], 'slot_number' => $slot->slot_number],
                ['start_time' => $slot->start_time, 'end_time' => $slot->end_time, 'is_break' => $slot->is_break]
            );

            $created += $copy->wasRecentlyCreated ? 1 : 0;
        }

        AuditLogger::log('TIME_SLOTS_COPIED', null, null, $data + ['created' => $created]);

        return back()->with('success', "Copied {$created} period(s) from {$data['from_day']} to {$data['to_day']}. Set teacher availability for the new day.");
    }

    private function validated(Request $request, ?TimeSlot $slot = null): array
    {
        $data = $request->validate([
            'day' => ['required', Rule::in(config('intellisched.days'))],
            'slot_number' => ['required', 'integer', 'min:1', 'max:30'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_break' => ['nullable', 'boolean'],
        ]);

        $duplicate = TimeSlot::where('day', $data['day'])
            ->where('slot_number', $data['slot_number'])
            ->when($slot, fn ($query) => $query->where('id', '!=', $slot->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'slot_number' => "{$data['day']} already has a period {$data['slot_number']}.",
            ]);
        }

        $data['is_break'] = $request->boolean('is_break') ? 1 : 0;

        return $data;
    }
}
