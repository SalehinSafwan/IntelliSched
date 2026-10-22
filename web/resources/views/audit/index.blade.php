@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')

<x-page-header eyebrow="Administration" title="Audit Trail"
               subtitle="Every change made through the management pages, newest first." />

@if($entities->isNotEmpty())
    <div class="filter-pills mb-4">
        <a href="{{ route('audit.index') }}" class="{{ !$entity ? 'active' : '' }}">All</a>
        @foreach($entities as $option)
            <a href="{{ route('audit.index', ['entity' => $option]) }}" class="{{ $entity === $option ? 'active' : '' }}">{{ $option }}</a>
        @endforeach
    </div>
@endif

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>IP</th><th class="text-end"></th></tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-muted" style="white-space: nowrap;">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                    <td class="fw-semibold text-white">{{ $log->user?->name ?? 'System' }}</td>
                    <td><span class="badge-custom badge-role">{{ str_replace('_', ' ', $log->action) }}</span></td>
                    <td class="text-muted">{{ $log->entity_type ? $log->entity_type . ($log->entity_id ? ' #' . $log->entity_id : '') : '—' }}</td>
                    <td class="text-dim">{{ $log->ip_address }}</td>
                    <td class="text-end">
                        @if($log->old_data || $log->new_data)
                            <button type="button" class="btn-glass btn-sm-custom" data-bs-toggle="collapse" data-bs-target="#log{{ $log->id }}">
                                <i class="bi bi-code-slash"></i> Details
                            </button>
                        @endif
                    </td>
                </tr>
                @if($log->old_data || $log->new_data)
                    <tr class="collapse" id="log{{ $log->id }}">
                        <td colspan="6">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="form-label-custom">Before</div>
                                    <pre class="info-panel mb-0" style="white-space: pre-wrap; font-size: 0.75rem; color: #fca5a5;">{{ $log->old_data ? json_encode(json_decode($log->old_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-label-custom">After</div>
                                    <pre class="info-panel mb-0" style="white-space: pre-wrap; font-size: 0.75rem; color: #6ee7b7;">{{ $log->new_data ? json_encode(json_decode($log->new_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No actions recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $logs->links('pagination::bootstrap-5') }}

@endsection
