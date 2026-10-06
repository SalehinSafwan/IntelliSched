<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IntelliSched</title>
    <meta name="description" content="IntelliSched — Intelligent Academic Scheduling System Login">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark:       #080c18;
            --card-bg:       rgba(18, 24, 48, 0.55);
            --card-border:   rgba(139, 92, 246, 0.18);
            --text-primary:  #f1f5f9;
            --text-muted:    #94a3b8;
            --indigo:        #6366f1;
            --violet:        #8b5cf6;
            --purple:        #a855f7;
            --grad-main:     linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            --grad-glow:     linear-gradient(135deg, rgba(99,102,241,0.18), rgba(168,85,247,0.12));
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--bg-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            padding: 20px;
        }

        /* ── Animated background mesh ── */
        .bg-mesh {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(99,102,241,0.14) 0%, transparent 60%),
                radial-gradient(ellipse 70% 50% at 80% 80%, rgba(168,85,247,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 60% 10%, rgba(139,92,246,0.08) 0%, transparent 50%);
            z-index: 0;
        }

        /* ── Floating orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.45;
            z-index: 0;
            pointer-events: none;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            top: -15%; left: -10%;
            animation: floatOrb 14s ease-in-out infinite alternate;
        }
        .orb-2 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            bottom: -10%; right: -8%;
            animation: floatOrb 18s ease-in-out infinite alternate-reverse;
        }
        .orb-3 {
            width: 280px; height: 280px;
            background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
            top: 50%; left: 60%;
            animation: floatOrb 22s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            0%   { transform: translate(0px, 0px) scale(1); }
            100% { transform: translate(50px, 35px) scale(1.08); }
        }

        /* ── Grid dots overlay ── */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 32px 32px;
            z-index: 0;
        }

        /* ── Card ── */
        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            background: var(--card-bg);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 48px 44px;
            box-shadow:
                0 40px 80px -20px rgba(0,0,0,0.7),
                0 0 0 1px rgba(255,255,255,0.04) inset;
            animation: cardIn 0.75s cubic-bezier(0.16,1,0.3,1) forwards;
        }

        @keyframes cardIn {
            from { opacity:0; transform: translateY(36px) scale(0.96); filter: blur(8px); }
            to   { opacity:1; transform: translateY(0)    scale(1);    filter: blur(0);  }
        }

        /* ── Logo / Brand ── */
        .logo-wrap {
            width: 68px; height: 68px;
            background: var(--grad-glow);
            border: 1px solid rgba(139,92,246,0.35);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 22px;
            font-size: 2rem;
            color: var(--violet);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        .logo-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--grad-main);
            opacity: 0;
            transition: opacity 0.4s ease;
            border-radius: inherit;
        }
        .logo-wrap:hover { transform: scale(1.06) rotate(4deg); box-shadow: 0 0 30px rgba(139,92,246,0.35); }
        .logo-wrap:hover::after { opacity: 0.12; }
        .logo-wrap i { position: relative; z-index: 1; }

        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #e2e8f0 30%, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-sub {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 4px;
        }

        /* ── Role badges ── */
        .role-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px 0 28px;
        }
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: var(--text-muted);
            transition: all 0.25s ease;
        }
        .role-badge.admin     { border-color: rgba(239,68,68,0.3);   color: #fca5a5; }
        .role-badge.coord     { border-color: rgba(99,102,241,0.4);  color: #a5b4fc; }
        .role-badge.teacher   { border-color: rgba(34,197,94,0.3);   color: #86efac; }
        .role-badge.student   { border-color: rgba(251,191,36,0.3);  color: #fde68a; }

        /* ── Section label ── */
        .section-label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 18px;
        }

        /* ── Input groups ── */
        .field-wrap {
            position: relative;
            margin-bottom: 18px;
        }
        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #4b5563;
            font-size: 1.05rem;
            transition: color 0.3s ease;
            z-index: 5;
            pointer-events: none;
        }
        .field-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #4b5563;
            font-size: 1rem;
            z-index: 5;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.3s ease;
        }
        .field-toggle:hover { color: var(--violet); }

        input.field-input {
            width: 100%;
            background: rgba(15, 20, 40, 0.6) !important;
            border: 1px solid rgba(75,85,99,0.35);
            color: var(--text-primary) !important;
            border-radius: 14px;
            padding: 14px 16px 14px 46px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            outline: none;
        }
        input.field-input::placeholder { color: #4b5563; }
        input.field-input:focus {
            border-color: var(--violet);
            background: rgba(15, 20, 40, 0.8) !important;
            box-shadow: 0 0 0 4px rgba(139,92,246,0.15);
            transform: translateY(-1px);
        }
        .field-wrap:focus-within .field-icon { color: var(--violet); }

        /* ── Remember & forgot ── */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .check-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }
        .check-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--violet);
            cursor: pointer;
            border-radius: 4px;
        }
        .forgot-link {
            font-size: 0.875rem;
            color: var(--violet);
            text-decoration: none;
            transition: color 0.25s ease;
        }
        .forgot-link:hover { color: var(--purple); text-decoration: underline; }

        /* ── Submit button ── */
        .btn-sign-in {
            width: 100%;
            background: var(--grad-main);
            border: none;
            border-radius: 14px;
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.02em;
            color: #fff;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            box-shadow: 0 4px 24px rgba(99,102,241,0.3);
        }
        .btn-sign-in::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .btn-sign-in:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(139,92,246,0.45);
        }
        .btn-sign-in:hover::before { opacity: 1; }
        .btn-sign-in:active { transform: translateY(0); }

        /* Loading state */
        .btn-sign-in.loading { pointer-events: none; opacity: 0.8; }
        .btn-sign-in .spinner {
            display: none;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.07);
        }
        .divider span {
            font-size: 0.75rem;
            color: #374151;
            letter-spacing: 0.05em;
        }

        /* ── Footer ── */
        .card-footer-text {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .card-footer-text a {
            color: var(--violet);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.25s ease;
        }
        .card-footer-text a:hover { color: var(--purple); }

        /* ── Alert ── */
        .alert-error {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shakeX 0.4s ease;
        }
        .alert-success {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 12px;
            padding: 12px 16px;
            color: #86efac;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @keyframes shakeX {
            0%,100% { transform: translateX(0); }
            20%,60% { transform: translateX(-6px); }
            40%,80% { transform: translateX(6px); }
        }

        /* ── Version badge ── */
        .version-tag {
            position: fixed;
            bottom: 18px;
            right: 20px;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.15);
            font-family: 'Inter', sans-serif;
            z-index: 100;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>

<!-- Background layers -->
<div class="bg-mesh"></div>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<!-- Login Card -->
<div class="login-card">

    <!-- Brand -->
    <div class="text-center">
        <div class="logo-wrap">
            <i class="bi bi-calendar2-week"></i>
        </div>
        <div class="brand-name">IntelliSched</div>
        <div class="brand-sub">Intelligent Academic Scheduling System</div>
    </div>

    <!-- Role Badges -->
    <div class="role-badges">
        <span class="role-badge admin"><i class="bi bi-shield-fill"></i> Admin</span>
        <span class="role-badge coord"><i class="bi bi-person-badge"></i> Coordinator</span>
        <span class="role-badge teacher"><i class="bi bi-mortarboard"></i> Teacher</span>
        <span class="role-badge student"><i class="bi bi-person-fill"></i> Student</span>
    </div>

    <!-- Alerts -->
    @if($errors->has('email'))
        <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            {{ $errors->first('email') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Section label -->
    <div class="section-label">Sign in to your account</div>

    <!-- Form -->
    <form id="loginForm" method="POST" action="{{ route('login.post') }}" autocomplete="off">
        @csrf

        <!-- Email -->
        <div class="field-wrap">
            <i class="bi bi-envelope field-icon"></i>
            <input
                id="email"
                type="email"
                name="email"
                class="field-input"
                placeholder="Email address"
                value="{{ old('email', Cookie::get('saved_email', '')) }}"
                required
                autofocus
            >
        </div>

        <!-- Password -->
        <div class="field-wrap">
            <i class="bi bi-lock field-icon"></i>
            <input
                id="password"
                type="password"
                name="password"
                class="field-input"
                placeholder="Password"
                required
                style="padding-right: 46px;"
            >
            <button type="button" class="field-toggle" id="togglePassword" title="Show/Hide password">
                <i class="bi bi-eye" id="toggleIcon"></i>
            </button>
        </div>

        <!-- Remember / Forgot -->
        <div class="meta-row">
            <label class="check-label">
                <input type="checkbox" name="remember_email" id="rememberEmail" {{ Cookie::has('saved_email') ? 'checked' : '' }}>
                Remember my email
            </label>
            <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-sign-in" id="signInBtn">
            <span class="spinner" id="spinner"></span>
            <span id="btnText">Sign In</span>
            <i class="bi bi-arrow-right-short ms-1" id="btnIcon"></i>
        </button>

    </form>

</div>

<!-- Version -->
<div class="version-tag">IntelliSched v1.0 · ariful51</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password toggle
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });

    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('signInBtn');
        const spinner = document.getElementById('spinner');
        const icon = document.getElementById('btnIcon');
        const text = document.getElementById('btnText');
        btn.classList.add('loading');
        spinner.style.display = 'inline-block';
        icon.style.display = 'none';
        text.textContent = 'Signing in...';
    });

    // Role badge highlight on email focus (subtle UX)
    document.getElementById('email').addEventListener('focus', function() {
        document.querySelectorAll('.role-badge').forEach(b => {
            b.style.opacity = '0.6';
        });
    });
    document.getElementById('email').addEventListener('blur', function() {
        document.querySelectorAll('.role-badge').forEach(b => {
            b.style.opacity = '1';
        });
    });
</script>

</body>
</html>