<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'IntelliSched') — Intelligent Academic Scheduling</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- IntelliSched design system (served from public/, no build step) -->
    <link rel="stylesheet" href="{{ asset('css/intellisched.css') }}?v={{ filemtime(public_path('css/intellisched.css')) }}">

    @stack('styles')
</head>
<body>

    <!-- Ambient glow + dot grid behind the whole app -->
    <div class="app-backdrop" aria-hidden="true"></div>

    <div class="app-layout">

        <!-- Sidebar Navigation -->
        @include('components.sidebar')
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <!-- Main Workspace -->
        <div class="main-area">

            <!-- Navbar -->
            @include('components.navbar')

            <!-- Main Content Container -->
            <main class="content">

                <!-- Alert Messages -->
                @include('components.alert')

                <!-- Page Content Injection -->
                @yield('content')

            </main>

        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Off-canvas sidebar on small screens.
        document.querySelectorAll('[data-sidebar-toggle], [data-sidebar-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                document.querySelector('.sidebar').classList.toggle('open');
            });
        });
    </script>

    @stack('scripts')

</body>
</html>
