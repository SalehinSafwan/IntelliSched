@extends('layouts.app')

@section('title', 'Time Slots')

@section('content')

<x-page-header eyebrow="Infrastructure" title="Weekly Time Slots"
               subtitle="Click any period to edit it. Labs need 3 consecutive teaching periods without a break between them.">
    <button type="button" class="btn-glass" data-bs-toggle="modal" data-bs-target="#copyDayModal"><i class="bi bi-copy"></i> Copy a Day</button>
    <button type="button" class="btn-indigo" data-bs-toggle="modal" data-bs-target="#slotModal" data-mode="create"><i class="bi bi-plus-lg"></i> New Period</button>
</x-page-header>

@if($slots->isEmpty())
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-clock"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No periods defined</h4>
        <p class="text-muted">Add the first period of a day, then copy the day to the rest of the week.</p>
    </div>
@else
    <div class="timetable-container mb-4">
        <table class="pref-grid">
            <thead>
                <tr>
                    <th style="width: 90px;">PERIOD</th>
                    @foreach($days as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($slotNumbers as $number)
                    <tr>
                        <td class="time-cell">#{{ $number }}</td>
                        @foreach($days as $day)
                            @php
                                $slot = $grid[$number][$day] ?? null;
                                $slotJson = $slot ? json_encode([
                                    'id' => $slot->id,
                                    'day' => strtoupper($slot->day),
                                    'slot_number' => (int) $slot->slot_number,
                                    'start_time' => $slot->start_time,
                                    'end_time' => $slot->end_time,
                                    'is_break' => (bool) $slot->is_break,
                                ]) : null;
                            @endphp
                            @if($slot)
                                <td class="pref-cell {{ $slot->is_break ? 'is-break' : '' }}" data-state="{{ $slot->is_break ? '' : 'available' }}"
                                    style="cursor: pointer;"
                                    data-bs-toggle="modal" data-bs-target="#slotModal" data-mode="edit"
                                    data-slot="{{ $slotJson }}">
                                    {{ $slot->start_time }} – {{ $slot->end_time }}
                                    @if($slot->is_break)<div style="font-size: 0.62rem; letter-spacing: 0.2em;">BREAK</div>@endif
                                </td>
                            @else
                                <td style="opacity: 0.25;"></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-dim" style="font-size: 0.8rem;">{{ $slots->where('is_break', 0)->count() }} teaching periods and {{ $slots->where('is_break', 1)->count() }} breaks across {{ $days->count() }} days.</p>
@endif

<!-- Create / edit period -->
<x-modal id="slotModal" title="Period">
    <form method="POST" id="slotForm" action="{{ route('timeslots.store') }}">
        @csrf
        <input type="hidden" name="_method" id="slotMethod" value="POST">
        <div class="row g-3">
            <div class="col-7">
                <label class="form-label-custom" for="slot_day">Day</label>
                <select id="slot_day" name="day" class="form-select form-select-custom">
                    @foreach(config('intellisched.days') as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-5">
                <label class="form-label-custom" for="slot_number">Period #</label>
                <input id="slot_number" name="slot_number" type="number" min="1" class="form-control form-control-custom" required>
            </div>
            <div class="col-6">
                <label class="form-label-custom" for="slot_start">Start</label>
                <input id="slot_start" name="start_time" type="time" class="form-control form-control-custom date-input" required>
            </div>
            <div class="col-6">
                <label class="form-label-custom" for="slot_end">End</label>
                <input id="slot_end" name="end_time" type="time" class="form-control form-control-custom date-input" required>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="slot_break" name="is_break">
                    <label class="form-check-label text-muted" for="slot_break">This period is a break (no classes)</label>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn-glass btn-danger-glass" id="slotDelete" hidden><i class="bi bi-trash3"></i> Delete</button>
            <button class="btn-indigo ms-auto"><i class="bi bi-check2"></i> Save Period</button>
        </div>
    </form>
    <form method="POST" id="slotDeleteForm" hidden>
        @csrf
        @method('DELETE')
    </form>
</x-modal>

<!-- Copy a day -->
<x-modal id="copyDayModal" title="Copy a Day's Periods">
    <form method="POST" action="{{ route('timeslots.copy') }}">
        @csrf
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label-custom" for="from_day">From</label>
                <select id="from_day" name="from_day" class="form-select form-select-custom">
                    @foreach($days as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label-custom" for="to_day">To</label>
                <select id="to_day" name="to_day" class="form-select form-select-custom">
                    @foreach(config('intellisched.days') as $day)
                        <option value="{{ $day }}" @selected($unusedDays->first() === $day)>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="form-hint mt-3">Existing periods on the target day are kept; only missing period numbers are added.</p>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn-indigo"><i class="bi bi-copy"></i> Copy Periods</button>
        </div>
    </form>
</x-modal>

@push('scripts')
<script>
    // One modal for both creating and editing a period.
    const slotModal = document.getElementById('slotModal');
    const baseUrl = @json(url('timeslots'));

    slotModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const form = document.getElementById('slotForm');
        const del = document.getElementById('slotDelete');
        const slot = trigger && trigger.dataset.slot ? JSON.parse(trigger.dataset.slot) : null;

        slotModal.querySelector('.modal-title').textContent = slot ? 'Edit Period' : 'New Period';
        form.action = slot ? baseUrl + '/' + slot.id : @json(route('timeslots.store'));
        document.getElementById('slotMethod').value = slot ? 'PUT' : 'POST';
        document.getElementById('slot_day').value = slot ? slot.day : @json($days->first() ?? 'SUNDAY');
        document.getElementById('slot_number').value = slot ? slot.slot_number : '';
        document.getElementById('slot_start').value = slot ? slot.start_time : '';
        document.getElementById('slot_end').value = slot ? slot.end_time : '';
        document.getElementById('slot_break').checked = slot ? slot.is_break : false;
        del.hidden = !slot;
        del.onclick = function () {
            if (confirm('Delete this period? Teacher availability for it is removed too.')) {
                const deleteForm = document.getElementById('slotDeleteForm');
                deleteForm.action = baseUrl + '/' + slot.id;
                deleteForm.submit();
            }
        };
    });
</script>
@endpush

@endsection
