<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    // Teacher accounts are created from the Teachers page, together
    // with their teaching profile.
    private const MANAGED_ROLES = ['admin', 'coordinator', 'student'];

    public function index(Request $request): View
    {
        $role = strtolower((string) $request->query('role'));

        $users = User::with('teacher')
            ->when(array_key_exists($role, config('intellisched.roles')),
                fn ($query) => $query->whereRaw('LOWER(role) = ?', [$role]))
            ->orderBy('name')
            ->get();

        $counts = User::all()->countBy(fn ($user) => strtolower($user->role));

        return view('users.index', compact('users', 'role', 'counts'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => 'coordinator', 'is_active' => 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create($this->validated($request));

        AuditLogger::log('USER_CREATED', $user, null, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', "Account for {$user->name} created.");
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $old = $user->only(['name', 'email', 'role', 'is_active']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Never lock yourself out.
        if ($user->is($request->user())) {
            $data['is_active'] = 1;
            $data['role'] = $user->role;
        }

        $user->update($data);

        AuditLogger::log('USER_UPDATED', $user, $old, $user->only(['name', 'email', 'role', 'is_active']));

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
        ]);

        $data['is_active'] = $request->boolean('is_active') ? 1 : 0;

        return $data;
    }
}
