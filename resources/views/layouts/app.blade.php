<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — E-Outsourcing</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap"
          rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/super-admin.css'])

    @stack('styles')
</head>
<body class="app-body" data-session-monitor data-session-monitor-interval="30000">

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <img src="{{ asset('images/logo/logo-ecogreen.webp') }}"
                     alt="Logo PT Ecogreen"
                     class="sidebar-logo-img">
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">E-Outsourcing</span>
                <span class="sidebar-brand-sub">PT Ecogreen Oleochemicals</span>
            </div>
        </div>

        <div class="sidebar-role-badge">
            <span class="sidebar-role-dot"></span>
            @yield('sidebar-role', 'Super Administrator')
        </div>

        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                {{-- Gunakan nama_lengkap sesuai kolom tabel pengguna --}}
                {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'U', 0, 1)) }}
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name">{{ auth()->user()->nama_lengkap ?? 'Pengguna' }}</span>
                <span class="sidebar-user-role">{{ auth()->user()->role ?? '-' }}</span>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                @csrf
            </form>
            <button class="sidebar-user-logout"
                    onclick="document.getElementById('logout-form').submit()"
                    title="Keluar">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                </svg>
            </button>
        </div>

    </aside>

    <div class="app-main" id="app-main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="topbar-breadcrumb">
                    <span class="topbar-breadcrumb-parent">@yield('breadcrumb-parent', 'Super Admin')</span>
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="topbar-breadcrumb-sep">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="topbar-breadcrumb-current">@yield('breadcrumb-current', 'Dashboard')</span>
                </div>
            </div>

            <div class="topbar-right">
                <button class="topbar-icon-btn" aria-label="Notifikasi">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="topbar-notif-dot" id="notif-dot" style="display:none;"></span>
                </button>
                <div class="topbar-avatar">
                    {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'U', 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>

    </div>

    @if(auth()->check() && in_array(auth()->user()->role, ['user_departemen', 'hr', 'super_admin']))
    <!-- Panel Overlay Notifikasi — User Departemen, HR & Super Admin -->
    <div id="notif-panel-overlay" aria-hidden="true">
        <div id="notif-backdrop"></div>
        <div id="notif-panel" role="dialog" aria-label="Notifikasi" aria-modal="false">

            <div class="app-notif-panel-header">
                <span class="app-notif-panel-title">Notifikasi</span>
                <div class="app-notif-panel-actions">
                    <button id="btn-tandai-semua-baca" type="button">
                        Tandai semua dibaca
                    </button>
                    <button id="btn-tutup-notif-panel" type="button" aria-label="Tutup panel notifikasi">
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div id="notif-panel-list" role="list">
                <!-- diisi JS -->
            </div>

            <div class="app-notif-panel-footer">
                <a href="#" id="notif-see-all-link" class="app-notif-panel-see-all">
                    Lihat semua notifikasi
                </a>
            </div>

        </div>
    </div>
    @endif

    @vite(['resources/js/app.js', 'resources/js/session-monitor.js'])
    @include('layouts._panel-profile') {{-- Panel Profil Pengguna --}}
    @stack('scripts')

    <script>
        document.getElementById('sidebar-toggle').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('sidebar--collapsed');
            document.getElementById('app-main').classList.toggle('app-main--expanded');
        });
    </script>

</body>
</html>
