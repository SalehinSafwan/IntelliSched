@extends('layouts.app')

@section('title', 'Notice Board')

@section('content')

@use('App\Services\Messaging\ClassMessageService')

@php $categories = config('intellisched.message_categories'); @endphp

<x-page-header :eyebrow="'Class Representative · ' . ($rep->section?->label ?? '')" title="Notice Board"
               subtitle="Approved notices, ready for your class WhatsApp group. Share in one tap, then mark them as posted.">
    <a href="{{ route('class.routine') }}" class="btn-glass"><i class="bi bi-calendar-day"></i> My Routine</a>
    <a href="{{ route('messages.index') }}" class="btn-glass"><i class="bi bi-chat-left-text"></i> Teacher Messages</a>
</x-page-header>

{{-- Quick counts --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Ready to post', $board['ready']->count(), 'bi-megaphone-fill', 'stat-icon-success', '#ready'],
        ['Need your notice', $board['todo']->count(), 'bi-pencil-square', 'stat-icon-warning', '#todo'],
        ['Waiting for approval', $board['waiting']->count(), 'bi-hourglass-split', 'stat-icon-primary', '#waiting'],
    ] as [$label, $count, $icon, $tone, $anchor])
        <div class="col-12 col-md-4">
            <a href="{{ $anchor }}" class="text-decoration-none">
                <div class="glass-card card-hoverable stat-card">
                    <div><div class="stat-lbl">{{ $label }}</div><div class="stat-val text-white">{{ $count }}</div></div>
                    <div class="stat-icon-wrapper {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- Ready to post --}}
<section id="ready" class="mb-5">
    <div class="section-heading">
        <span class="section-chip"><i class="bi bi-megaphone-fill"></i></span>
        <h5 class="brand-font fw-bold text-white mb-0">Ready to post</h5>
    </div>

    @forelse($board['ready'] as $item)
        @php
            $message = $item['message'];
            $category = $categories[$message->category] ?? $categories['GENERAL'];
            $textId = 'notice-' . $message->id;
        @endphp
        <article class="notice-card is-approved mb-3">
            <header class="notice-head">
                <span class="badge-custom tone-{{ $category['tone'] }}"><i class="bi {{ $category['icon'] }}"></i> {{ $category['label'] }}</span>
                <span class="notice-title">{{ $message->subject }}</span>
                <span class="message-meta ms-auto">
                    Approved by {{ $message->teacher?->display_name }} · {{ $message->completed_at?->diffForHumans() }}
                </span>
            </header>
            <div class="notice-body">{{ ClassMessageService::formatNotice($item['text']) }}</div>
            <textarea id="{{ $textId }}" class="d-none" readonly aria-hidden="true" tabindex="-1">{{ $item['text'] }}</textarea>
            <footer class="notice-actions">
                <a href="{{ route('messages.show', $message) }}" class="btn-glass btn-sm-custom"><i class="bi bi-box-arrow-up-right"></i> Open session</a>
                <span class="flex-grow-1"></span>
                <button type="button" class="btn-glass btn-sm-custom" data-copy-target="#{{ $textId }}"><i class="bi bi-clipboard"></i> <span>Copy</span></button>
                <a href="{{ $item['whatsapp'] }}" target="_blank" rel="noopener" class="btn-indigo btn-whatsapp btn-sm-custom"><i class="bi bi-whatsapp"></i> <span>Share on WhatsApp</span></a>
                <form method="POST" action="{{ route('class.notices.posted', $message) }}">
                    @csrf
                    <button class="btn-indigo btn-success-glow btn-sm-custom"><i class="bi bi-check2-all"></i> Mark as posted</button>
                </form>
            </footer>
        </article>
    @empty
        <div class="glass-card text-center py-4">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <p class="text-muted mt-3 mb-0">Nothing to post right now. Approved notices appear here automatically.</p>
        </div>
    @endforelse
</section>

<div class="row g-4 mb-5">
    {{-- Waiting on the CR --}}
    <section id="todo" class="col-12 col-lg-6">
        <div class="glass-card h-100">
            <div class="form-section-title mb-2"><i class="bi bi-pencil-square text-warning"></i> Need your notice</div>
            @forelse($board['todo'] as $message)
                @php $status = $message->statusInfo(); @endphp
                <a href="{{ route('messages.show', $message) }}" class="board-row">
                    <span class="flex-grow-1 min-w-0">
                        <span class="d-block text-white fw-semibold text-truncate">{{ $message->subject }}</span>
                        <span class="message-meta">{{ $message->teacher?->display_name }} · {{ $message->last_activity_at?->diffForHumans() }}</span>
                    </span>
                    <span class="badge-custom tone-{{ $status['tone'] }}">{{ $status['label'] }}</span>
                    <span class="btn-indigo btn-sm-custom">{{ $message->status === \App\Models\ClassMessage::CHANGES_REQUESTED ? 'Fix notice' : 'Write notice' }}</span>
                </a>
            @empty
                <p class="text-muted mb-0" style="font-size: 0.86rem;">You're all caught up.</p>
            @endforelse
        </div>
    </section>

    {{-- Waiting on the teacher --}}
    <section id="waiting" class="col-12 col-lg-6">
        <div class="glass-card h-100">
            <div class="form-section-title mb-2"><i class="bi bi-hourglass-split text-info"></i> Waiting for approval</div>
            @forelse($board['waiting'] as $message)
                <a href="{{ route('messages.show', $message) }}" class="board-row">
                    <span class="flex-grow-1 min-w-0">
                        <span class="d-block text-white fw-semibold text-truncate">{{ $message->subject }}</span>
                        <span class="message-meta">Sent to {{ $message->teacher?->display_name }} · v{{ $message->notices->count() }}</span>
                    </span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            @empty
                <p class="text-muted mb-0" style="font-size: 0.86rem;">No notices are waiting for a teacher.</p>
            @endforelse
        </div>
    </section>
</div>

{{-- History --}}
<section>
    <details class="comment-box" @if($board['ready']->isEmpty() && $board['posted']->isNotEmpty()) open @endif>
        <summary><i class="bi bi-clock-history"></i> Posted notices <span class="message-meta">({{ $board['posted']->count() }})</span></summary>
        <div class="mt-3">
            @forelse($board['posted'] as $item)
                @php $message = $item['message']; $textId = 'posted-' . $message->id; @endphp
                <div class="board-row">
                    <span class="flex-grow-1 min-w-0">
                        <a href="{{ route('messages.show', $message) }}" class="d-block text-white fw-semibold text-truncate text-decoration-none">{{ $message->subject }}</a>
                        <span class="message-meta"><i class="bi bi-check2-all text-emerald"></i> Posted {{ $message->posted_at?->format('d M, h:i A') }}</span>
                    </span>
                    @if($item['text'])
                        <textarea id="{{ $textId }}" class="d-none" readonly aria-hidden="true" tabindex="-1">{{ $item['text'] }}</textarea>
                        <button type="button" class="btn-glass btn-sm-custom" data-copy-target="#{{ $textId }}" title="Copy again"><i class="bi bi-clipboard"></i> <span>Copy</span></button>
                    @endif
                    <form method="POST" action="{{ route('class.notices.posted', $message) }}">
                        @csrf
                        <input type="hidden" name="undo" value="1">
                        <button class="btn-glass btn-sm-custom" title="Move back to Ready to post"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </form>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size: 0.86rem;">Notices you mark as posted are kept here.</p>
            @endforelse
        </div>
    </details>
</section>

@endsection
