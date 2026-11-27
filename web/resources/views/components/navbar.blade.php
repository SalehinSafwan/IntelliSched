@php
    $user = auth()->user();
    $role = strtolower((string) $user?->role);
    $isManager = in_array($role, ['admin', 'coordinator'], true);
    $isRep = $role === 'student' && $user?->isClassRep();
    $currentTerm = \App\Models\AcademicTerm::current();
    $unreadMessages = in_array($role, ['teacher', 'student'], true)
        ? app(\App\Services\Messaging\ClassMessageService::class)->unreadCount($user)
        : 0;

    // One link: [label, icon, route, active patterns, badge]. A group
    // is ['group' => label, 'icon' => …, 'items' => [links]].
    $link = fn ($label, $icon, $route, $active = null, $badge = null) => compact('label', 'icon', 'route', 'active', 'badge');

    $menu = [$link('Dashboard', 'bi-grid-1x2-fill', 'dashboard')];

    if ($isManager) {
        $menu[] = ['group' => 'Academic Setup', 'icon' => 'bi-buildings', 'items' => [
            $link('Academic Terms', 'bi-calendar-range', 'terms.index', 'terms.*'),
            $link('Batches & Sections', 'bi-collection-fill', 'batches.index', 'batches.*'),
            $link('Courses', 'bi-journal-bookmark-fill', 'courses.index', 'courses.*'),
            $link('Teachers', 'bi-person-video3', 'teachers.index', ['teachers.index', 'teachers.create', 'teachers.edit', 'teachers.show', 'teachers.availability']),
            $link('Rooms & Labs', 'bi-building', 'rooms.index', 'rooms.*'),
            $link('Time Slots', 'bi-clock-fill', 'timeslots.index', 'timeslots.*'),
        ]];
        $menu[] = ['group' => 'Scheduling', 'icon' => 'bi-cpu-fill', 'items' => [
            $link('Teacher Suitability', 'bi-sliders', 'teachers.preferences'),
            $link('Scheduling Engine', 'bi-cpu-fill', 'schedules.index', 'schedules.*', 'CP-SAT'),
            $link('Examinations', 'bi-card-checklist', 'exams.index', 'exams.*'),
        ]];
        $menu[] = ['group' => 'Analysis', 'icon' => 'bi-graph-up-arrow', 'items' => [
            $link('Bottleneck Analysis', 'bi-diagram-3-fill', 'analysis.bottleneck'),
            $link('Utilization Heatmaps', 'bi-grid-3x3-gap-fill', 'analysis.utilization'),
        ]];

        if ($role === 'admin') {
            $menu[] = ['group' => 'Administration', 'icon' => 'bi-shield-lock-fill', 'items' => [
                $link('Users', 'bi-people-fill', 'users.index', 'users.*'),
                $link('Audit Logs', 'bi-shield-lock-fill', 'audit.index', 'audit.*'),
            ]];
        }
    }

    if ($role === 'teacher') {
        $menu[] = $link('My Schedule', 'bi-calendar-event', 'my.schedule');
        $menu[] = ['group' => 'Teacher Portal', 'icon' => 'bi-person-workspace', 'items' => [
            $link('My Availability', 'bi-calendar-week', 'my.availability'),
            $link('Supervised Sections', 'bi-people-fill', 'my.supervision', 'my.supervision*'),
            $link('Free Slot Finder', 'bi-calendar2-check', 'my.free-slots'),
        ]];
        $menu[] = $link('CR/ACR Messages', 'bi-chat-left-text-fill', 'messages.index', 'messages.*', $unreadMessages ?: null);
    }

    if ($role === 'student') {
        $menu[] = $link('My Routine', 'bi-calendar-day', 'class.routine');
    }

    if ($isRep) {
        $boardCount = app(\App\Services\Student\ClassPortalService::class)->pendingCount($user);
        $menu[] = $link('Notice Board', 'bi-megaphone-fill', 'class.notices', 'class.notices*', $boardCount ?: null);
        $menu[] = $link('Teacher Messages', 'bi-chat-left-text-fill', 'messages.index', 'messages.*', $unreadMessages ?: null);
    }

    if (!$isManager) {
        $timetables = [
            $link('Class Routines', 'bi-calendar3', 'schedules.index', 'schedules.*'),
            $link('Exam Schedules', 'bi-file-earmark-text', 'exams.index', 'exams.*'),
        ];
        // Plain students have few links, so theirs stay on the bar.
        if ($role === 'teacher' || $isRep) {
            $menu[] = ['group' => 'Timetables', 'icon' => 'bi-calendar3', 'items' => $timetables];
        } else {
            array_push($menu, ...$timetables);
        }
    }

    $isActive = fn ($item) => request()->routeIs(...(array) ($item['active'] ?? $item['route']));
@endphp

<header class="topbar">
    <nav class="navbar navbar-expand-lg topbar-inner" aria-label="Main navigation">

        <!-- Brand -->
        <a href="{{ route('dashboard') }}" class="topbar-brand">
            <span class="logo-icon"><i class="bi bi-calendar2-week-fill"></i></span>
            <span class="brand-text">
                <span class="brand-name">IntelliSched</span>
                <span class="brand-sub">Academic Scheduling</span>
            </span>
        </a>

        <!-- Menu (collapses into the toggle below lg) -->
        <div class="collapse navbar-collapse order-3 order-lg-1" id="topnav">
            <ul class="navbar-nav topnav">
                @foreach($menu as $item)
                    @if(isset($item['group']))
                        @php
                            $groupActive = collect($item['items'])->contains(fn ($child) => $isActive($child));
                            $groupBadge = collect($item['items'])->sum(fn ($child) => is_int($child['badge']) ? $child['badge'] : 0);
                        @endphp
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle {{ $groupActive ? 'active' : '' }}" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi {{ $item['icon'] }}"></i>
                                <span>{{ $item['group'] }}</span>
                                @if($groupBadge)<span class="nav-badge">{{ $groupBadge }}</span>@endif
                            </a>
                            <ul class="dropdown-menu topnav-menu">
                                @foreach($item['items'] as $child)
                                    <li>
                                        <a href="{{ route($child['route']) }}" class="dropdown-item {{ $isActive($child) ? 'active' : '' }}"
                                           @if($isActive($child)) aria-current="page" @endif>
                                            <i class="bi {{ $child['icon'] }}"></i>
                                            <span>{{ $child['label'] }}</span>
                                            @if($child['badge'])<span class="nav-badge">{{ $child['badge'] }}</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive($item) ? 'active' : '' }}"
                               @if($isActive($item)) aria-current="page" @endif>
                                <i class="bi {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                                @if($item['badge'])<span class="nav-badge">{{ $item['badge'] }}</span>@endif
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <!-- Term + user (always visible) -->
        <div class="topbar-actions order-1 order-lg-2">
            @if($currentTerm)
                <span class="term-badge" title="Current academic term">
                    <i class="bi bi-calendar-check-fill"></i> {{ $currentTerm->name }}
                </span>
            @endif

            <div class="dropdown">
                <a href="#" class="user-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-circle">
                        {{ strtoupper(substr($user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="d-none d-xl-block text-start" style="line-height: 1.2;">
                        <div style="font-size: 0.86rem; font-weight: 600; color: #fff;">{{ $user->name }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">
                            {{ config('intellisched.roles')[$role] ?? $role }}
                        </div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 240px;">
                    <li class="px-3 py-2 border-bottom border-secondary mb-1">
                        <div class="fw-semibold text-white" style="font-size: 0.88rem;">{{ $user->name }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $user->email }}</div>
                        <div class="mt-1" style="font-size: 0.72rem; font-weight: 700; color: #a5b4fc;">
                            <i class="bi bi-shield-check me-1"></i>{{ config('intellisched.roles')[$role] ?? ucfirst($role) }}{{ $isRep ? ' · ' . $user->class_rep : '' }}
                        </div>
                    </li>
                    @if($currentTerm)
                        <li class="px-3 py-1 d-sm-none message-meta"><i class="bi bi-calendar-check-fill text-emerald me-1"></i> {{ $currentTerm->name }}</li>
                    @endif
                    @if($role === 'teacher')
                        <li><a class="dropdown-item py-2" href="{{ route('my.schedule') }}" style="font-size: 0.85rem;"><i class="bi bi-calendar-event me-2 text-indigo"></i> My Schedule</a></li>
                        <li><a class="dropdown-item py-2" href="{{ route('my.availability') }}" style="font-size: 0.85rem;"><i class="bi bi-calendar-week me-2 text-indigo"></i> My Availability</a></li>
                    @endif
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger py-2 w-100 text-start" style="font-size: 0.85rem;">
                                <i class="bi bi-box-arrow-right me-2"></i> Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <button class="topbar-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#topnav"
                    aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
                @if($unreadMessages || ($boardCount ?? 0))<span class="toggle-dot" aria-hidden="true"></span>@endif
            </button>
        </div>

    </nav>
</header>
