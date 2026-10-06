@php
    $user = auth()->user();
    $role = request('preview_role', session('current_role', $user->role ?? 'ADMIN'));
    $userName = $user->name ?? 'System Administrator';
    $userEmail = $user->email ?? 'admin@intellisched.edu';
@endphp

<header class="navbar-custom">
    
    <!-- Title & Term Header -->
    <div class="page-header-title">
        <h1>@yield('title', 'Dashboard')</h1>
        <span class="term-badge">
            <i class="bi bi-calendar-check-fill"></i> Fall 2026 Academic Term
        </span>
    </div>

    <!-- Right Controls & User Profile -->
    <div class="nav-actions">

        <!-- Global Quick Search -->
        <div class="d-none d-md-block" style="position: relative; width: 220px;">
            <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 0.85rem;"></i>
            <input type="text" class="form-control form-control-custom" placeholder="Quick search..." style="padding-left: 34px !important; height: 38px; font-size: 0.82rem !important; border-radius: 20px !important;">
        </div>

        <!-- System Notifications Icon -->
        <div class="dropdown">
            <button class="btn btn-glass" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 8px 12px; border-radius: 12px; position: relative;">
                <i class="bi bi-bell-fill" style="color: var(--text-muted);"></i>
                <span style="position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; background: var(--accent); border-radius: 50%; border: 2px solid var(--bg-card);"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-dark dropdown-menu-end p-3 shadow-lg" style="width: 300px; background: var(--bg-card); border-color: var(--border-color);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 brand-font" style="font-size: 0.9rem;">Notifications</h6>
                    <span class="badge bg-indigo" style="font-size: 0.68rem; background: var(--primary);">3 New</span>
                </div>
                <hr class="my-2" style="border-color: var(--border-color);">
                <div class="d-flex flex-column gap-2" style="font-size: 0.82rem;">
                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.03);">
                        <div class="fw-semibold text-white">Routine Optimization Completed</div>
                        <div class="text-muted" style="font-size: 0.74rem;">Score 91.4% · 0 Conflicts</div>
                    </div>
                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.03);">
                        <div class="fw-semibold text-white">Teacher Preference Updated</div>
                        <div class="text-muted" style="font-size: 0.74rem;">Dr. Ahmed updated preferences for DB Lab</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="user-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-circle">
                    {{ strtoupper(substr($userName, 0, 1)) }}
                </div>
                <div class="d-none d-lg-block text-start" style="line-height: 1.2;">
                    <div style="font-size: 0.88rem; font-weight: 600; color: #fff;">{{ $userName }}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">
                        {{ $role }}
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg p-2" style="background: var(--bg-card); border-color: var(--border-color); min-width: 220px;">
                <li class="px-3 py-2 border-bottom border-secondary mb-1">
                    <div class="fw-semibold text-white" style="font-size: 0.88rem;">{{ $userName }}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ $userEmail }}</div>
                </li>
                <li>
                    <a class="dropdown-item rounded text-white py-2" href="#" style="font-size: 0.85rem;">
                        <i class="bi bi-person me-2 text-indigo"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded text-white py-2" href="#" style="font-size: 0.85rem;">
                        <i class="bi bi-gear me-2 text-indigo"></i> Preferences
                    </a>
                </li>
                <li><hr class="dropdown-divider border-secondary"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item rounded text-danger py-2 w-100 text-start" style="font-size: 0.85rem; background: none; border: none;">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>

</header>
