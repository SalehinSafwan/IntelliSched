@extends('layouts.app')

@section('title', 'Teacher Profile Missing')

@section('content')

<div class="glass-card text-center py-5">
    <div class="empty-state-icon"><i class="bi bi-person-exclamation"></i></div>
    <h4 class="brand-font fw-bold text-white mt-4">Your account has no teacher profile</h4>
    <p class="text-muted mb-1">
        {{ auth()->user()->email }} has the teacher role, but no teaching profile is linked to it, so there is no schedule,
        availability, supervision or message history to show.
    </p>
    <p class="text-muted">
        Ask an administrator to open <span class="text-white">Teachers</span> and create your profile with this email address,
        or sign in with the account your profile is linked to.
    </p>
    <a href="{{ route('dashboard') }}" class="btn-glass"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>

@endsection
