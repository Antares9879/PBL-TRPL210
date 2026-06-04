@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Dashboard')

@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

{{-- ══ SIDEBAR NAV — scope F17, F18, F19 ══════════════════════════════════ --}}
@section('sidebar-nav')

    {{-- DASHBOARD --}}
    <div class="nav-section-label">Beranda</div>
    <a href="{{ url('/super-admin/dashboard') }}"
       class="nav-item {{ request()->is('super-admin/dashboard') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
            </svg>
        </span>
        <span class="nav-item-label">Dashboard</span>
    </a>

    {{-- F17: MANAJEMEN AKUN --}}
    <div class="nav-section-label">Manajemen Akun</div>
    <a href="{{ url('/super-admin/akun') }}"
       class="nav-item {{ request()->is('super-admin/akun*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
            </svg>
        </span>
        <span class="nav-item-label">Pengguna</span>
    </a>

    {{-- F18: MASTER DATA --}}
    <div class="nav-section-label">Master Data</div>
    <a href="{{ url('/super-admin/master-data/perusahaan') }}"
       class="nav-item {{ request()->is('super-admin/master-data/perusahaan*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
            </svg>
        </span>
        <span class="nav-item-label">Perusahaan Outsourcing</span>
    </a>
    <a href="{{ url('/super-admin/master-data/departemen') }}"
       class="nav-item {{ request()->is('super-admin/master-data/departemen*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm0 8a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Departemen</span>
    </a>
    <a href="{{ url('/super-admin/master-data/shift') }}"
       class="nav-item {{ request()->is('super-admin/master-data/shift*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </span>
        <span class="nav-item-label">Shift & Waktu</span>
    </a>

    {{-- F19: KONFIGURASI AREA --}}
    <div class="nav-section-label">Konfigurasi</div>
    <a href="{{ url('/super-admin/konfigurasi-area') }}"
       class="nav-item {{ request()->is('super-admin/konfigurasi-area*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
        </span>
        <span class="nav-item-label">Konfigurasi Area</span>
    </a>

    {{-- AUDIT LOG --}}
    <div class="nav-section-label">Sistem</div>
    <a href="{{ url('/super-admin/audit-log') }}"
       class="nav-item {{ request()->is('super-admin/audit-log*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <span class="nav-item-label">Audit Log</span>
    </a>

@endsection

{{-- ══ DASHBOARD CONTENT ════════════════════════════════════════════════════ --}}
@section('content')

<div class="dashboard-wrap">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Selamat datang kembali — ringkasan status sistem hari ini.</p>
        </div>
        <div class="page-header-right">
            <div class="page-date-badge">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                </svg>
                <span id="live-date">—</span>
            </div>
        </div>
    </div>

    {{-- ── STAT CARDS ─────────────────────────────────────────────────────── --}}
    {{--
        Data ini adalah PLACEHOLDER.
        Nantinya diisi via AJAX dari: GET /api/super-admin/dashboard/stats
        JS: resources/js/super-admin/dashboard.js
    --}}
    <div class="stats-grid">

        {{-- Card 1: Total Pengguna (F17) --}}
        <div class="stat-card stat-card--blue anim-fade-up">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Total Pengguna</span>
            </div>
            <div class="stat-card-value" data-stat="total-pengguna">—</div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--up" data-stat="pengguna-baru">— baru bulan ini</span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 2: Perusahaan Outsource (F18) --}}
        <div class="stat-card stat-card--green anim-fade-up anim-d1">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="stat-card-label">Perusahaan Outsource</span>
            </div>
            <div class="stat-card-value" data-stat="total-perusahaan">—</div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral" data-stat="perusahaan-aktif">— aktif terdaftar</span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>

        {{-- Card 3: Departemen (F18) --}}
        <div class="stat-card stat-card--amber anim-fade-up anim-d2">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Departemen</span>
            </div>
            <div class="stat-card-value" data-stat="total-departemen">—</div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral" data-stat="departemen-aktif">— aktif beroperasi</span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2z"/>
                </svg>
            </div>
        </div>

        {{-- Card 4: Radius Area GPS (F19) --}}
        <div class="stat-card stat-card--violet anim-fade-up anim-d3">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Radius Area GPS</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="radius-meter">—</span>
                <span class="stat-card-unit">m</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral">
                    Diperbarui: <span data-stat="radius-updated">—</span>
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
            </div>
        </div>

    </div>{{-- /stats-grid --}}

    {{-- ── SECONDARY ROW: Role breakdown + Quick Actions ─────────────────── --}}
    <div class="dashboard-secondary">

        {{-- Role Breakdown --}}
        <div class="dash-panel anim-fade-up anim-d2">
            <div class="dash-panel-header">
                <h2 class="dash-panel-title">Distribusi Role Pengguna</h2>
                <span class="dash-panel-tag">F17</span>
            </div>
            <div class="dash-panel-body">
                {{-- Bar rows — diisi JS via data-stat --}}
                <div class="role-row">
                    <div class="role-row-left">
                        <span class="role-dot role-dot--hr"></span>
                        <span class="role-label">HR</span>
                    </div>
                    <div class="role-bar-track">
                        <div class="role-bar role-bar--hr" data-stat="pct-hr" style="width: 0%"></div>
                    </div>
                    <span class="role-count" data-stat="count-hr">—</span>
                </div>
                <div class="role-row">
                    <div class="role-row-left">
                        <span class="role-dot role-dot--dept"></span>
                        <span class="role-label">User Dept.</span>
                    </div>
                    <div class="role-bar-track">
                        <div class="role-bar role-bar--dept" data-stat="pct-dept" style="width: 0%"></div>
                    </div>
                    <span class="role-count" data-stat="count-dept">—</span>
                </div>
                <div class="role-row">
                    <div class="role-row-left">
                        <span class="role-dot role-dot--admin"></span>
                        <span class="role-label">Admin Outsource</span>
                    </div>
                    <div class="role-bar-track">
                        <div class="role-bar role-bar--admin" data-stat="pct-admin" style="width: 0%"></div>
                    </div>
                    <span class="role-count" data-stat="count-admin">—</span>
                </div>
                <div class="role-row">
                    <div class="role-row-left">
                        <span class="role-dot role-dot--super"></span>
                        <span class="role-label">Super Admin</span>
                    </div>
                    <div class="role-bar-track">
                        <div class="role-bar role-bar--super" data-stat="pct-super" style="width: 0%"></div>
                    </div>
                    <span class="role-count" data-stat="count-super">—</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="dash-panel anim-fade-up anim-d3">
            <div class="dash-panel-header">
                <h2 class="dash-panel-title">Akses Cepat</h2>
            </div>
            <div class="dash-panel-body quick-actions">

                <a href="{{ url('/super-admin/akun') }}" class="quick-action-btn quick-action-btn--blue">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM3 20a6 6 0 0 1 12 0v1H3v-1z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Tambah Pengguna</span>
                        <span class="qa-desc">Buat akun baru</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ url('/super-admin/master-data/perusahaan') }}" class="quick-action-btn quick-action-btn--green">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Tambah Perusahaan</span>
                        <span class="qa-desc">Daftarkan vendor baru</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ url('/super-admin/master-data/departemen') }}" class="quick-action-btn quick-action-btn--amber">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Tambah Departemen</span>
                        <span class="qa-desc">Kelola unit kerja</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ url('/super-admin/konfigurasi-area') }}" class="quick-action-btn quick-action-btn--violet">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Atur Radius GPS</span>
                        <span class="qa-desc">Konfigurasi area absensi</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

            </div>
        </div>

    </div>{{-- /dashboard-secondary --}}

    {{-- ── RECENT AUDIT LOG ────────────────────────────────────────────────── --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d4">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Aktivitas Sistem Terbaru</h2>
                <p class="dash-panel-subtitle">10 entri audit log terakhir</p>
            </div>
            <a href="{{ url('/super-admin/audit-log') }}" class="dash-panel-link">
                Lihat semua
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="audit-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aksi</th>
                            <th>Modul</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="audit-table-body">
                        {{--
                            Baris ini adalah PLACEHOLDER loading state.
                            Akan diganti oleh JS saat data AJAX tiba.
                            Endpoint: GET /api/super-admin/audit-log?limit=10
                        --}}
                        <tr class="table-skeleton">
                            <td colspan="5">
                                <div class="skeleton-wrap">
                                    <div class="skeleton-line skeleton-line--short"></div>
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--short"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>{{-- /dashboard-wrap --}}

@endsection

@push('scripts')
    @vite(['resources/js/super-admin/dashboard.js'])
@endpush

@push('scripts')
    @vite(['resources/js/super-admin/notifikasi.js'])
@endpush