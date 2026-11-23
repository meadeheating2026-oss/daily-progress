<!DOCTYPE html>
<html lang="{{ $currentLocale ?? session('locale', app()->getLocale()) }}" dir="{{ $direction ?? (session('locale', app()->getLocale()) == 'ar' ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="UTF-8">
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#213c7a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ __('general.system_name') }}</title>
    <link rel="icon" href="{{ asset('img/app.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preload" href="{{ asset('fonts/plex-arabic-400.woff2') }}" as="font" type="font/woff2" crossorigin>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ ($currentLocale ?? session('locale', app()->getLocale())) == 'ar' ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/kon-theme.css') }}?v=16">
</head>
<body>
    <div class="kon-app" id="konApp">
        @include('layouts.sidebar')

        <div class="kon-main" id="mainContainer">
            @include('layouts.navbar')
            @include('projects.partials.project-nav')

            <main class="kon-page main-content" id="mainContent">
                @include('partials.alerts')
                @yield('content')
            </main>

            @include('layouts.footer')
        </div>
    </div>

    <div class="kon-scrim" id="konScrim" onclick="toggleSidebar()"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const languageLinks = document.querySelectorAll('a[href*="lang/"]');
            languageLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    document.body.style.cursor = 'wait';
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                });
            });
        });

        window.currentLocale = '{{ session("locale", app()->getLocale()) }}';

        // Dark Mode Toggle (KON Work Plan palette + existing localStorage key)
        (function() {
            'use strict';

            const STORAGE_KEY = 'darkMode';
            const CLASS_NAME = 'dark-mode';

            function getInitialMode() {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) return saved === 'enabled';
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function applyDarkMode(enabled) {
                document.documentElement.classList.toggle(CLASS_NAME, enabled);
                document.body.classList.toggle(CLASS_NAME, enabled);
                document.documentElement.setAttribute('data-theme', enabled ? 'dark' : 'light');
                localStorage.setItem(STORAGE_KEY, enabled ? 'enabled' : 'disabled');

                const icon = document.getElementById('darkModeIcon');
                if (icon) {
                    icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                applyDarkMode(getInitialMode());

                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        document.documentElement.classList.remove('dm-preload');
                    });
                });

                const toggle = document.getElementById('darkModeToggle');
                if (toggle) {
                    toggle.addEventListener('click', function() {
                        const newState = !document.documentElement.classList.contains(CLASS_NAME);
                        applyDarkMode(newState);
                    });
                }
            });

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem(STORAGE_KEY)) {
                    applyDarkMode(e.matches);
                }
            });
        })();

        // Sidebar Rail Toggle (Desktop collapse + Mobile drawer)
        function toggleSidebar() {
            const app = document.getElementById('konApp');
            const sidebar = document.getElementById('sidebarMenu');
            const scrim = document.getElementById('konScrim');
            const isMobile = window.innerWidth <= 900;

            if (isMobile) {
                const isOpen = sidebar.classList.toggle('open');
                if (scrim) scrim.classList.toggle('on', isOpen);
            } else {
                const collapsed = app.classList.toggle('rail-collapsed');
                sidebar.classList.toggle('collapsed', collapsed);
                localStorage.setItem('sidebarState', collapsed ? 'closed' : 'open');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const app = document.getElementById('konApp');
            const sidebar = document.getElementById('sidebarMenu');
            const savedState = localStorage.getItem('sidebarState');
            if (window.innerWidth > 900 && savedState === 'closed') {
                app.classList.add('rail-collapsed');
                sidebar.classList.add('collapsed');
            }

            // Auto-scroll long project card titles only when overflowing
            function updateProjectTitleMarquees() {
                document.querySelectorAll('.project-card .project-title').forEach(function(el) {
                    let track = el.querySelector('.kon-title-track');
                    if (!track) {
                        const text = el.textContent.trim();
                        el.textContent = '';
                        track = document.createElement('span');
                        track.className = 'kon-title-track';
                        const bdi = document.createElement('bdi');
                        bdi.textContent = text;
                        track.appendChild(bdi);
                        el.appendChild(track);
                    }
                    el.classList.remove('is-marquee');
                    const overflow = Math.ceil(track.offsetWidth - el.clientWidth);
                    if (overflow > 2) {
                        const dur = Math.max(5.5, Math.min(14, 4 + overflow / 28));
                        el.style.setProperty('--marquee-dist', (overflow + 8) + 'px');
                        el.style.setProperty('--marquee-dur', dur.toFixed(1) + 's');
                        el.classList.add('is-marquee');
                    }
                });
            }

            updateProjectTitleMarquees();
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(updateProjectTitleMarquees);
            }
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(updateProjectTitleMarquees, 150);
            });
        });
    </script>
</body>
</html>
