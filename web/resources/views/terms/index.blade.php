@extends('layouts.app')

@section('title', 'Academic Terms')

@section('content')

<x-page-header eyebrow="Academic Setup" title="Academic Terms"
               subtitle="The current term is used by the routine generator, exams and the navigation bar.">
    <a href="{{ route('terms.create') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> New Term</a>
</x-page-header>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Term</th>
                <th>Year</th>
                <th>Dates</th>
                <th class="text-end">Offerings</th>
                <th class="text-end">Routines</th>
                <th>Status</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($terms as $term)
                <tr>
                    <td class="fw-bold text-white brand-font">{{ $term->name }} <span class="text-dim fw-normal">· {{ $term->term }}</span></td>
                    <td>{{ $term->academic_year }}</td>
                    <td class="text-muted">
                        {{ \Carbon\Carbon::parse($term->start_date)->format('d M Y') }} –
                        {{ \Carbon\Carbon::parse($term->end_date)->format('d M Y') }}
                    </td>
                    <td class="text-end">{{ $term->course_offerings_count }}</td>
                    <td class="text-end">{{ $term->schedule_runs_count }}</td>
                    <td><x-status-badge :status="$term->status === 'ACTIVE' ? 'ACTIVE' : $term->status" /></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            @if($term->status !== 'ACTIVE')
                                <form method="POST" action="{{ route('terms.activate', $term) }}">
                                    @csrf
                                    <button class="btn-glass btn-sm-custom"><i class="bi bi-check2-circle"></i> Make current</button>
                                </form>
                            @endif
                            <a href="{{ route('terms.edit', $term) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                            <x-delete-button :action="route('terms.destroy', $term)"
                                             :confirm="'Delete the term ' . $term->name . '?'" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No academic terms yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
