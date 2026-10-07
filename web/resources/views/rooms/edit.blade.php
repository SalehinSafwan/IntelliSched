@extends('layouts.app')

@section('title', 'Edit Room — LAB-01')

@section('content')

<div class="mb-4">
    <a href="{{ route('rooms.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Rooms Directory
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">Edit Room — LAB-01</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Update room parameters and priority ordering.</p>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <form action="{{ route('rooms.index') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Room Number</label>
                        <input type="text" name="room_number" class="form-control form-control-custom" value="LAB-01" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label-custom">Room Name / Label</label>
                        <input type="text" name="name" class="form-control form-control-custom" value="Database & Software Lab" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Room Type</label>
                        <select name="type" class="form-select form-select-custom" required>
                            <option value="Theory">Theory Classroom</option>
                            <option value="Lab" selected>Specialized Lab</option>
                            <option value="Lab/Theory">Lab / Theory Multi-use</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Seating Capacity</label>
                        <input type="number" name="capacity" class="form-control form-control-custom" value="30" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Scheduling Priority</label>
                        <input type="number" name="priority" class="form-control form-control-custom" value="5">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Building</label>
                        <input type="text" name="building" class="form-control form-control-custom" value="CSE Building">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Floor Level</label>
                        <input type="text" name="floor" class="form-control form-control-custom" value="2nd Floor">
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
                        <i class="bi bi-check-lg"></i> Update Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
