@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Dashboard')

@section('sidebar-role', 'HR Ecogreen')

{{-- ══ SIDEBAR NAV — scope F13–F16 ══════════════════════════════════════════ --}}
@section('sidebar-nav')

    {{-- DASHBOARD --}}
    <div class="nav-section-label">Beranda</div>
    <a href="{{ url('/hr/dashboard') }}"
       class="nav-item {{ request()->is('hr/dashboard') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
            </svg>
        </span>
        <span class="nav-item-label">Dashboard</span>
    </a>

    {{-- F14: VERIFIKASI DOKUMEN --}}
    <div class="nav-section-label">Verifikasi</div>
    <a href="{{ url('/hr/dokumen') }}"
       class="nav-item {{ request()->is('hr/dokumen*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Verifikasi Dokumen</span>
    </a>

    {{-- F15: REKAP ABSENSI --}}
    <div class="nav-section-label">Rekap & Laporan</div>
    <a href="{{ url('/hr/rekap') }}"
       class="nav-item {{ request()->is('hr/rekap*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Rekap Absensi</span>
    </a>

    {{-- F16: AUDIT LOG --}}
    <div class="nav-section-label">Sistem</div>
    <a href="{{ url('/hr/audit') }}"
       class="nav-item {{ request()->is('hr/audit*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <span class="nav-item-label">Audit Log</span>
    </a>

    <div class="nav-section-label">Lainnya</div>
    <a href="{{ url('/hr/notifikasi') }}"
       class="nav-item {{ request()->is('hr/notifikasi*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
            </svg>
        </span>
        <span class="nav-item-label">Notifikasi</span>
    </a>

@endsection

{{-- ══ DASHBOARD CONTENT ════════════════════════════════════════════════════ --}}
@section('content')

<div class="dashboard-wrap">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Dashboard Monitoring</h1>
            <p class="page-subtitle">
                <span id="nama-hr">{{ auth()->user()->nama_lengkap }}</span> — 
                <span id="tanggal-hari-ini">—</span>
            </p>
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

    {{-- ── FILTER PERIODE ─────────────────────────────────────────────────── --}}
    <div class="hr-filter-panel">
        <div class="hr-filter-group">
            <label class="hr-filter-label">Periode</label>
            <div class="hr-filter-row">
                <select id="filter-bulan" class="hr-filter-select">
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
                <select id="filter-tahun" class="hr-filter-select">
                    <!-- Diisi oleh JS -->
                </select>
                <button id="btn-terapkan-filter" class="hr-btn-primary">Terapkan</button>
            </div>
        </div>
    </div>

    {{-- ── STAT CARDS BARIS 1 (4 kartu) ───────────────────────────────────── --}}
    <div class="hr-stats-grid hr-stats-grid--4">
        
        <div id="card-karyawan-aktif" class="hr-stat-card hr-stat-card--green anim-fade-up">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Karyawan Aktif</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="total-karyawan">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Total karyawan outsource</span>
            </div>
        </div>

        <div id="card-total-perusahaan" class="hr-stat-card hr-stat-card--blue anim-fade-up anim-d1">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Perusahaan Outsource</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="total-perusahaan">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Vendor terdaftar</span>
            </div>
        </div>

        <div id="card-total-departemen" class="hr-stat-card hr-stat-card--amber anim-fade-up anim-d2">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Departemen</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="total-departemen">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Unit kerja aktif</span>
            </div>
        </div>

        <div id="card-hadir-hari-ini" class="hr-stat-card hr-stat-card--violet anim-fade-up anim-d3">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Hadir Hari Ini</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="hadir-hari-ini">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Karyawan check-in</span>
            </div>
        </div>

    </div>

    {{-- ── STAT CARDS BARIS 2 (3 kartu) ───────────────────────────────────── --}}
    <div class="hr-stats-grid hr-stats-grid--3">
        
        <div id="card-menunggu-absensi" class="hr-stat-card hr-stat-card--pending anim-fade-up">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Menunggu Validasi Absensi</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="menunggu-absensi">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Perlu ditinjau</span>
            </div>
        </div>

        <div id="card-menunggu-lembur" class="hr-stat-card hr-stat-card--pending anim-fade-up anim-d1">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Menunggu Validasi Lembur</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="menunggu-lembur">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Perlu ditinjau</span>
            </div>
        </div>

        <div id="card-menunggu-izin" class="hr-stat-card hr-stat-card--pending anim-fade-up anim-d2">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Menunggu Validasi Izin</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" data-stat="menunggu-izin">—</span>
            </div>
            <div class="hr-stat-card-footer">
                <span class="label">Perlu ditinjau</span>
            </div>
        </div>

    </div>

    {{-- ── TABEL RINGKASAN PER DEPARTEMEN ─────────────────────────────────── --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d3">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Ringkasan Per Departemen</h2>
                <p class="dash-panel-subtitle">Statistik kehadiran periode terpilih</p>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-ringkasan-departemen">
                    <thead>
                        <tr>
                            <th>Departemen</th>
                            <th>Jumlah Karyawan</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                            <th>Alpa</th>
                            <th>% Kehadiran</th>
                            <th>Total Menit Lembur</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-ringkasan-departemen">
                        <tr class="table-skeleton">
                            <td colspan="7">
                                <div class="skeleton-wrap">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                    <div class="skeleton-line"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── TABEL ABSENSI TERBARU (7 hari terakhir) ────────────────────────── --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d4">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Absensi Terbaru</h2>
                <p class="dash-panel-subtitle">7 hari terakhir</p>
            </div>
        </div>
        <div class="dash-panel-body">
            {{-- Filter departemen & perusahaan --}}
            <div class="hr-filter-row" style="margin-bottom: 16px;">
                <select id="filter-departemen-absensi" class="hr-filter-select">
                    <option value="">Semua Departemen</option>
                </select>
                <select id="filter-perusahaan-absensi" class="hr-filter-select">
                    <option value="">Semua Perusahaan</option>
                </select>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="tabel-absensi-terbaru">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Karyawan</th>
                            <th>Departemen</th>
                            <th>Perusahaan</th>
                            <th>Shift</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status Kehadiran</th>
                            <th>Status Validasi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-absensi-terbaru">
                        <tr class="table-skeleton">
                            <td colspan="9">
                                <div class="skeleton-wrap">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                    <div class="skeleton-line"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="paginasi-absensi" class="hr-paginasi"></div>
        </div>
    </div>

</div>{{-- /dashboard-wrap --}}

@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@push('scripts')
    @vite('resources/js/hr/dashboard.js')
@endpush

@push('scripts')
    @vite('resources/js/hr/notifikasi.js')
@endpush
