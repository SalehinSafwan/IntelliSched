<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 90px 36px 60px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e1b4b; font-size: 10.5px; line-height: 1.45; }

        header { position: fixed; top: -70px; left: 0; right: 0; height: 56px; border-bottom: 2px solid #6366f1; }
        header .brand { font-size: 17px; font-weight: bold; color: #4338ca; }
        header .brand span { color: #a855f7; }
        header .sub { font-size: 9px; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; }
        header .title { position: absolute; right: 0; top: 4px; text-align: right; font-size: 13px; font-weight: bold; color: #111827; }
        header .title small { display: block; font-size: 8.5px; font-weight: normal; color: #6b7280; }

        footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 24px; border-top: 1px solid #e5e7eb; font-size: 8.5px; color: #9ca3af; }
        footer .page:after { content: counter(page); }

        h2 { font-size: 14px; color: #312e81; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #e0e7ff; }
        h3 { font-size: 12px; color: #4338ca; margin: 12px 0 4px; }
        p { margin: 0 0 6px; }
        .muted { color: #6b7280; }

        table { width: 100%; border-collapse: collapse; margin: 6px 0 12px; }
        th { background: #eef2ff; color: #3730a3; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 6px 7px; border: 1px solid #e0e7ff; }
        td { padding: 6px 7px; border: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #fafaff; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; font-weight: bold; background: #eef2ff; color: #4338ca; margin: 0 2px 2px 0; }
        .badge-lab { background: #fdf2f8; color: #be185d; }
        .badge-ok { background: #ecfdf5; color: #047857; }
        .badge-off { background: #fef2f2; color: #b91c1c; }

        .card { border: 1px solid #e0e7ff; border-radius: 6px; padding: 10px 12px; margin-bottom: 10px; page-break-inside: avoid; }
        .card .name { font-size: 12.5px; font-weight: bold; color: #111827; }
        .kv { width: 100%; border: none; margin: 4px 0 0; }
        .kv td { border: none; padding: 2px 0; background: none !important; }
        .kv td.k { color: #6b7280; width: 32%; }
    </style>
    @stack('pdf-styles')
</head>
<body>
    <header>
        <div class="brand">Intelli<span>Sched</span></div>
        <div class="sub">Academic Scheduling System</div>
        <div class="title">
            {{ $title }}
            <small>Generated {{ $generatedAt->format('d M Y, h:i A') }}{{ $generatedBy ? ' by ' . $generatedBy : '' }}</small>
        </div>
    </header>

    <footer>
        <span>IntelliSched · {{ $title }}</span>
        <span style="float: right;">Page <span class="page"></span></span>
    </footer>

    <main>
        @yield('content')
    </main>
</body>
</html>
