@php
    $user = auth()->user();
    // Allow role override via query param or session for easy UI testing/previewing
    $role = request('preview_role', session('current_role', $user->role ?? 'ADMIN'));
    $role = strtoupper($role);
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

    <!-- Dynamic Navigation Menu -->
    <nav class="sidebar-nav">

        <div class="nav-section-title">Core Navigation</div>

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        @if(in_array($role, ['ADMIN', 'COORDINATOR']))
            <!-- User Management -->
            @if($role === 'ADMIN')
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Users</span>
                </a>
            @endif

            <!-- Teachers -->
            <a href="{{ route('teachers.index') }}" class="{{ request()->routeIs('teachers.index', 'teachers.create', 'teachers.edit', 'teachers.show') ? 'active' : '' }}">
                <i class="bi bi-person-video3"></i>
                <span>Teachers</span>
            </a>

            <!-- Courses -->
            <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Courses</span>
            </a>

            <!-- Batches -->
            <a href="{{ route('batches.index') }}" class="{{ request()->routeIs('batches*') ? 'active' : '' }}">
                <i class="bi bi-collection-fill"></i>
                <span>Batches & Sections</span>
            </a>

            <!-- Rooms -->
            <a href="{{ route('rooms.index') }}" class="{{ request()->routeIs('rooms*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                <span>Rooms</span>
            </a>

            <!-- Time Slots -->
            <a href="{{ route('timeslots.index') }}" class="{{ request()->routeIs('timeslots*') ? 'active' : '' }}">
                <i class="bi bi-clock-fill"></i>
                <span>Time Slots</span>
            </a>

            <div class="nav-section-title">Intelligence & Optimization</div>

            <!-- CR & ACR Network -->
            <a href="{{ route('teacher.cr-communication') }}" class="{{ request()->routeIs('teacher.cr-communication*') ? 'active' : '' }}">
                <i class="bi bi-people-fill text-indigo"></i>
                <span>CR & ACR Network</span>
            </a>

            <!-- Teacher Preferences -->
            <a href="{{ route('teachers.preferences') }}" class="{{ request()->routeIs('teachers.preferences*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i>
                <span>Teacher Preferences</span>
            </a>

            <!-- Scheduling Engine -->
            <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules*') ? 'active' : '' }}">
                <i class="bi bi-cpu-fill"></i>
                <span>Scheduling Engine</span>
                <span class="badge-count">CP-SAT</span>
            </a>

            <!-- Examinations -->
            <a href="{{ route('exams.index') }}" class="{{ request()->routeIs('exams*') ? 'active' : '' }}">
                <i class="bi bi-card-checklist"></i>
                <span>Examinations</span>
            </a>

            @if($role === 'ADMIN')
                <div class="nav-section-title">Administration</div>
                <!-- Audit Logs -->
                <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Audit Logs</span>
                </a>
            @endif
        @endif

        @if($role === 'TEACHER')
            <div class="nav-section-title">Teacher Portal</div>

            <a href="{{ route('teacher.cr-communication') }}" class="{{ request()->routeIs('teacher.cr-communication*') ? 'active' : '' }}">
                <i class="bi bi-megaphone-fill text-indigo"></i>
                <span>CR & ACR Communication</span>
            </a>

            <a href="{{ route('teachers.show', ['teacher' => 1]) }}" class="{{ request()->routeIs('teachers.show') ? 'active' : '' }}">
                <i class="bi bi-book-half"></i>
                <span>My Courses</span>
            </a>

            <a href="{{ route('teachers.preferences') }}" class="{{ request()->routeIs('teachers.preferences') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i>
                <span>My Preferences</span>
            </a>

            <a href="{{ route('teachers.availability', ['teacher' => 1]) }}" class="{{ request()->routeIs('teachers.availability') ? 'active' : '' }}">
                <i class="bi bi-calendar-week"></i>
                <span>My Availability</span>
            </a>

            <a href="{{ route('schedules.index', ['view' => 'teacher']) }}" class="{{ request()->routeIs('schedules*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i>
                <span>My Schedule</span>
            </a>
        @endif

        @if($role === 'STUDENT')
            <div class="nav-section-title">Student Portal</div>

            <a href="{{ route('schedules.index', ['view' => 'section']) }}" class="{{ request()->routeIs('schedules*') ? 'active' : '' }}">
                <i class="bi bi-calendar3"></i>
                <span>Class Routine</span>
            </a>

            <a href="{{ route('exams.index') }}" class="{{ request()->routeIs('exams*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>CT & Exam Schedule</span>
            </a>
        @endif

    </nav>

    <!-- Role Indicator Footer -->
    <div style="padding: 16px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Active Role</div>
                <div style="font-size: 0.85rem; font-weight: 700; color: #a5b4fc;">
                    <i class="bi bi-shield-check me-1"></i> {{ $role }}
                </div>
            </div>
            <!-- Role Toggle quick switcher for UI previewing -->
            <div class="dropdown">
                <button class="btn btn-sm btn-glass dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 3px 8px; font-size: 0.72rem;">
                    Switch
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg" style="background: var(--bg-card); border-color: var(--border-color);">
                    <li><a class="dropdown-item text-white" href="?preview_role=ADMIN"><i class="bi bi-shield-fill text-danger me-2"></i> Admin</a></li>
                    <li><a class="dropdown-item text-white" href="?preview_role=COORDINATOR"><i class="bi bi-person-badge text-primary me-2"></i> Coordinator</a></li>
                    <li><a class="dropdown-item text-white" href="?preview_role=TEACHER"><i class="bi bi-mortarboard text-success me-2"></i> Teacher</a></li>
                    <li><a class="dropdown-item text-white" href="?preview_role=STUDENT"><i class="bi bi-person-fill text-warning me-2"></i> Student</a></li>
                </ul>
            </div>
        </div>
    </div>

</aside>
