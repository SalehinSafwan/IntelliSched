@extends('layouts.app')

@section('title', $message->subject)

@section('content')

@use('App\Models\ClassMessage')
@use('App\Services\Messaging\ClassMessageService')

@php
    $category = config('intellisched.message_categories')[$message->category] ?? config('intellisched.message_categories')['GENERAL'];
    $status = $message->statusInfo();
    $slotLabel = fn ($slot) => $slot ? ucfirst(strtolower($slot->day)) . " · P{$slot->slot_number} ({$slot->start_time}–{$slot->end_time})" : null;
    $hasOriginal = $message->class_date || $message->timeSlot;
    $hasNew = $message->new_date || $message->newTimeSlot;
    $teacherName = $message->teacher?->display_name ?? 'the teacher';

    // Notice id => version number (1, 2, 3 …).
    $versions = $notices->pluck('id')->values()->flip()->map(fn ($index) => $index + 1);
    $nextVersion = $notices->count() + 1;

    // Progress through the session, for the stepper.
    $stage = match ($message->status) {
        ClassMessage::PENDING_REVIEW => 3,
        ClassMessage::COMPLETED => 5,
        default => 2,
    };
    $steps = [
        1 => ['Teacher\'s message', 'bi-envelope-paper'],
        2 => [$message->status === ClassMessage::CHANGES_REQUESTED ? 'Corrected notice' : 'CR/ACR notice', 'bi-megaphone'],
        3 => ['Teacher review', 'bi-eye'],
        4 => ['Completed', 'bi-check2-circle'],
    ];
@endphp

<x-page-header :back="route('messages.index')" back-label="Messages" :eyebrow="$category['label']" :title="$message->subject">
    <span class="badge-custom tone-{{ $status['tone'] }} align-self-center px-3 py-2">
        <i class="bi {{ $status['icon'] }}"></i> {{ $status['label'] }}
    </span>
    @if($isSender)
        <x-delete-button :action="route('messages.destroy', $message)" label="Delete" :small="false"
                         confirm="Delete this message, its notices and replies for everyone?" />
    @endif
</x-page-header>

{{-- Where the session stands --}}
<div class="glass-card mb-4 py-3">
    <ol class="session-steps" aria-label="Session progress">
        @foreach($steps as $number => [$label, $icon])
            @php
                $state = $number < $stage ? 'done' : ($number === $stage ? 'current' : 'todo');
                if ($number === 2 && $message->status === ClassMessage::CHANGES_REQUESTED) { $state = 'retry'; }
            @endphp
            <li class="is-{{ $state }}" @if($state === 'current' || $state === 'retry') aria-current="step" @endif>
                <span class="step-dot"><i class="bi {{ $state === 'done' ? 'bi-check-lg' : $icon }}"></i></span>
                <span class="step-label">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">

        {{-- The teacher's original message --}}
        <div class="glass-card mb-4">
            <div class="d-flex gap-3 align-items-start mb-3">
                <span class="message-icon tone-{{ $category['tone'] }}"><i class="bi {{ $category['icon'] }}"></i></span>
                <div>
                    <div class="text-white fw-bold">{{ $teacherName }}</div>
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

        {{-- Thread: notices, reviews and comments in order --}}
        <div class="section-heading">
            <span class="section-chip"><i class="bi bi-chat-dots"></i></span>
            <h5 class="brand-font fw-bold text-white mb-0">Notice &amp; Replies</h5>
        </div>

        @forelse($message->replies as $reply)
            @php
                $mine = (int) $reply->user_id === (int) auth()->id();
                $author = $reply->user?->name ?? 'Deleted account';
            @endphp
            <div class="mb-3" @if($loop->last) id="latest" @endif>
                @if($reply->isNotice())
                    @php
                        $verdict = $reviews->get((int) $reply->id);
                        $noticeState = $verdict ? strtolower($verdict->kind) : 'pending';
                    @endphp
                    <article class="notice-card is-{{ $noticeState }}">
                        <header class="notice-head">
                            <span class="notice-title"><i class="bi bi-megaphone-fill"></i> Formal notice · v{{ $versions[$reply->id] }}</span>
                            @if($noticeState === 'approved')
                                <span class="badge-custom tone-success"><i class="bi bi-patch-check-fill"></i> Approved</span>
                            @elseif($noticeState === 'rejected')
                                <span class="badge-custom tone-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                            @else
                                <span class="badge-custom tone-warning"><i class="bi bi-hourglass-split"></i> Pending review</span>
                            @endif
                            <span class="message-meta ms-auto">
                                {{ $author }}
                                @if($reply->user?->class_rep) <span class="badge-custom badge-gold">{{ $reply->user->class_rep }}</span> @endif
                                · {{ $reply->created_at?->format('d M, h:i A') }}
                            </span>
                        </header>
                        <div class="notice-body">{{ ClassMessageService::formatNotice($reply->body) }}</div>
                    </article>
                @elseif($reply->isReview())
                    @php $approved = $reply->kind === \App\Models\ClassMessageReply::APPROVED; @endphp
                    <div class="review-event {{ $approved ? 'is-approved' : 'is-rejected' }}">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <i class="bi {{ $approved ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }}"></i>
                            <span>
                                <strong>{{ $author }}</strong>
                                {{ $approved ? 'approved' : 'rejected' }} notice v{{ $versions[$reply->notice_reply_id] ?? '?' }}{{ $approved ? ' — session completed.' : ($reply->correction ? ' with a correction:' : '.') }}
                            </span>
                            <span class="message-meta ms-auto">{{ $reply->created_at?->format('d M, h:i A') }}</span>
                        </div>
                        @if($reply->correction)
                            <blockquote class="review-correction">{{ $reply->correction }}</blockquote>
                        @endif
                    </div>
                @else
                    <div class="message-meta mb-1">
                        <span class="text-white fw-semibold">{{ $author }}</span>
                        @if($reply->user?->class_rep) <span class="badge-custom badge-gold">{{ $reply->user->class_rep }}</span> @endif
                        @if($mine) <span class="badge-custom badge-role">You</span> @endif
                        · {{ $reply->created_at?->format('d M, h:i A') }}
                    </div>
                    <div class="message-bubble {{ $mine ? 'is-mine' : '' }}">{{ $reply->body }}</div>
                @endif
            </div>
        @empty
            <p class="text-muted" style="font-size: 0.86rem;">
                {{ $isSender ? 'The CR/ACR has not posted a notice yet.' : 'No notice yet. Write the notice for your classmates below.' }}
            </p>
        @endforelse

        {{-- What this user can do next --}}
        @if($message->isCompleted())
            <div class="session-closed mt-3">
                <i class="bi bi-lock-fill"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-white">Session completed</div>
                    <div class="message-meta">
                        {{ $teacherName }} approved the notice{{ $message->completed_at ? ' on ' . $message->completed_at->format('d M Y, h:i A') : '' }}.
                        No more messages can be sent in this session.
                    </div>
                </div>
                @if($approvedNotice)
                    <button type="button" class="btn-glass btn-sm-custom" data-copy-target="#approvedNoticeText">
                        <i class="bi bi-clipboard"></i> <span>Copy notice</span>
                    </button>
                    @unless($isSender)
                        <button type="button" class="btn-indigo btn-whatsapp btn-sm-custom" data-whatsapp-target="#approvedNoticeText">
                            <i class="bi bi-whatsapp"></i> <span>Share on WhatsApp</span>
                        </button>
                    @endunless
                    <textarea id="approvedNoticeText" class="visually-hidden" readonly tabindex="-1" aria-hidden="true">{{ $approvedNotice->body }}</textarea>
                @endif
            </div>
            @if($approvedNotice && !$isSender)
                <p class="form-hint mt-2 mb-0"><i class="bi bi-whatsapp"></i> The notice is approved. Copy it and post it in your class group.</p>
            @endif
        @endif

        @if($canReview && $latestNotice)
            <form method="POST" action="{{ route('messages.review', $message) }}" class="glass-card review-panel mt-3" id="reviewForm">
                @csrf
                <input type="hidden" name="notice_id" value="{{ $latestNotice->id }}">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-clipboard-check text-gold fs-5"></i>
                    <h6 class="text-white fw-bold mb-0">Review notice v{{ $versions[$latestNotice->id] }}</h6>
                </div>
                <p class="message-meta mb-3">Approving completes and closes this session. Rejecting sends it back to the CR/ACR for a corrected notice.</p>

                <div class="review-correction-field" id="correctionField" @unless(old('decision') === 'CORRECT' || $errors->has('correction')) hidden @endunless>
                    <label class="form-label-custom" for="correction">What should be corrected? *</label>
                    <textarea id="correction" name="correction" rows="3" maxlength="2000"
                              class="form-control form-control-custom @error('correction') is-invalid @enderror"
                              placeholder="e.g. The class moves to Room 402, not 304. Please also mention bringing calculators.">{{ old('correction') }}</textarea>
                    @if($errors->has('correction'))<div class="invalid-feedback-custom">{{ $errors->first('correction') }}</div>@endif
                    <div class="d-flex gap-2 justify-content-end mt-3">
                        <button type="button" class="btn-glass btn-sm-custom" data-correction-cancel>Cancel</button>
                        <button type="submit" name="decision" value="CORRECT" class="btn-glass btn-danger-glass btn-sm-custom">
                            <i class="bi bi-send"></i> Send correction
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end" id="reviewButtons" @if(old('decision') === 'CORRECT' || $errors->has('correction')) hidden @endif>
                    <button type="submit" name="decision" value="REJECT" class="btn-glass btn-danger-glass"
                            data-confirm="Reject this notice without a correction?">
                        <i class="bi bi-x-lg"></i> Reject
                    </button>
                    <button type="button" class="btn-glass" data-correction-open>
                        <i class="bi bi-pencil"></i> Reject with correction
                    </button>
                    <button type="submit" name="decision" value="APPROVE" class="btn-indigo btn-success-glow"
                            data-confirm="Approve this notice? The session will be completed and closed.">
                        <i class="bi bi-check2-circle"></i> Approve
                    </button>
                </div>
            </form>
        @elseif($isSender && $message->awaitsNotice())
            <div class="info-panel mt-3 d-flex gap-2 align-items-center">
                <i class="bi bi-hourglass-split text-info"></i>
                <span class="message-meta">
                    Waiting for the CR/ACR to post {{ $message->status === ClassMessage::CHANGES_REQUESTED ? 'a corrected' : 'the' }} notice. You will review it here.
                </span>
            </div>
        @endif

        @if($canSubmitNotice)
            <form method="POST" action="{{ route('messages.notice', $message) }}" class="glass-card mt-3" id="noticeForm">
                @csrf
                @if($message->status === ClassMessage::CHANGES_REQUESTED && $lastReview)
                    <div class="review-event is-rejected mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span><strong>{{ $teacherName }}</strong> asked for a corrected notice{{ $lastReview->correction ? ':' : '.' }}</span>
                        </div>
                        @if($lastReview->correction)
                            <blockquote class="review-correction">{{ $lastReview->correction }}</blockquote>
                        @endif
                    </div>
                @endif

                <label class="form-label-custom" for="notice">
                    {{ $message->status === ClassMessage::CHANGES_REQUESTED ? 'Corrected notice (v' . $nextVersion . ')' : 'Formal notice for your classmates' }}
                </label>
                <p class="form-hint mt-0 mb-2">
                    Write it the way you would post it in the class WhatsApp group. Wrap words in *stars* for <strong>bold</strong> and _underscores_ for <em>italics</em>.
                    {{ $teacherName }} approves it before you share it.
                </p>
                {{-- Typing savers: formatting, common phrases, reset. --}}
                <div class="notice-tools" role="toolbar" aria-label="Notice shortcuts">
                    <button type="button" class="chip-btn" data-wrap="*" title="Bold the selected text"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="chip-btn" data-wrap="_" title="Italicise the selected text"><i class="bi bi-type-italic"></i></button>
                    <span class="tool-sep" aria-hidden="true"></span>
                    @foreach(config('intellisched.notice_snippets', []) as $snippet)
                        <button type="button" class="chip-btn" data-snippet="{{ $snippet }}" title="Insert this line">+ {{ $snippet }}</button>
                    @endforeach
                    <span class="tool-sep" aria-hidden="true"></span>
                    <button type="button" class="chip-btn" data-reset-template title="Start again from the auto-filled notice"><i class="bi bi-arrow-counterclockwise"></i> Reset to template</button>
                </div>
                <textarea id="notice" name="notice" rows="12" maxlength="2000" required data-notice-input
                          class="form-control form-control-custom notice-input @error('notice') is-invalid @enderror">{{ old('notice', $noticeDraft) }}</textarea>
                <textarea id="noticeTemplate" class="d-none" aria-hidden="true" tabindex="-1" readonly>{{ $noticeTemplate }}</textarea>
                <div class="form-hint d-flex justify-content-between"><span>Tip: select words, then tap <strong>B</strong> to make them bold.</span><span data-notice-count></span></div>
                @if($errors->has('notice'))<div class="invalid-feedback-custom">{{ $errors->first('notice') }}</div>@endif

                <div class="form-section-title mt-3 mb-2">Preview</div>
                <div class="notice-card is-preview"><div class="notice-body" data-notice-preview></div></div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn-indigo"><i class="bi bi-send-check"></i> Send for Approval</button>
                </div>
            </form>
        @elseif(!$isSender && $message->awaitsReview())
            <div class="info-panel mt-3 d-flex gap-2 align-items-center">
                <i class="bi bi-hourglass-split text-warning"></i>
                <span class="message-meta">Your notice was sent. Waiting for {{ $teacherName }} to approve or reject it.</span>
            </div>
        @endif

        @if($canComment)
            <details class="comment-box mt-3" @if($errors->has('body')) open @endif>
                <summary><i class="bi bi-chat-left-dots"></i> {{ $isSender ? 'Add a comment for the CR/ACR' : 'Ask ' . $teacherName . ' a question' }} <span class="message-meta">(not part of the notice)</span></summary>
                <form method="POST" action="{{ route('messages.reply', $message) }}" class="mt-3">
                    @csrf
                    <textarea id="reply_body" name="body" rows="3" maxlength="2000" required class="form-control form-control-custom @error('body') is-invalid @enderror"
                              placeholder="{{ $isSender ? 'e.g. Please also remind them to bring lab coats.' : 'e.g. Sir, which room should we go to?' }}">{{ old('body') }}</textarea>
                    @if($errors->has('body'))<div class="invalid-feedback-custom">{{ $errors->first('body') }}</div>@endif
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn-glass"><i class="bi bi-reply"></i> Send Comment</button>
                    </div>
                </form>
            </details>
        @endif
    </div>

    <div class="col-12 col-xl-4">
        <div class="glass-card mb-4">
            <div class="form-section-title mb-2">Session</div>
            <dl class="session-facts mb-0">
                <dt>Status</dt>
                <dd><span class="badge-custom tone-{{ $status['tone'] }}"><i class="bi {{ $status['icon'] }}"></i> {{ $status['label'] }}</span></dd>
                <dt>State</dt>
                <dd class="{{ $message->isCompleted() ? 'text-emerald' : 'text-white' }}">{{ $message->isCompleted() ? 'Completed · closed' : 'Not completed · open' }}</dd>
                <dt>Notice versions</dt>
                <dd class="text-white">{{ $notices->count() }}</dd>
                <dt>Started</dt>
                <dd class="text-white">{{ $message->created_at?->format('d M Y, h:i A') }}</dd>
                @if($message->completed_at)
                    <dt>Completed</dt>
                    <dd class="text-white">{{ $message->completed_at->format('d M Y, h:i A') }}</dd>
                @endif
            </dl>
        </div>

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
            <p class="form-hint mt-3 mb-0"><i class="bi bi-shield-lock"></i> Visible only to {{ $teacherName }} and the CR/ACR of {{ $message->section?->label }}.</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // Same WhatsApp markup as ClassMessageService::formatNotice().
    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }
    function formatNotice(text) {
        return escapeHtml(text)
            .replace(/(^|[^\w*])\*(?=\S)([^*\n]+?)(?<=\S)\*(?![\w*])/g, '$1<strong>$2</strong>')
            .replace(/(^|[^\w_])_(?=\S)([^_\n]+?)(?<=\S)_(?![\w_])/g, '$1<em>$2</em>');
    }

    var input = document.querySelector('[data-notice-input]');
    var preview = document.querySelector('[data-notice-preview]');
    if (input && preview) {
        var counter = document.querySelector('[data-notice-count]');
        var render = function () {
            preview.innerHTML = formatNotice(input.value) || '<span class="text-muted">Nothing to preview yet.</span>';
            if (counter) counter.textContent = input.value.length + ' / 2000';
        };
        input.addEventListener('input', render);
        render();

        // Replace the selection and keep the cursor after the insert.
        var insert = function (text, selectFrom, selectTo) {
            var start = input.selectionStart, end = input.selectionEnd;
            input.setRangeText(text, start, end, 'end');
            if (selectFrom !== undefined) input.setSelectionRange(start + selectFrom, start + selectTo);
            input.focus();
            render();
        };

        document.querySelectorAll('[data-wrap]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mark = btn.dataset.wrap;
                var selected = input.value.substring(input.selectionStart, input.selectionEnd);
                if (selected) {
                    insert(mark + selected + mark);
                } else {
                    insert(mark + 'text' + mark, 1, 5);
                }
            });
        });

        document.querySelectorAll('[data-snippet]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var before = input.value.substring(0, input.selectionStart);
                var prefix = before === '' || before.endsWith('\n') ? '' : '\n';
                insert(prefix + btn.dataset.snippet + '\n');
            });
        });

        document.querySelectorAll('[data-reset-template]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (input.value.trim() && !window.confirm('Replace what you wrote with the auto-filled notice?')) return;
                input.value = document.getElementById('noticeTemplate').value;
                input.focus();
                render();
            });
        });
    }

    // Reject with correction: reveal the correction box.
    var field = document.getElementById('correctionField');
    var buttons = document.getElementById('reviewButtons');
    document.querySelectorAll('[data-correction-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            field.hidden = false; buttons.hidden = true;
            document.getElementById('correction').focus();
        });
    });
    document.querySelectorAll('[data-correction-cancel]').forEach(function (btn) {
        btn.addEventListener('click', function () { field.hidden = true; buttons.hidden = false; });
    });

    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>
@endpush
