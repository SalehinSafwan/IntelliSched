@extends('layouts.app')

@section('title', 'Users')

@section('content')

<x-page-header eyebrow="Administration" title="User Accounts"
               subtitle="Administrators manage everything; coordinators manage academic data, routines and exams; teacher accounts are created from the Teachers page.">
    <a href="{{ route('users.create') }}" class="btn-indigo"><i class="bi bi-person-plus"></i> New Account</a>
</x-page-header>

<div class="filter-pills mb-4">
    <a href="{{ route('users.index') }}" class="{{ !$role ? 'active' : '' }}">All · {{ $counts->sum() }}</a>
    @foreach(config('intellisched.roles') as $value => $label)
        <a href="{{ route('users.index', ['role' => $value]) }}" class="{{ $role === $value ? 'active' : '' }}">{{ $label }} · {{ $counts[$value] ?? 0 }}</a>
    @endforeach
</div>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th class="text-end"></th></tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <span class="fw-semibold text-white">{{ $user->name }}</span>
                            @if($user->is(auth()->user()))<span class="badge-custom badge-gold">You</span>@endif
                        </div>
                    </td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td><span class="badge-custom badge-role">{{ config('intellisched.roles')[strtolower($user->role)] ?? $user->role }}</span></td>
                    <td><x-status-badge :status="$user->is_active ? 'ACTIVE' : 'INACTIVE'" /></td>
                    <td class="text-muted">{{ $user->created_at?->format('d M Y') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            @if($user->teacher)
                                <a href="{{ route('teachers.show', $user->teacher) }}" class="btn-glass btn-sm-custom"><i class="bi bi-person-video3"></i> Teacher</a>
                            @endif
                            <a href="{{ route('users.edit', $user) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                            @unless($user->is(auth()->user()) || $user->teacher)
                                <x-delete-button :action="route('users.destroy', $user)" :confirm="'Delete the account of ' . $user->name . '?'" />
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No accounts match this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
