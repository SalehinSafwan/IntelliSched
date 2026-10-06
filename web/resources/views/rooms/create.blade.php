@extends('layouts.app')

@section('title', 'Add New Room')

@section('content')

<div class="mb-4">
    <a href="{{ route('rooms.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Rooms Directory
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">Add Classroom / Laboratory</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Register room dimensions, capacity, type, and priority ordering.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <form action="{{ route('rooms.index') }}" method="POST">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Room Number <span class="text-danger">*</span></label>
                        <input type="text" name="room_number" class="form-control form-control-custom" placeholder="e.g. 101 or LAB-01" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Room Name / Label <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Database & Software Lab" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Room Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-custom" required>
                            <option value="Theory" selected>Theory Classroom</option>
                            <option value="Lab">Specialized Lab</option>
                            <option value="Lab/Theory">Lab / Theory Multi-use</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Seating Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" class="form-control form-control-custom" value="60" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Scheduling Priority</label>
                        <input type="number" name="priority" class="form-control form-control-custom" value="1" placeholder="1 = Highest">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Building</label>
                        <input type="text" name="building" class="form-control form-control-custom" placeholder="CSE Building">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Floor Level</label>
                        <input type="text" name="floor" class="form-control form-control-custom" placeholder="2nd Floor">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Status</label>
                        <select name="status" class="form-select form-select-custom">
                            <option value="ACTIVE" selected>Active</option>
                            <option value="MAINTENANCE">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top border-secondary">
                    <a href="{{ route('rooms.index') }}" class="btn-glass">Cancel</a>
                    <button type="submit" class="btn-indigo">
                        <i class="bi bi-check-lg"></i> Register Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
