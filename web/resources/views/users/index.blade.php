@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="brand-font fw-bold text-white mb-1">User Account Management</h2>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Manage system user accounts, assigned system roles, and access credentials.</p>
    </div>
    <div>
        <button class="btn-indigo" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add System User
        </button>
    </div>
</div>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>User Name</th>
                <th>Email Address</th>
                <th>Assigned Role</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
                $users = [
                    ['name' => 'System Administrator', 'email' => 'admin@intellisched.edu', 'role' => 'ADMIN', 'status' => 'ACTIVE'],
                    ['name' => 'Dr. Ahmed Rahman', 'email' => 'ahmed@intellisched.edu', 'role' => 'TEACHER', 'status' => 'ACTIVE'],
                    ['name' => 'Academic Coordinator', 'email' => 'coord@intellisched.edu', 'role' => 'COORDINATOR', 'status' => 'ACTIVE'],
                    ['name' => 'Sabbir Ahmed (CR)', 'email' => 'student@intellisched.edu', 'role' => 'STUDENT', 'status' => 'ACTIVE'],
                ];
            @endphp

            @foreach($users as $u)
                <tr>
                    <td class="fw-semibold text-white">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ strtoupper(substr($u['name'], 0, 1)) }}
                            </div>
                            {{ $u['name'] }}
                        </div>
                    </td>
                    <td class="text-indigo">{{ $u['email'] }}</td>
                    <td>
                        <span class="badge-custom badge-role">{{ $u['role'] }}</span>
                    </td>
                    <td><span class="badge-custom badge-active">Active</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-glass py-1 px-2 me-1"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-glass text-danger py-1 px-2"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<x-modal id="addUserModal" title="Add System User">
    <form action="{{ route('users.index') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label-custom">Full Name</label>
            <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Dr. Farhana Nusrat" required>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">Email Address</label>
            <input type="email" name="email" class="form-control form-control-custom" placeholder="farhana@intellisched.edu" required>
        </div>
        <div class="mb-3">
            <label class="form-label-custom">System Role</label>
            <select name="role" class="form-select form-select-custom">
                <option value="ADMIN">Administrator</option>
                <option value="COORDINATOR">Coordinator</option>
                <option value="TEACHER" selected>Teacher</option>
                <option value="STUDENT">Student / CR</option>
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-indigo">Create User</button>
        </div>
    </form>
</x-modal>

@endsection
