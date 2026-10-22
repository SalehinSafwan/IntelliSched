@extends('layouts.app')

@section('title', $room->exists ? 'Edit Room' : 'New Room')

@section('content')

<x-page-header :back="$room->exists ? route('rooms.show', $room) : route('rooms.index')"
               :back-label="$room->exists ? $room->room_code : 'All Rooms'" eyebrow="Infrastructure"
               :title="$room->exists ? 'Edit ' . $room->room_code : 'New Room'" />

<form method="POST" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}">
    @csrf
    @if($room->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="glass-card h-100">
                <div class="form-section-title">Room details</div>
                <div class="row g-4">
                    <div class="col-12 col-md-5">
                        <label class="form-label-custom" for="room_code">Room Code *</label>
                        <input id="room_code" name="room_code" class="form-control form-control-custom" required
                               value="{{ old('room_code', $room->room_code) }}" placeholder="R-101">
                    </div>
                    <div class="col-12 col-md-7">
                        <label class="form-label-custom" for="room_name">Room Name *</label>
                        <input id="room_name" name="room_name" class="form-control form-control-custom" required
                               value="{{ old('room_name', $room->room_name) }}" placeholder="Classroom 101">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label-custom" for="capacity">Capacity *</label>
                        <input id="capacity" name="capacity" type="number" min="1" class="form-control form-control-custom" required
                               value="{{ old('capacity', $room->capacity) }}">
                        <div class="form-hint">Seats; must fit a whole section.</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label-custom" for="priority">Priority *</label>
                        <input id="priority" name="priority" type="number" min="1" max="999" class="form-control form-control-custom" required
                               value="{{ old('priority', $room->priority) }}">
                        <div class="form-hint">1 = most preferred.</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom" for="status">Status *</label>
                        <select id="status" name="status" class="form-select form-select-custom">
                            @foreach(config('intellisched.statuses') as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $room->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Inactive rooms are never scheduled.</div>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label-custom" for="building">Building</label>
                        <input id="building" name="building" class="form-control form-control-custom"
                               value="{{ old('building', $room->building) }}" placeholder="Academic Building">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom" for="floor">Floor</label>
                        <input id="floor" name="floor" class="form-control form-control-custom"
                               value="{{ old('floor', $room->floor) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="glass-card h-100">
                <div class="form-section-title">Room type</div>
                <div class="d-grid gap-2 mb-4">
                    @foreach(config('intellisched.room_types') as $value => $label)
                        <label class="choice-card">
                            <input type="radio" name="room_type" value="{{ $value }}" class="js-room-type"
                                   @checked(old('room_type', $room->room_type) === $value)>
                            <span class="choice-body">
                                <span class="choice-title">{{ $label }}</span>
                                <span class="choice-sub">
                                    @switch($value)
                                        @case('THEORY') Hosts theory classes only. @break
                                        @case('LAB') Hosts labs of the courses selected below. @break
                                        @default Hosts theory classes and the selected labs.
                                    @endswitch
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div id="labCoursesBlock">
                    <div class="form-section-title">Eligible lab courses</div>
                    @php $selected = old('eligible_courses', $selectedCourses); @endphp
                    <div class="choice-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
                        @forelse($labCourses as $course)
                            <label class="choice-card">
                                <input type="checkbox" name="eligible_courses[]" value="{{ $course->id }}"
                                       @checked(in_array($course->id, array_map('intval', $selected)))>
                                <span class="choice-body">
                                    <span class="choice-title">{{ $course->course_code }}</span>
                                    <span class="choice-sub">{{ $course->course_name }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="text-muted mb-0">No lab courses exist yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ $room->exists ? route('rooms.show', $room) : route('rooms.index') }}" class="btn-glass">Cancel</a>
        <button class="btn-indigo"><i class="bi bi-check2"></i> {{ $room->exists ? 'Save Changes' : 'Create Room' }}</button>
    </div>
</form>

@push('scripts')
<script>
    // Lab eligibility applies to lab and multipurpose rooms only.
    function syncLabBlock() {
        const theory = document.querySelector('.js-room-type[value="THEORY"]').checked;
        document.getElementById('labCoursesBlock').hidden = theory;
    }
    document.querySelectorAll('.js-room-type').forEach(el => el.addEventListener('change', syncLabBlock));
    syncLabBlock();
</script>
@endpush

@endsection
