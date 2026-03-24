<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d1117">

    <title>@yield('title', 'Dashboard') — Karyawan | E-Outsourcing</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Mono:wght@400;500&display=swap"
          rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/karyawan.css'])

    @stack('styles')
</head>
<body class="karyawan-body">

    {{-- ═══ SIDEBAR — Desktop (≥ 1024px) ═══════════════════════════════════ --}}
    <aside class="k-sidebar" id="k-sidebar">

        {{-- Brand --}}
        <div class="k-sidebar-brand">
            <div class="k-sidebar-logo">
                <img src="{{ asset('images/logo/logo-ecogreen.webp') }}"
                     alt="Logo PT Ecogreen">
            </div>
            <div class="k-sidebar-text">
                <span class="k-sidebar-name">E-Outsourcing</span>
                <span class="k-sidebar-sub">PT Ecogreen Oleochemicals</span>
            </div>
        </div>

        {{-- Role badge --}}
        <div class="k-sidebar-role">
            <span class="k-sidebar-role-dot"></span>
            <span class="k-sidebar-text">Karyawan Outsource</span>
        </div>

        {{-- Navigation --}}
        <nav class="k-sidebar-nav" aria-label="Navigasi Karyawan">
            @include('karyawan._sidebar-nav')
        </nav>

        {{-- User footer --}}
        <div class="k-sidebar-user">
            <div class="k-sidebar-user-avatar">
                {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'K', 0, 1)) }}
            </div>
            <div class="k-sidebar-user-info">
                <span class="k-sidebar-user-name">
                    {{ auth()->user()->nama_lengkap ?? 'Karyawan' }}
                </span>
                <span class="k-sidebar-user-role">
                    {{ auth()->user()->karyawan->nomor_karyawan ?? 'Karyawan' }}
                </span>
            </div>
            <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
            <button class="k-sidebar-logout"
                    onclick="document.getElementById('sidebar-logout-form').submit()"
                    title="Keluar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                </svg>
            </button>
        </div>

    </aside>

    {{-- ═══ MAIN AREA ════════════════════════════════════════════════════════ --}}
    <div class="k-main" id="k-main">

        {{-- ── Topbar ─────────────────────────────────────────────────────── --}}
        <header class="k-topbar" role="banner">

            <div class="k-topbar-left">

                {{-- Toggle sidebar (desktop) --}}
                <button class="k-sidebar-toggle" id="k-sidebar-toggle"
                        aria-label="Toggle sidebar" aria-controls="k-sidebar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Brand (mobile only) --}}
                <div class="k-topbar-brand">
                    <div class="k-topbar-logo">
                        <img src="{{ asset('images/logo/logo-ecogreen.webp') }}"
                             alt="Logo PT Ecogreen">
                    </div>
                    <div>
                        <div class="k-topbar-title">E-Outsourcing</div>
                    </div>
                </div>

                {{-- Breadcrumb (desktop only) --}}
                <nav class="k-topbar-breadcrumb" aria-label="Breadcrumb">
                    <span class="k-topbar-breadcrumb-parent">
                        @yield('breadcrumb-parent', 'Karyawan')
                    </span>
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         class="k-topbar-breadcrumb-sep" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="k-topbar-breadcrumb-current">
                        @yield('breadcrumb-current', 'Dashboard')
                    </span>
                </nav>

            </div>

            <div class="k-topbar-right">

                {{-- Notifikasi --}}
                <button class="k-topbar-btn" id="k-btn-notif" aria-label="Notifikasi">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="k-topbar-notif-dot" id="k-notif-dot" style="display:none;"></span>
                </button>

                {{-- Avatar --}}
                <div class="k-topbar-avatar" title="{{ auth()->user()->nama_lengkap ?? 'Karyawan' }}">
                    {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'K', 0, 1)) }}
                </div>

            </div>

        </header>

        {{-- ── Main Content ────────────────────────────────────────────────── --}}
        <main class="k-content" id="k-content" role="main">
            @yield('content')
        </main>

    </div>{{-- /k-main --}}

    {{-- ═══ BOTTOM NAVIGATION — Mobile (< 1024px) ═══════════════════════════ --}}
    <nav class="k-bottom-nav" role="navigation" aria-label="Navigasi Utama">

        {{-- Dashboard --}}
        <a href="{{ url('/karyawan/dashboard') }}"
           class="k-bottom-nav-item {{ request()->is('karyawan/dashboard') ? 'k-bottom-nav-item--active' : '' }}"
           aria-label="Dashboard">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
            </svg>
            <span>Beranda</span>
        </a>

        {{-- Jadwal --}}
        <a href="{{ url('/karyawan/jadwal') }}"
           class="k-bottom-nav-item {{ request()->is('karyawan/jadwal*') ? 'k-bottom-nav-item--active' : '' }}"
           aria-label="Jadwal Kerja">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
            <span>Jadwal</span>
        </a>

        {{-- Absensi — tombol khusus di tengah --}}
        <a href="{{ url('/karyawan/absensi') }}"
           class="k-bottom-nav-item k-bottom-nav-item--absensi {{ request()->is('karyawan/absensi*') ? 'k-bottom-nav-item--active' : '' }}"
           aria-label="Absensi">
            <div class="k-bottom-nav-dot">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
            </div>
            <span>Absensi</span>
        </a>

        {{-- Pengajuan (Lembur & Izin) --}}
        <a href="{{ url('/karyawan/izin') }}"
           class="k-bottom-nav-item {{ request()->is('karyawan/izin*') || request()->is('karyawan/lembur*') ? 'k-bottom-nav-item--active' : '' }}"
           aria-label="Pengajuan">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
            <span>Pengajuan</span>
        </a>

        {{-- Riwayat --}}
        <a href="{{ url('/karyawan/riwayat') }}"
           class="k-bottom-nav-item {{ request()->is('karyawan/riwayat*') ? 'k-bottom-nav-item--active' : '' }}"
           aria-label="Riwayat Absensi">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
            <span>Riwayat</span>
        </a>

    </nav>

    {{-- ═══ SCRIPTS ═══════════════════════════════════════════════════════════ --}}
    @vite(['resources/js/app.js'])
    @stack('scripts')

    {{-- Toggle sidebar script --}}
    <script>
        const sidebar = document.getElementById('k-sidebar');
        const main    = document.getElementById('k-main');
        const toggle  = document.getElementById('k-sidebar-toggle');

        if (toggle && sidebar && main) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('k-sidebar--collapsed');
                main.classList.toggle('k-main--expanded');
            });
        }
    </script>

</body>
</html>