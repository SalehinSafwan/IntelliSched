@extends('layouts.app')

@section('title', $teacher->exists ? 'Edit Teacher' : 'New Teacher')

@section('content')

<x-page-header :back="$teacher->exists ? route('teachers.show', $teacher) : route('teachers.index')"
               :back-label="$teacher->exists ? $teacher->display_name : 'All Teachers'" eyebrow="Faculty"
               :title="$teacher->exists ? 'Edit ' . $teacher->display_name : 'New Teacher'" />

<form method="POST" action="{{ $teacher->exists ? route('teachers.update', $teacher) : route('teachers.store') }}">
    @csrf
    @if($teacher->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="glass-card h-100">
                <div class="form-section-title">Login account</div>
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label-custom" for="name">Full Name *</label>
                        <input id="name" name="name" class="form-control form-control-custom" required
                               value="{{ old('name', $user->name) }}" placeholder="Dr. Jane Doe">
                    </div>
                    <div class="col-12">
                        <label class="form-label-custom" for="email">Email *</label>
                        <input id="email" name="email" type="email" class="form-control form-control-custom" required
                               value="{{ old('email', $user->email) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label-custom" for="password">{{ $teacher->exists ? 'New Password' : 'Password *' }}</label>
                        <input id="password" name="password" type="password" class="form-control form-control-custom"
                               {{ $teacher->exists ? '' : 'required' }} minlength="8" autocomplete="new-password">
                        <div class="form-hint">{{ $teacher->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters. The teacher signs in with this email and password.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="glass-card h-100">
                <div class="form-section-title">Teaching profile</div>
                <div class="row g-4">
                    <div class="col-6">
                        <label class="form-label-custom" for="employee_code">Employee Code *</label>
                        <input id="employee_code" name="employee_code" class="form-control form-control-custom" required
                               value="{{ old('employee_code', $teacher->employee_code) }}" placeholder="T004">
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom" for="department">Department *</label>
                        <input id="department" name="department" class="form-control form-control-custom" required
                               value="{{ old('department', $teacher->department) }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom" for="max_weekly_classes">Max Periods / Week *</label>
                        <input id="max_weekly_classes" name="max_weekly_classes" type="number" min="1" max="40" class="form-control form-control-custom" required
                               value="{{ old('max_weekly_classes', $teacher->max_weekly_classes) }}">
                        <div class="form-hint">A lab counts as 3 periods.</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom" for="status">Status *</label>
                        <select id="status" name="status" class="form-select form-select-custom">
                            @foreach(config('intellisched.statuses') as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $teacher->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Inactive teachers cannot sign in and are not scheduled.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between gap-2 mt-4">
        <div>
            @if($teacher->exists)
                <button type="submit" form="deleteTeacher" class="btn-glass btn-danger-glass"><i class="bi bi-trash3"></i> Delete Teacher</button>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $teacher->exists ? route('teachers.show', $teacher) : route('teachers.index') }}" class="btn-glass">Cancel</a>
            <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $teacher->exists ? 'Save Changes' : 'Create Teacher' }}</button>
        </div>
    </div>
</form>

@if($teacher->exists)
    <form id="deleteTeacher" method="POST" action="{{ route('teachers.destroy', $teacher) }}"
          onsubmit="return confirm('Delete this teacher and their login account?');">
        @csrf
        @method('DELETE')
    </form>
@endif

@endsection
