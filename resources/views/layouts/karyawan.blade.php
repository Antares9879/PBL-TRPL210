{{--
    resources/views/layouts/karyawan.blade.php

    Layout shell untuk semua halaman Karyawan Outsource (F01–F06).
    Tidak ada data bisnis di sini — semua konten dimuat via AJAX.

    Sections yang tersedia untuk child views:
      @section('title')            — judul tab browser
      @section('breadcrumb-parent')— parent breadcrumb (desktop)
      @section('breadcrumb-current')— current breadcrumb (desktop)
      @section('content')          — konten utama halaman
      @push('scripts')             — JS spesifik halaman (via @vite)
--}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d1117">

    <title>@yield('title', 'Dashboard') — E-Outsourcing Karyawan</title>

    {{-- Preconnect font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap"
          rel="stylesheet">

    {{-- CSS global + CSS karyawan --}}
    @vite(['resources/css/app.css', 'resources/css/karyawan.css'])

    {{-- Injeksi CSS tambahan dari child view --}}
    @stack('styles')
</head>
<body class="karyawan-body">

{{-- ══════════════════════════════════════════════════════════════════════════
     SIDEBAR — Desktop ≥ 1024px
     Dikontrol kelas k-sidebar--collapsed via JS (toggle button)
══════════════════════════════════════════════════════════════════════════ --}}
<aside class="k-sidebar" id="k-sidebar" role="navigation" aria-label="Menu utama karyawan">

    {{-- Brand --}}
    <div class="k-sidebar-brand">
        <div class="k-sidebar-logo">
            <img src="/images/logo/logo-ecogreen.png"
                 alt="Logo PT Ecogreen Oleochemicals"
                 onerror="this.style.display='none'">
        </div>
        <div class="k-sidebar-text">
            <span class="k-sidebar-name">E-Outsourcing</span>
            <span class="k-sidebar-sub">PT Ecogreen Oleochemicals</span>
        </div>
    </div>

    {{-- Role badge --}}
    <div class="k-sidebar-role">
        <span class="k-sidebar-role-dot" aria-hidden="true"></span>
        <span>Karyawan Outsource</span>
    </div>

    {{-- Navigation items — partial reusable --}}
    <nav class="k-sidebar-nav" aria-label="Navigasi karyawan">
        @include('karyawan._sidebar-nav')
    </nav>

    {{-- User info + logout --}}
    <div class="k-sidebar-user">
        <div class="k-sidebar-user-avatar" aria-hidden="true">
            {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'K', 0, 1)) }}
        </div>
        <div class="k-sidebar-user-info">
            <span class="k-sidebar-user-name">
                {{ auth()->user()->nama_lengkap ?? 'Karyawan' }}
            </span>
            <span class="k-sidebar-user-role">Karyawan Outsource</span>
        </div>
        {{-- Logout button — POST ke /logout (web route) --}}
        <form method="POST" action="{{ route('logout') }}" style="display:contents;">
            @csrf
            <button type="submit"
                    class="k-sidebar-logout"
                    title="Keluar dari sistem"
                    aria-label="Keluar dari sistem">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
                </svg>
            </button>
        </form>
    </div>

</aside>

{{-- ══════════════════════════════════════════════════════════════════════════
     MAIN WRAPPER — offset untuk sidebar di desktop
══════════════════════════════════════════════════════════════════════════ --}}
<div class="k-main" id="k-main">

    {{-- ── TOPBAR ──────────────────────────────────────────────────────── --}}
    <header class="k-topbar" role="banner">
        <div class="k-topbar-left">

            {{-- Sidebar toggle (desktop only) --}}
            <button class="k-sidebar-toggle"
                    id="btn-sidebar-toggle"
                    aria-label="Toggle sidebar"
                    aria-expanded="true"
                    aria-controls="k-sidebar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Brand (mobile only) --}}
            <div class="k-topbar-brand">
                <div class="k-topbar-logo">
                    <img src="/images/logo/logo-ecogreen.png"
                         alt="Logo"
                         onerror="this.style.display='none'">
                </div>
                <div>
                    <p class="k-topbar-title">E-Outsourcing</p>
                    <p class="k-topbar-subtitle">Karyawan</p>
                </div>
            </div>

            {{-- Breadcrumb (desktop only) --}}
            <nav class="k-topbar-breadcrumb" aria-label="Breadcrumb">
                <span class="k-topbar-breadcrumb-parent">
                    @yield('breadcrumb-parent', 'Karyawan')
                </span>
                <svg class="k-topbar-breadcrumb-sep"
                     fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="k-topbar-breadcrumb-current">
                    @yield('breadcrumb-current', 'Dashboard')
                </span>
            </nav>

        </div>

        <div class="k-topbar-right">

            {{-- Notifikasi bell --}}
            <button class="k-topbar-btn"
                    id="btn-notif"
                    aria-label="Notifikasi"
                    title="Notifikasi">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 0 0-9.33-4.997M15 17v1a3 3 0 0 1-6 0v-1M6 11a6 6 0 0 1 6-6"/>
                </svg>
                {{-- Dot merah — ditampilkan JS jika ada notif belum dibaca --}}
                <span class="k-topbar-notif-dot" id="notif-dot" style="display:none;" aria-hidden="true"></span>
            </button>

            {{-- Avatar + nama --}}
            <div class="k-topbar-avatar"
                 role="button"
                 tabindex="0"
                 title="{{ auth()->user()->nama_lengkap ?? 'Karyawan' }}"
                 aria-label="Menu akun: {{ auth()->user()->nama_lengkap ?? 'Karyawan' }}">
                {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'K', 0, 1)) }}
            </div>

        </div>
    </header>

    {{-- ── CONTENT ────────────────────────────────────────────────────── --}}
    <main class="k-content" id="k-content" role="main">
        @yield('content')
    </main>

</div>{{-- /k-main --}}

{{-- ══════════════════════════════════════════════════════════════════════════
     BOTTOM NAVIGATION — Mobile < 1024px
══════════════════════════════════════════════════════════════════════════ --}}
<nav class="k-bottom-nav" role="navigation" aria-label="Navigasi bawah">

    {{-- Dashboard --}}
    <a href="{{ url('/karyawan/dashboard') }}"
       class="k-bottom-nav-item {{ request()->is('karyawan/dashboard') ? 'k-bottom-nav-item--active' : '' }}"
       aria-label="Dashboard"
       aria-current="{{ request()->is('karyawan/dashboard') ? 'page' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
        </svg>
        <span>Beranda</span>
    </a>

    {{-- Jadwal --}}
    <a href="{{ url('/karyawan/jadwal') }}"
       class="k-bottom-nav-item {{ request()->is('karyawan/jadwal*') ? 'k-bottom-nav-item--active' : '' }}"
       aria-label="Jadwal Kerja"
       aria-current="{{ request()->is('karyawan/jadwal*') ? 'page' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
        </svg>
        <span>Jadwal</span>
    </a>

    {{-- Absensi GPS — highlight khusus --}}
    <a href="{{ url('/karyawan/absensi') }}"
       class="k-bottom-nav-item k-bottom-nav-item--absensi {{ request()->is('karyawan/absensi*') ? 'k-bottom-nav-item--active' : '' }}"
       aria-label="Absensi GPS"
       aria-current="{{ request()->is('karyawan/absensi*') ? 'page' : '' }}">
        <div class="k-bottom-nav-dot">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
            </svg>
        </div>
        <span>Absensi</span>
    </a>

    {{-- Pengajuan (izin/lembur) --}}
    <a href="{{ url('/karyawan/izin') }}"
       class="k-bottom-nav-item {{ (request()->is('karyawan/izin*') || request()->is('karyawan/lembur*')) ? 'k-bottom-nav-item--active' : '' }}"
       aria-label="Pengajuan Izin"
       aria-current="{{ (request()->is('karyawan/izin*') || request()->is('karyawan/lembur*')) ? 'page' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
        <span>Pengajuan</span>
    </a>

    {{-- Riwayat --}}
    <a href="{{ url('/karyawan/riwayat') }}"
       class="k-bottom-nav-item {{ request()->is('karyawan/riwayat*') ? 'k-bottom-nav-item--active' : '' }}"
       aria-label="Riwayat Absensi"
       aria-current="{{ request()->is('karyawan/riwayat*') ? 'page' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
        </svg>
        <span>Riwayat</span>
    </a>

</nav>

{{-- ══════════════════════════════════════════════════════════════════════════
     JS GLOBAL: sidebar toggle
══════════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    const sidebar   = document.getElementById('k-sidebar');
    const main      = document.getElementById('k-main');
    const toggleBtn = document.getElementById('btn-sidebar-toggle');
    const KEY       = 'karyawan_sidebar_collapsed';

    if (!sidebar || !toggleBtn) return;

    // Restore state dari localStorage
    const isCollapsed = localStorage.getItem(KEY) === '1';
    if (isCollapsed) {
        sidebar.classList.add('k-sidebar--collapsed');
        main.classList.add('k-main--expanded');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    toggleBtn.addEventListener('click', () => {
        const collapsed = sidebar.classList.toggle('k-sidebar--collapsed');
        main.classList.toggle('k-main--expanded', collapsed);
        toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        localStorage.setItem(KEY, collapsed ? '1' : '0');
    });
})();
</script>

{{-- JS global (app.js), lalu JS spesifik halaman --}}
@vite(['resources/js/app.js'])
@stack('scripts')

</body>
</html>