@extends('layouts.app')

@section('title', $user->exists ? 'Edit Account' : 'New Account')

@section('content')

@php
    $isTeacher = $user->exists && $user->teacher;
    $isSelf = $user->exists && $user->is(auth()->user());
@endphp

<x-page-header :back="route('users.index')" back-label="All Accounts" eyebrow="Administration"
               :title="$user->exists ? 'Edit ' . $user->name : 'New Account'" />

<div class="glass-card" style="max-width: 760px;">
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="name">Full Name *</label>
                <input id="name" name="name" class="form-control form-control-custom" required value="{{ old('name', $user->name) }}">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="email">Email *</label>
                <input id="email" name="email" type="email" class="form-control form-control-custom" required value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="password">{{ $user->exists ? 'New Password' : 'Password *' }}</label>
                <input id="password" name="password" type="password" minlength="8" class="form-control form-control-custom"
                       {{ $user->exists ? '' : 'required' }} autocomplete="new-password">
                @if($user->exists)<div class="form-hint">Leave blank to keep the current password.</div>@endif
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label-custom" for="role">Role *</label>
                <select id="role" name="role" class="form-select form-select-custom" @disabled($isSelf)>
                    @if($isTeacher)
                        <option value="teacher" selected>Teacher</option>
                    @else
                        @foreach(['admin', 'coordinator', 'student'] as $value)
                            <option value="{{ $value }}" @selected(old('role', strtolower((string) $user->role)) === $value)>{{ config('intellisched.roles')[$value] }}</option>
                        @endforeach
                    @endif
                </select>
                @if($isSelf)
                    <input type="hidden" name="role" value="{{ strtolower($user->role) }}">
                    <div class="form-hint">You cannot change your own role.</div>
                @endif
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $user->is_active)) @disabled($isSelf)>
                    <label class="form-check-label text-muted" for="is_active">Account is active (can sign in)</label>
                    @if($isSelf)<input type="hidden" name="is_active" value="1">@endif
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('users.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $user->exists ? 'Save Changes' : 'Create Account' }}</button>
        </div>
    </form>
</div>

@endsection
