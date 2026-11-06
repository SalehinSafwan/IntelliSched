@extends('layouts.app')

@section('title', 'New Message')

@section('content')

@php $categories = config('intellisched.message_categories'); @endphp

<x-page-header :back="route('messages.index')" back-label="Messages" eyebrow="Teacher Portal" title="Message a Section's CR / ACR"
               subtitle="Only the class representatives of the section you choose receive this message." />

@if($sections->isEmpty())
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No sections to message</h4>
        <p class="text-muted">You can message sections you teach in a published routine or supervise.</p>
        <a href="{{ route('my.supervision') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> Add Supervised Section</a>
    </div>
@else
<form method="POST" action="{{ route('messages.store') }}" class="row g-4">
    @csrf
    <div class="col-12 col-xl-8">
        <div class="glass-card">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label-custom" for="section_id">To the CR/ACR of *</label>
                    <select id="section_id" name="section_id" class="form-select form-select-custom @error('section_id') is-invalid @enderror" required>
                        <option value="">Choose a section…</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected((int) old('section_id', $prefill['section_id']) === (int) $section->id)>
                                {{ $section->label }}{{ $section->supervises ? ' (supervised)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('section_id')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                    <div class="form-hint" id="recipientHint"></div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label-custom" for="course_id">Course</label>
                    <select id="course_id" name="course_id" class="form-select form-select-custom @error('course_id') is-invalid @enderror">
                        <option value="">Not about a specific course</option>
                    </select>
                    @error('course_id')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom">Type *</label>
                    <div class="choice-grid">
                        @foreach($categories as $value => $category)
                            <label class="choice-card">
                                <input type="radio" name="category" value="{{ $value }}" @checked(old('category', $prefill['category'] ?: 'GENERAL') === $value)>
                                <span class="choice-body">
                                    <span class="choice-title"><i class="bi {{ $category['icon'] }} me-1"></i> {{ $category['label'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('category')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-6" data-show="RESCHEDULE CANCEL ROOM_CHANGE ASSESSMENT">
                    <label class="form-label-custom" for="class_date"><span data-label-for="class_date">Class date</span></label>
                    <input id="class_date" name="class_date" type="date" class="form-control form-control-custom date-input @error('class_date') is-invalid @enderror" value="{{ old('class_date') }}">
                    @error('class_date')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6" data-show="RESCHEDULE CANCEL ROOM_CHANGE ASSESSMENT">
                    <label class="form-label-custom" for="time_slot_id">Period</label>
                    <select id="time_slot_id" name="time_slot_id" class="form-select form-select-custom">
                        <option value="">—</option>
                        @foreach($slots as $slot)
                            <option value="{{ $slot['id'] }}" @selected((int) old('time_slot_id') === $slot['id'])>{{ $slot['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6" data-show="RESCHEDULE EXTRA_CLASS">
                    <label class="form-label-custom" for="new_date">New date *</label>
                    <input id="new_date" name="new_date" type="date" class="form-control form-control-custom date-input @error('new_date') is-invalid @enderror" value="{{ old('new_date') }}">
                    @error('new_date')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6" data-show="RESCHEDULE EXTRA_CLASS">
                    <label class="form-label-custom" for="new_time_slot_id">New period</label>
                    <select id="new_time_slot_id" name="new_time_slot_id" class="form-select form-select-custom">
                        <option value="">—</option>
                        @foreach($slots as $slot)
                            <option value="{{ $slot['id'] }}" data-day="{{ $slot['day'] }}" @selected((int) old('new_time_slot_id', $prefill['new_time_slot_id']) === $slot['id'])>{{ $slot['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Use the Free Slot Finder to pick a period when everyone is free.</div>
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="subject">Subject *</label>
                    <input id="subject" name="subject" maxlength="150" required class="form-control form-control-custom @error('subject') is-invalid @enderror" value="{{ old('subject') }}">
                    @error('subject')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label-custom" for="body">Message *</label>
                    <textarea id="body" name="body" rows="6" maxlength="2000" required class="form-control form-control-custom @error('body') is-invalid @enderror"
                              placeholder="Details for the class representatives to pass on to students.">{{ old('body') }}</textarea>
                    @error('body')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="glass-card">
            <div class="form-section-title mb-2">Delivery</div>
            <ul class="check-list mb-4">
                <li><i class="bi bi-shield-lock"></i> Only the CR and ACR of the chosen section can read and reply.</li>
                <li><i class="bi bi-eye"></i> You can see when each of them has opened it.</li>
                <li><i class="bi bi-chat-dots"></i> Replies come back to you in the same thread.</li>
            </ul>
            <button class="btn-indigo w-100 justify-content-center"><i class="bi bi-send"></i> Send Message</button>
        </div>
    </div>
</form>
@endif

@push('scripts')
<script>
    (function () {
        const courses = @json($courses);
        const recipients = @json($recipients);
        const oldCourse = @json((int) old('course_id'));
        const section = document.getElementById('section_id');
        const course = document.getElementById('course_id');
        const hint = document.getElementById('recipientHint');

        if (!section) return;

        function refreshSection() {
            const list = courses[section.value] || [];
            course.innerHTML = '<option value="">Not about a specific course</option>';
            list.forEach(function (c) {
                const option = new Option(c.label, c.id, false, c.id === oldCourse);
                course.add(option);
            });

            const reps = recipients[section.value] || [];
            hint.innerHTML = '';
            if (section.value) {
                hint.textContent = reps.length ? 'Recipients: ' + reps.join(', ') : 'This section has no CR or ACR yet; the message cannot be sent.';
                hint.style.color = reps.length ? '' : '#fca5a5';
            }
        }

        function refreshCategory() {
            const checked = document.querySelector('input[name="category"]:checked');
            const value = checked ? checked.value : '';
            document.querySelectorAll('[data-show]').forEach(function (el) {
                el.hidden = !el.dataset.show.split(' ').includes(value);
            });
            const label = document.querySelector('[data-label-for="class_date"]');
            label.textContent = (value === 'RESCHEDULE' || value === 'CANCEL') ? 'Original class date *' : 'Class date';
        }

        section.addEventListener('change', refreshSection);
        document.querySelectorAll('input[name="category"]').forEach(function (input) {
            input.addEventListener('change', refreshCategory);
        });

        refreshSection();
        refreshCategory();
    })();
</script>
@endpush

@endsection
