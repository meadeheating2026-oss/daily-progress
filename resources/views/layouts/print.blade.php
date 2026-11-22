<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Print')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap{{ app()->getLocale() == 'ar' ? '.rtl' : '' }}.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <style>
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-arabic-400.woff2) format("woff2"); font-weight: 400; unicode-range: U+0600-06FF, U+0750-077F, U+08A0-08FF, U+FB50-FDFF, U+FE70-FEFF, U+200C-200E; }
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-arabic-600.woff2) format("woff2"); font-weight: 600; unicode-range: U+0600-06FF, U+0750-077F, U+08A0-08FF, U+FB50-FDFF, U+FE70-FEFF, U+200C-200E; }
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-arabic-700.woff2) format("woff2"); font-weight: 700; unicode-range: U+0600-06FF, U+0750-077F, U+08A0-08FF, U+FB50-FDFF, U+FE70-FEFF, U+200C-200E; }
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-latin-400.woff2) format("woff2"); font-weight: 400; unicode-range: U+0000-024F, U+2000-206F; }
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-latin-600.woff2) format("woff2"); font-weight: 600; unicode-range: U+0000-024F, U+2000-206F; }
        @font-face { font-family: "Plex Arabic"; src: url(/fonts/plex-latin-700.woff2) format("woff2"); font-weight: 700; unicode-range: U+0000-024F, U+2000-206F; }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 6mm;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                background: white !important;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: "Plex Arabic", "Segoe UI", Tahoma, sans-serif;
            background: white;
            color: #121c30;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container-fluid py-4">
        @yield('content')
    </div>
    @yield('scripts')
    <script>
        window.addEventListener('load', function () {
            if (typeof window.beforeAutoPrint === 'function') {
                var result = window.beforeAutoPrint();
                if (result && typeof result.then === 'function') {
                    result.then(function () { window.print(); });
                } else {
                    window.print();
                }
            } else {
                window.print();
            }
        });
    </script>
</body>
</html>

