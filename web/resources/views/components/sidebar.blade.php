@php
    $role = strtolower((string) auth()->user()?->role);
    $isManager = in_array($role, ['admin', 'coordinator'], true);
@endphp

<aside class="sidebar">

    <!-- Brand Header -->
    <div class="sidebar-logo">
        <div class="logo-icon">
            <i class="bi bi-calendar2-week-fill"></i>
        </div>
        <div>
            <h2>IntelliSched</h2>
            <span>Academic Scheduling System</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section-title">Overview</div>

        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        @if($isManager)
            <div class="nav-section-title">Academic Setup</div>

            <a href="{{ route('terms.index') }}" class="{{ request()->routeIs('terms.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-range"></i>
                <span>Academic Terms</span>
            </a>

            <a href="{{ route('batches.index') }}" class="{{ request()->routeIs('batches.*') ? 'active' : '' }}">
                <i class="bi bi-collection-fill"></i>
                <span>Batches & Sections</span>
            </a>

            <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Courses</span>
            </a>

            <a href="{{ route('teachers.index') }}" class="{{ request()->routeIs('teachers.index', 'teachers.create', 'teachers.edit', 'teachers.show', 'teachers.availability') ? 'active' : '' }}">
                <i class="bi bi-person-video3"></i>
                <span>Teachers</span>
            </a>

            <a href="{{ route('rooms.index') }}" class="{{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Rooms & Labs</span>
            </a>

            <a href="{{ route('timeslots.index') }}" class="{{ request()->routeIs('timeslots.*') ? 'active' : '' }}">
                <i class="bi bi-clock-fill"></i>
                <span>Time Slots</span>
            </a>

            <div class="nav-section-title">Intelligence & Optimization</div>

            <a href="{{ route('teachers.preferences') }}" class="{{ request()->routeIs('teachers.preferences') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i>
                <span>Teacher Suitability</span>
            </a>

            <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                <i class="bi bi-cpu-fill"></i>
                <span>Scheduling Engine</span>
                <span class="badge-count">CP-SAT</span>
            </a>

            <a href="{{ route('exams.index') }}" class="{{ request()->routeIs('exams.*') ? 'active' : '' }}">
                <i class="bi bi-card-checklist"></i>
                <span>Examinations</span>
            </a>

            @if($role === 'admin')
                <div class="nav-section-title">Administration</div>

                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Users</span>
                </a>

                <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Audit Logs</span>
                </a>
            @endif
        @endif

        @if($role === 'teacher')
            <div class="nav-section-title">Teacher Portal</div>

            <a href="{{ route('my.schedule') }}" class="{{ request()->routeIs('my.schedule') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i>
                <span>My Schedule</span>
            </a>

            <a href="{{ route('my.availability') }}" class="{{ request()->routeIs('my.availability') ? 'active' : '' }}">
                <i class="bi bi-calendar-week"></i>
                <span>My Availability</span>
            </a>
        @endif

        @unless($isManager)
            <div class="nav-section-title">Timetables</div>

            <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}">
                <i class="bi bi-calendar3"></i>
                <span>Class Routines</span>
            </a>

            <a href="{{ route('exams.index') }}" class="{{ request()->routeIs('exams.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>Exam Schedules</span>
            </a>
        @endunless

    </nav>

    <!-- Signed-in role -->
    <div class="sidebar-footer">
        <div style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; letter-spacing: 0.08em;">Signed in as</div>
        <div style="font-size: 0.86rem; font-weight: 700; color: #a5b4fc;">
            <i class="bi bi-shield-check me-1"></i> {{ config('intellisched.roles')[$role] ?? ucfirst($role) }}
        </div>
    </div>

</aside>
