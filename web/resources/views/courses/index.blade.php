@extends('layouts.app')

@section('title', 'Courses')

@section('content')

<x-page-header eyebrow="Academic Setup" title="Course Catalogue"
               subtitle="Theory courses meet 3 times a week; lab courses meet in 3-period lab sessions.">
    <x-export-pdf-button :href="route('export.courses')" />
    <a href="{{ route('courses.create') }}" class="btn-indigo"><i class="bi bi-plus-lg"></i> New Course</a>
</x-page-header>

<div class="filter-pills mb-4">
    <a href="{{ route('courses.index') }}" class="{{ !$type ? 'active' : '' }}">All · {{ $counts['all'] }}</a>
    <a href="{{ route('courses.index', ['type' => 'THEORY']) }}" class="{{ $type === 'THEORY' ? 'active' : '' }}">Theory · {{ $counts['THEORY'] }}</a>
    <a href="{{ route('courses.index', ['type' => 'LAB']) }}" class="{{ $type === 'LAB' ? 'active' : '' }}">Lab · {{ $counts['LAB'] }}</a>
</div>

<div class="table-custom-container mb-4">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Code</th>
                <th>Course Title</th>
                <th>Department</th>
                <th>Type</th>
                <th class="text-end">Credits</th>
                <th>Weekly Load</th>
                <th class="text-end">Offerings</th>
                <th>Status</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($courses as $course)
                <tr>
                    <td><a href="{{ route('courses.show', $course) }}" class="fw-bold text-white brand-font text-decoration-none">{{ $course->course_code }}</a></td>
                    <td class="text-white">{{ $course->course_name }}</td>
                    <td class="text-muted">{{ $course->department }}</td>
                    <td><span class="badge-custom {{ $course->course_type === 'LAB' ? 'badge-lab' : 'badge-theory' }}">{{ $course->course_type }}</span></td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $course->credit_hours, 2), '0'), '.') }}</td>
                    <td class="text-muted">
                        @if($course->course_type === 'LAB')
                            {{ $course->weekly_lab_classes }} × 3-period lab
                        @else
                            {{ $course->weekly_theory_classes }} theory classes
                        @endif
                    </td>
                    <td class="text-end">{{ $course->offerings_count }}</td>
                    <td><x-status-badge :status="$course->status" /></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a href="{{ route('courses.edit', $course) }}" class="btn-glass btn-sm-custom"><i class="bi bi-pencil"></i> Edit</a>
                            <x-delete-button :action="route('courses.destroy', $course)"
                                             :confirm="'Delete ' . $course->course_code . '? Teacher expertise and room eligibility for it are removed too.'" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No courses yet. Add the first one.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
