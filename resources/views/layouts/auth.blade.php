<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('darkMode');
                var dark = saved ? saved === 'enabled' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (dark) {
                    document.documentElement.classList.add('dark-mode');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
                document.documentElement.classList.add('dm-preload');
            } catch (e) {}
        })();
    </script>
    <style>
        html.dark-mode, html.dark-mode body { background-color: #0b1120; color: #e6ecf7; }
        html.dark-mode { color-scheme: dark; }
        html.dm-preload, html.dm-preload * { transition: none !important; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#213c7a">
    <title>{{ config('app.name', 'KON') }} - @yield('title', __('تسجيل الدخول'))</title>
    <link rel="icon" href="{{ asset('img/app.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="preload" href="{{ asset('fonts/plex-arabic-400.woff2') }}" as="font" type="font/woff2" crossorigin>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ app()->getLocale() == 'ar' ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/kon-theme.css') }}">
</head>
<body class="antialiased" style="min-height: 100vh;">
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.documentElement.classList.remove('dm-preload');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
