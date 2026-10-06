@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

@php
    $role = request('preview_role', session('current_role', auth()->user()->role ?? 'ADMIN'));
    $role = strtoupper($role);
@endphp

<!-- Welcome Banner -->
<div class="glass-card mb-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.18), rgba(139, 92, 246, 0.12)); border-color: rgba(139, 92, 246, 0.3);">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h2 class="brand-font fw-bold mb-1 text-white">Welcome back, {{ auth()->user()->name ?? 'Administrator' }} 👋</h2>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                Academic Term: <span class="text-indigo fw-semibold">Fall 2026</span> · Computer Science & Engineering Department
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(in_array($role, ['ADMIN', 'COORDINATOR']))
                <a href="{{ route('schedules.create') }}" class="btn-indigo">
                    <i class="bi bi-play-circle-fill"></i> Generate Routine
                </a>
            @else
                <a href="{{ route('schedules.index') }}" class="btn-indigo">
                    <i class="bi bi-calendar3"></i> View Schedule
                </a>
            @endif
        </div>
    </div>
</div>

@if(in_array($role, ['ADMIN', 'COORDINATOR']))
    <!-- Admin & Coordinator Dashboard Stats -->
    <div class="row g-4 mb-4">
        <!-- Teachers -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="glass-card card-hoverable stat-card">
                <div>
                    <div class="stat-lbl">Active Teachers</div>
                    <div class="stat-val text-white">24</div>
                    <div class="text-success" style="font-size: 0.75rem; margin-top: 4px;">
                        <i class="bi bi-arrow-up-right me-1"></i>100% Availability set
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-primary">
                    <i class="bi bi-person-video3"></i>
                </div>
            </div>
        </div>

        <!-- Courses -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="glass-card card-hoverable stat-card">
                <div>
                    <div class="stat-lbl">Active Courses</div>
                    <div class="stat-val text-white">32</div>
                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                        20 Theory · 12 Lab
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-accent">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>

        <!-- Rooms -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="glass-card card-hoverable stat-card">
                <div>
                    <div class="stat-lbl">Classrooms & Labs</div>
                    <div class="stat-val text-white">18</div>
                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                        12 Theory · 6 Specialized Labs
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-success">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>

        <!-- Batches -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="glass-card card-hoverable stat-card">
                <div>
                    <div class="stat-lbl">Active Batches</div>
                    <div class="stat-val text-white">8</div>
                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                        16 Sections (A & B)
                    </div>
                </div>
                <div class="stat-icon-wrapper stat-icon-warning">
                    <i class="bi bi-collection-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduling Engine Status & Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="glass-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="brand-font fw-bold text-white mb-1">Scheduling Engine Status</h4>
                        <p class="text-muted mb-0" style="font-size: 0.85rem;">OR-Tools CP-SAT Solver Readiness</p>
                    </div>
                    <span class="badge-custom badge-active">
                        <i class="bi bi-check-circle-fill"></i> Solver Ready
                    </span>
                </div>

                <div class="p-4 rounded-4 mb-4" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-6">
                            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">CURRENT ROUTINE VERSION</div>
                            <div class="h5 fw-bold text-white mb-1">Routine Run #5 (Fall 2026)</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Generated Oct 06, 2026 · 08:32 PM</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">OPTIMIZATION SCORE</div>
                            <div class="h3 fw-bold text-success mb-0">91.4%</div>
                            <div class="text-muted" style="font-size: 0.75rem;">High Suitability</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">HARD CONFLICTS</div>
                            <div class="h3 fw-bold text-white mb-0">
                                <span class="badge bg-success" style="font-size: 1rem;">0 Conflicts</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.75rem;">100% Conflict Free</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('schedules.create') }}" class="btn-indigo">
                        <i class="bi bi-play-circle-fill"></i> Run Schedule Wizard
                    </a>
                    <a href="{{ route('schedules.index') }}" class="btn-glass">
                        <i class="bi bi-eye-fill"></i> View Active Timetable
                    </a>
                    <a href="{{ route('schedules.conflicts') }}" class="btn-glass">
                        <i class="bi bi-shield-check"></i> Run Conflict Diagnostics
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent System Activity -->
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="brand-font fw-bold text-white mb-0">Recent Activities</h5>
                    <a href="{{ route('audit.index') }}" class="text-indigo" style="font-size: 0.8rem; text-decoration: none;">View All</a>
                </div>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex gap-3">
                        <div class="stat-icon-wrapper stat-icon-primary" style="width: 36px; height: 36px; font-size: 1rem; flex-shrink: 0;">
                            <i class="bi bi-cpu-fill"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-white" style="font-size: 0.85rem;">Routine Regenerated</div>
                            <div class="text-muted" style="font-size: 0.76rem;">CSE 47 Section A & B schedule updated</div>
                            <div class="text-dim" style="font-size: 0.7rem;">15 mins ago by Admin</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="stat-icon-wrapper stat-icon-accent" style="width: 36px; height: 36px; font-size: 1rem; flex-shrink: 0;">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-white" style="font-size: 0.85rem;">Teacher Preferences Saved</div>
                            <div class="text-muted" style="font-size: 0.76rem;">Dr. Ahmed updated course suitability scores</div>
                            <div class="text-dim" style="font-size: 0.7rem;">1 hour ago by Dr. Ahmed</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="stat-icon-wrapper stat-icon-success" style="width: 36px; height: 36px; font-size: 1rem; flex-shrink: 0;">
                            <i class="bi bi-journal-plus"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-white" style="font-size: 0.85rem;">Course CSE3202 Updated</div>
                            <div class="text-muted" style="font-size: 0.76rem;">Database Lab credit set to 1.5</div>
                            <div class="text-dim" style="font-size: 0.7rem;">3 hours ago by Coordinator</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if($role === 'TEACHER')
    <!-- Teacher Dashboard View -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="glass-card stat-card">
                <div>
                    <div class="stat-lbl">My Assigned Courses</div>
                    <div class="stat-val text-white">3 Courses</div>
                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Database Systems, Lab, AI</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-primary">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="glass-card stat-card">
                <div>
                    <div class="stat-lbl">Weekly Hours</div>
                    <div class="stat-val text-white">12 Hrs / Wk</div>
                    <div class="text-success" style="font-size: 0.75rem; margin-top: 4px;">Within workload limits</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-success">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="glass-card stat-card">
                <div>
                    <div class="stat-lbl">Availability Status</div>
                    <div class="stat-val text-emerald" style="color: #6ee7b7;">Configured</div>
                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Sun - Thu active</div>
                </div>
                <div class="stat-icon-wrapper stat-icon-warning">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
        </div>
    </div>
@endif

@if($role === 'STUDENT')
    <!-- Student Dashboard View -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="glass-card">
                <h5 class="brand-font fw-bold text-white mb-2"><i class="bi bi-mortarboard-fill me-2 text-indigo"></i> My Batch Overview</h5>
                <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.03);">
                    <div class="fw-semibold text-white">Batch: CSE 47 (Section A)</div>
                    <div class="text-muted" style="font-size: 0.85rem;">Total Students: 60 · Semester: 6th</div>
                </div>
                <a href="{{ route('schedules.index', ['view' => 'section']) }}" class="btn-indigo w-100 justify-content-center">
                    <i class="bi bi-calendar3"></i> Open Class Routine
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="glass-card">
                <h5 class="brand-font fw-bold text-white mb-2"><i class="bi bi-card-checklist me-2 text-warning"></i> Next Upcoming CT / Exam</h5>
                <div class="p-3 rounded mb-3" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2);">
                    <div class="fw-bold text-warning">Database Systems (CSE3201) — CT 1</div>
                    <div class="text-muted" style="font-size: 0.85rem;">Date: Nov 15, 2026 · Time: 10:00 AM · Room 101</div>
                </div>
                <a href="{{ route('exams.index') }}" class="btn-glass w-100 justify-content-center">
                    <i class="bi bi-file-earmark-text"></i> View Full Exam Timetable
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
