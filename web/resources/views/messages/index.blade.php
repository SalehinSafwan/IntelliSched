@extends('layouts.app')

@section('title', 'CR/ACR Messages')

@section('content')

@php $categories = config('intellisched.message_categories'); @endphp

@if($teacher)
    <x-page-header eyebrow="Teacher Portal" title="CR / ACR Messages"
                   subtitle="Tell a section's class representatives about rescheduled or cancelled classes and other notices. Only the CR and ACR of the section you choose receive the message.">
        <a href="{{ route('messages.create') }}" class="btn-indigo"><i class="bi bi-send"></i> New Message</a>
    </x-page-header>

    @if($sections->isNotEmpty())
        <div class="filter-pills mb-4">
            <a href="{{ route('messages.index') }}" class="{{ !$sectionFilter ? 'active' : '' }}">All sections</a>
            @foreach($sections as $section)
                <a href="{{ route('messages.index', ['section' => $section->id]) }}" class="{{ $sectionFilter === (int) $section->id ? 'active' : '' }}">{{ $section->label }}</a>
            @endforeach
        </div>
    @endif
@else
    <x-page-header eyebrow="Class Representative" title="Messages from Teachers"
                   :subtitle="$isRep
                       ? 'Notices from your teachers for ' . $student->section?->label . '. Please pass them on to your classmates.'
                       : 'Only a section\'s CR and ACR receive teacher messages.'" />
@endif

@if(!$teacher && !$isRep)
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-person-lock"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">Messages are for class representatives</h4>
        <p class="text-muted mb-0">Your account is not set as the CR or ACR of a section. Contact the administrator if this is wrong.</p>
    </div>
@elseif($messages->isEmpty())
    <div class="glass-card text-center py-5">
        <div class="empty-state-icon"><i class="bi bi-chat-square-text"></i></div>
        <h4 class="brand-font fw-bold text-white mt-4">No messages yet</h4>
        <p class="text-muted mb-0">
            {{ $teacher ? 'Messages you send to a section\'s CR and ACR appear here with their replies.' : 'When a teacher sends your section a notice, it appears here.' }}
        </p>
    </div>
@else
    <div class="table-custom-container mb-4">
        @foreach($messages as $message)
            @php
                $category = $categories[$message->category] ?? $categories['GENERAL'];
                $isUnread = in_array((int) $message->id, $unread, true);
            @endphp
            <a href="{{ route('messages.show', $message) }}" class="message-row {{ $isUnread ? 'is-unread' : '' }}">
                <span class="message-icon tone-{{ $category['tone'] }}"><i class="bi {{ $category['icon'] }}"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <span class="message-subject">{{ $message->subject }}</span>
                        <span class="message-meta">{{ $message->last_activity_at?->diffForHumans() }}</span>
                    </div>
                    <div class="message-meta mt-1">
                        <span class="badge-custom tone-{{ $category['tone'] }}">{{ $category['label'] }}</span>
                        @if($teacher)
                            · To CR/ACR of <span class="text-white">{{ $message->section?->label }}</span>
                        @else
                            · From <span class="text-white">{{ $message->teacher?->display_name }}</span>
                        @endif
                        @if($message->course) · {{ $message->course->course_code }} @endif
                        · <i class="bi bi-chat-dots"></i> {{ $message->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $message->replies_count) }}
                    </div>
                    <div class="text-muted mt-1 text-truncate" style="font-size: 0.82rem;">{{ \Illuminate\Support\Str::limit($message->body, 140) }}</div>
                </div>
            </a>
        @endforeach
    </div>
    {{ $messages->links() }}
@endif

@endsection
