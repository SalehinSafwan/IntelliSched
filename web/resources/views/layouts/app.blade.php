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

        <!-- Top menu bar -->
        @include('components.navbar')

        <!-- Main Workspace -->
        <div class="main-area">

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
        // Modals must live directly under <body>. Inside .app-layout
        // (its own stacking context) they render beneath Bootstrap's
        // backdrop, which dims the page and swallows every click.
        document.querySelectorAll('.modal').forEach(function (modal) {
            document.body.appendChild(modal);
        });

        // Copy / WhatsApp share for ready-made notices and routines.
        // data-copy-target / data-whatsapp-target point at a textarea
        // (possibly edited by the user) holding the text.
        function sourceText(selector) {
            var el = document.querySelector(selector);
            return el ? (el.value !== undefined ? el.value : el.textContent) : '';
        }
        function flash(btn, text) {
            var label = btn.querySelector('span');
            if (!label) return;
            var old = label.dataset.label || label.textContent;
            label.dataset.label = old;
            label.textContent = text;
            setTimeout(function () { label.textContent = old; }, 1800);
        }
        document.addEventListener('click', function (e) {
            var copy = e.target.closest('[data-copy-target]');
            if (copy) {
                var text = sourceText(copy.dataset.copyTarget);
                var fallback = function () {
                    var tmp = document.createElement('textarea');
                    tmp.value = text;
                    tmp.style.position = 'fixed';
                    tmp.style.opacity = '0';
                    document.body.appendChild(tmp);
                    tmp.select();
                    document.execCommand('copy');
                    tmp.remove();
                    flash(copy, 'Copied!');
                };
                // The clipboard API needs a secure origin (https / localhost).
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function () { flash(copy, 'Copied!'); }, fallback);
                } else {
                    fallback();
                }
                return;
            }
            var share = e.target.closest('[data-whatsapp-target]');
            if (share) {
                e.preventDefault();
                window.open('https://wa.me/?text=' + encodeURIComponent(sourceText(share.dataset.whatsappTarget)), '_blank', 'noopener');
            }
        });
    </script>

    @stack('scripts')

    {{-- Schedule assistant chat panel for the teacher and student portals --}}
    @if(in_array(strtolower((string) auth()->user()?->role), ['teacher', 'student'], true))
        @include('components.chatbot')
    @endif

</body>
</html>
