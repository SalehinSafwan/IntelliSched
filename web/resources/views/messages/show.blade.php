@extends('layouts.app')

@section('title', $message->subject)

@section('content')

@php
    $category = config('intellisched.message_categories')[$message->category] ?? config('intellisched.message_categories')['GENERAL'];
    $slotLabel = fn ($slot) => $slot ? ucfirst(strtolower($slot->day)) . " · P{$slot->slot_number} ({$slot->start_time}–{$slot->end_time})" : null;
    $hasOriginal = $message->class_date || $message->timeSlot;
    $hasNew = $message->new_date || $message->newTimeSlot;
@endphp

<x-page-header :back="route('messages.index')" back-label="Messages" :eyebrow="$category['label']" :title="$message->subject">
    @if($isSender)
        <x-delete-button :action="route('messages.destroy', $message)" label="Delete" :small="false"
                         confirm="Delete this message and its replies for everyone?" />
    @endif
</x-page-header>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="glass-card mb-4">
            <div class="d-flex gap-3 align-items-start mb-3">
                <span class="message-icon tone-{{ $category['tone'] }}"><i class="bi {{ $category['icon'] }}"></i></span>
                <div>
                    <div class="text-white fw-bold">{{ $message->teacher?->display_name }}</div>
                    <div class="message-meta">
                        To the CR/ACR of {{ $message->section?->label }}
                        @if($message->course) · {{ $message->course->course_code }} — {{ $message->course->course_name }} @endif
                        · {{ $message->created_at?->format('d M Y, h:i A') }}
                    </div>
                </div>
            </div>

            @if($hasOriginal || $hasNew)
                <div class="info-panel mb-3">
                    <div class="class-change">
                        <div>
                            <div class="form-section-title mb-1">{{ $message->category === 'EXTRA_CLASS' ? 'Extra class' : 'Class' }}</div>
                            @if($hasOriginal)
                                <div class="text-white fw-semibold {{ in_array($message->category, ['CANCEL', 'RESCHEDULE'], true) ? 'text-decoration-line-through' : '' }}">
                                    {{ $message->class_date?->format('D, d M Y') }}
                                </div>
                                <div class="text-muted" style="font-size: 0.82rem;">{{ $slotLabel($message->timeSlot) }}</div>
                            @else
                                <div class="text-muted">—</div>
                            @endif
                        </div>
                        @if($hasNew)
                            <i class="bi bi-arrow-right arrow"></i>
                            <div>
                                <div class="form-section-title mb-1">{{ $message->category === 'EXTRA_CLASS' ? 'Takes place' : 'Moved to' }}</div>
                                <div class="text-emerald fw-semibold">{{ $message->new_date?->format('D, d M Y') }}</div>
                                <div class="text-muted" style="font-size: 0.82rem;">{{ $slotLabel($message->newTimeSlot) }}</div>
                            </div>
                        @elseif($message->category === 'CANCEL')
                            <i class="bi bi-x-lg arrow" style="color: #fca5a5;"></i>
                            <div><span class="badge-custom tone-danger">Cancelled</span></div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="message-bubble">{{ $message->body }}</div>
        </div>

        <div class="section-heading"><span class="section-chip"><i class="bi bi-chat-dots"></i></span><h5 class="brand-font fw-bold text-white mb-0">Replies</h5></div>

        @forelse($message->replies as $reply)
            @php $mine = (int) $reply->user_id === (int) auth()->id(); @endphp
            <div class="mb-3" @if($loop->last) id="latest" @endif>
                <div class="message-meta mb-1">
                    <span class="text-white fw-semibold">{{ $reply->user?->name ?? 'Deleted account' }}</span>
                    @if($reply->user?->class_rep) <span class="badge-custom badge-gold">{{ $reply->user->class_rep }}</span> @endif
                    @if($mine) <span class="badge-custom badge-role">You</span> @endif
                    · {{ $reply->created_at?->format('d M, h:i A') }}
                </div>
                <div class="message-bubble {{ $mine ? 'is-mine' : '' }}">{{ $reply->body }}</div>
            </div>
        @empty
            <p class="text-muted" style="font-size: 0.86rem;">No replies yet.</p>
        @endforelse

        <form method="POST" action="{{ route('messages.reply', $message) }}" class="glass-card mt-3">
            @csrf
            <label class="form-label-custom" for="reply_body">{{ $isSender ? 'Reply to the CR/ACR' : 'Reply to ' . $message->teacher?->display_name }}</label>
            <textarea id="reply_body" name="body" rows="3" maxlength="2000" required class="form-control form-control-custom @error('body') is-invalid @enderror"
                      placeholder="{{ $isSender ? 'Add a follow-up…' : 'e.g. Noted, I have informed the class.' }}">{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback-custom">{{ $message }}</div>@enderror
            <div class="d-flex justify-content-end mt-3">
                <button class="btn-indigo"><i class="bi bi-reply"></i> Send Reply</button>
            </div>
        </form>
    </div>

    <div class="col-12 col-xl-4">
        <div class="glass-card">
            <div class="form-section-title mb-2">Recipients</div>
            @forelse($recipients as $rep)
                @php $read = $readers->firstWhere('user_id', $rep->id); @endphp
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                    <div>
                        <div class="text-white fw-semibold" style="font-size: 0.88rem;">{{ $rep->name }}</div>
                        <div class="message-meta">{{ config('intellisched.class_reps')[$rep->class_rep] ?? $rep->class_rep }}</div>
                    </div>
                    @if($read)
                        <span class="badge-custom badge-active" title="{{ $read->read_at?->format('d M Y, h:i A') }}"><i class="bi bi-check2-all"></i> Seen</span>
                    @else
                        <span class="badge-custom badge-archived">Not opened</span>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size: 0.86rem;">This section currently has no active CR or ACR.</p>
            @endforelse
            <p class="form-hint mt-3 mb-0"><i class="bi bi-shield-lock"></i> Visible only to {{ $message->teacher?->display_name }} and the CR/ACR of {{ $message->section?->label }}.</p>
        </div>
    </div>
</div>

@endsection
