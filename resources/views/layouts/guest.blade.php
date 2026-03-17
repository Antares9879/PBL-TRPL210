<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'E-Outsourcing') — PT Ecogreen Oleochemicals</title>

    {{--
        Google Fonts di-load via <link> langsung — lebih cepat dari @import di CSS
        karena browser bisa mulai fetch font paralel dengan parsing HTML.
        preconnect mengurangi latency DNS + TLS handshake.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap"
          rel="stylesheet">

    {{--
        @vite() menangani:
        - npm run dev   → inject Vite HMR client, load langsung dari dev server (port 5173)
        - npm run build → load file hasil build dari public/build/ dengan hash cache-busting

        login.css sudah didaftarkan sebagai entry point di vite.config.js.
        Tailwind utility class tersedia karena @tailwindcss/vite memproses semua CSS entry point.
        login.js didaftarkan di @push('scripts') pada login.blade.php.
    --}}
    @vite(['resources/css/login.css'])

    @stack('styles')
</head>
<body>
    @yield('content')

    @stack('scripts')
</body>
</html>