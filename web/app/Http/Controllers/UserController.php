<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    // Teacher accounts are created from the Teachers page, together
    // with their teaching profile.
    private const MANAGED_ROLES = ['admin', 'coordinator', 'student'];

    public function index(Request $request): View
    {
        $role = strtolower((string) $request->query('role'));

        $users = User::with(['teacher', 'section.batch'])
            ->when(array_key_exists($role, config('intellisched.roles')),
                fn ($query) => $query->whereRaw('LOWER(role) = ?', [$role]))
            ->orderBy('name')
            ->get();

        $counts = User::all()->countBy(fn ($user) => strtolower($user->role));

        return view('users.index', compact('users', 'role', 'counts'));
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['role' => 'coordinator', 'is_active' => 1]),
            'sections' => $this->sectionOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create($this->validated($request));

        AuditLogger::log('USER_CREATED', $user, null, $user->only(['name', 'email', 'role', 'section_id', 'class_rep']));

        return redirect()->route('users.index')->with('success', "Account for {$user->name} created.");
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user, 'sections' => $this->sectionOptions()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $old = $user->only(['name', 'email', 'role', 'is_active', 'section_id', 'class_rep']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Never lock yourself out.
        if ($user->is($request->user())) {
            $data['is_active'] = 1;
            $data['role'] = $user->role;
        }

        $user->update($data);

        AuditLogger::log('USER_UPDATED', $user, $old, $user->only(['name', 'email', 'role', 'is_active', 'section_id', 'class_rep']));

        return redirect()->route('users.index')->with('success', "Account for {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->teacher) {
            return back()->with('error', 'This account belongs to a teacher. Delete it from the Teachers page.');
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            return back()->with('error', "{$user->name} has generated routines or exams. Deactivate the account instead.");
        }

        AuditLogger::log('USER_DELETED', null, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', "Account for {$user->name} deleted.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        // Existing teacher accounts keep the teacher role.
        $roles = $user?->teacher ? ['teacher'] : self::MANAGED_ROLES;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in($roles)],
            'is_active' => ['nullable', 'boolean'],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')],
            'class_rep' => ['nullable', Rule::in(array_keys(config('intellisched.class_reps')))],
        ]);

        $data['is_active'] = $request->boolean('is_active') ? 1 : 0;

        // Only students belong to a section and can be CR/ACR.
        if (strtolower($data['role']) !== 'student') {
            $data['section_id'] = null;
            $data['class_rep'] = null;
        }

        if (!empty($data['class_rep'])) {
            if (empty($data['section_id'])) {
                throw ValidationException::withMessages([
                    'section_id' => "Choose the section this {$data['class_rep']} represents.",
                ]);
            }

            // One CR and one ACR per section.
            $holder = User::where('section_id', $data['section_id'])
                ->where('class_rep', $data['class_rep'])
                ->when($user, fn ($query) => $query->where('id', '!=', $user->id))
                ->first();

            if ($holder) {
                throw ValidationException::withMessages([
                    'class_rep' => "{$holder->name} is already the {$data['class_rep']} of this section.",
                ]);
            }
        }

        return $data;
    }

    /**
     * Sections grouped by batch for the student section menu.
     */
    private function sectionOptions(): Collection
    {
        return Section::with('batch')
            ->get()
            ->sortBy(fn ($section) => [(int) ($section->batch?->seniority_order ?? 99), $section->name])
            ->groupBy(fn ($section) => $section->batch?->name ?? 'Other');
    }
}
