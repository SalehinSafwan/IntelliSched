@php
    $user = auth()->user();
    $role = strtolower((string) $user?->role);
    $currentTerm = \App\Models\AcademicTerm::current();
@endphp

<header class="navbar-custom">

    <!-- Title & current term -->
    <div class="page-header-title">
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>
        <h1>@yield('title', 'Dashboard')</h1>
        @if($currentTerm)
            <span class="term-badge">
                <i class="bi bi-calendar-check-fill"></i> {{ $currentTerm->name }}
            </span>
        @endif
    </div>

    <!-- User profile -->
    <div class="nav-actions">
        <div class="dropdown">
            <a href="#" class="user-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-circle">
                    {{ strtoupper(substr($user->name ?? '?', 0, 1)) }}
                </div>
                <div class="d-none d-lg-block text-start" style="line-height: 1.2;">
                    <div style="font-size: 0.88rem; font-weight: 600; color: #fff;">{{ $user->name }}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">
                        {{ config('intellisched.roles')[$role] ?? $role }}
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 230px;">
                <li class="px-3 py-2 border-bottom border-secondary mb-1">
                    <div class="fw-semibold text-white" style="font-size: 0.88rem;">{{ $user->name }}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ $user->email }}</div>
                </li>
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
    </div>

</header>
