<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AcademicTermController extends Controller
{
    public function index(): View
    {
        $terms = AcademicTerm::withCount(['courseOfferings', 'scheduleRuns'])
            ->orderByDesc('start_date')
            ->get();

        return view('terms.index', compact('terms'));
    }

    public function create(): View
    {
        return view('terms.form', ['term' => new AcademicTerm()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $term = AcademicTerm::create($this->validated($request) + ['status' => 'INACTIVE']);

        AuditLogger::log('TERM_CREATED', $term, null, $term->toArray());

        return redirect()->route('terms.index')
            ->with('success', "Academic term {$term->name} created.");
    }

    public function edit(AcademicTerm $term): View
    {
        return view('terms.form', compact('term'));
    }

    public function update(Request $request, AcademicTerm $term): RedirectResponse
    {
        $old = $term->toArray();
        $term->update($this->validated($request));

        AuditLogger::log('TERM_UPDATED', $term, $old, $term->toArray());

        return redirect()->route('terms.index')
            ->with('success', "Academic term {$term->name} updated.");
    }

    /**
     * Make this the current term; every other term becomes inactive.
     */
    public function activate(AcademicTerm $term): RedirectResponse
    {
        DB::transaction(function () use ($term) {
            AcademicTerm::where('id', '!=', $term->id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'INACTIVE']);

            $term->update(['status' => 'ACTIVE']);
        });

        AuditLogger::log('TERM_ACTIVATED', $term);

        return back()->with('success', "{$term->name} is now the current term.");
    }

    public function destroy(AcademicTerm $term): RedirectResponse
    {
        // Deleting a term would cascade to its offerings and routines.
        if ($term->courseOfferings()->exists() || $term->scheduleRuns()->exists()) {
            return back()->with('error',
                "{$term->name} has course offerings or routines. Remove them first, or keep the term inactive."
            );
        }

        try {
            $term->delete();
        } catch (QueryException $e) {
            return back()->with('error', "{$term->name} is still in use and cannot be deleted.");
        }

        AuditLogger::log('TERM_DELETED', null, $term->toArray());

        return redirect()->route('terms.index')
            ->with('success', "Academic term {$term->name} deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:30'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
    }
}
