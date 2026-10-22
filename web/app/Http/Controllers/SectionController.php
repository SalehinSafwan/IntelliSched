<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Section;
use App\Models\ScheduleEntry;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function store(Request $request, Batch $batch): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20',
                Rule::unique('sections', 'name')->where('batch_id', $batch->id)],
            'student_count' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $section = $batch->sections()->create($data + ['status' => 'ACTIVE']);

        AuditLogger::log('SECTION_CREATED', $section, null, $section->toArray());

        return back()->with('success',
            "Section {$section->name} added. Re-save the course list to give it this term's courses."
        );
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20',
                Rule::unique('sections', 'name')
                    ->where('batch_id', $section->batch_id)
                    ->ignore($section->id)],
            'student_count' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(array_keys(config('intellisched.statuses')))],
        ]);

        $old = $section->toArray();
        $section->update($data);

        AuditLogger::log('SECTION_UPDATED', $section, $old, $section->toArray());

        return back()->with('success', "Section {$section->name} updated.");
    }

    public function destroy(Section $section): RedirectResponse
    {
        $used = ScheduleEntry::whereIn(
            'course_offering_id',
            $section->courseOfferings()->pluck('id')
        )->exists();

        if ($used) {
            return back()->with('error',
                "Section {$section->name} appears in a saved routine. Mark it inactive instead."
            );
        }

        $section->delete();

        AuditLogger::log('SECTION_DELETED', null, $section->toArray());

        return back()->with('success', "Section {$section->name} deleted.");
    }
}
