@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'User Departemen')
@section('breadcrumb-current', 'Dashboard')
@section('sidebar-role', 'User Departemen')

@section('sidebar-nav')
    @include('user-departemen._sidebar-nav')
@endsection

@push('styles')
    @vite('resources/css/departemen.css')
@endpush

@section('content')
<div class="dashboard-wrap">

    {{-- ══════════════════════════════════════════════════════════════════
         DEPARTEMEN BANNER — Identitas departemen User yang login
         Data diambil dari: auth()->user()->userDepartemenProfile->departemen
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="dept-banner anim-fade-up">
        <div class="dept-banner-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
            </svg>
        </div>
        <div class="dept-banner-info">
            <span class="dept-banner-name" id="nama-departemen">
                {{ auth()->user()->userDepartemenProfile->departemen->nama_departemen ?? '— Memuat data departemen…' }}
            </span>
            <span class="dept-banner-meta" id="meta-departemen">
                Anda mengelola validasi lembur karyawan di departemen ini
            </span>
        </div>
        <span class="dept-banner-tag">Departemen Anda</span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">
                Ringkasan kehadiran karyawan outsource di departemen Anda hari ini.
            </p>
        </div>
        <div class="page-date-badge">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
            <span id="live-date">—</span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         STAT CARDS (4 kartu)
         Data dari: GET /api/departemen/dashboard/ringkasan
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="stats-grid">

        {{-- Card 1: Karyawan Aktif di Departemen --}}
        <div class="stat-card stat-card--teal anim-fade-up">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Karyawan Aktif</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="karyawan-aktif">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral">
                    Di departemen Anda
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 2: Hadir Hari Ini --}}
        <div class="stat-card stat-card--green anim-fade-up anim-d1">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Hadir Hari Ini</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="hadir-hari-ini">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--success">
                    Sudah check-in
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 3: Belum Absen --}}
        <div class="stat-card stat-card--amber anim-fade-up anim-d2">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Belum Absen</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="belum-absen">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--warning">
                    Belum check-in
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 4: Lembur Menunggu Proses --}}
        <div class="stat-card stat-card--rose anim-fade-up anim-d3">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Lembur Pending</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="lembur-pending">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--danger">
                    Menunggu validasi
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
        </div>

    </div>{{-- /stats-grid --}}

    {{-- ══════════════════════════════════════════════════════════════════
         ROW 2: Absensi Hari Ini + Quick Actions
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="dashboard-secondary dashboard-secondary--wide">

        {{-- Panel Kiri: Absensi Hari Ini --}}
        <div class="dash-panel anim-fade-up anim-d2">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Absensi Hari Ini</h2>
                    <p class="dash-panel-subtitle">
                        Kehadiran karyawan departemen Anda (read-only)
                    </p>
                </div>
                <a href="{{ url('/departemen/monitoring-absensi') }}" class="dash-panel-link">
                    Lihat semua
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table" id="tabel-absensi-preview">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Shift</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-absensi-preview">
                            <tr>
                                <td colspan="5">
                                    <div class="skeleton-wrap" style="padding: 8px 0;">
                                        <div class="skeleton-line"></div>
                                        <div class="skeleton-line skeleton-line--medium"></div>
                                        <div class="skeleton-line skeleton-line--short"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Panel Kanan: Quick Actions --}}
        <div class="dash-panel anim-fade-up anim-d3">
            <div class="dash-panel-header">
                <h2 class="dash-panel-title">Akses Cepat</h2>
                <span class="dash-panel-tag">F12</span>
            </div>
            <div class="dash-panel-body quick-actions">

                {{-- F12 — Validasi lembur --}}
                <a href="{{ url('/departemen/validasi-lembur') }}" class="quick-action-btn quick-action-btn--teal">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Validasi Lembur</span>
                        <span class="qa-desc">Approve / Reject pengajuan lembur (F12)</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Monitoring Absensi --}}
                <a href="{{ url('/departemen/monitoring-absensi') }}" class="quick-action-btn quick-action-btn--blue">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Monitoring Absensi</span>
                        <span class="qa-desc">Lihat kehadiran karyawan departemen</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Notifikasi --}}
                <a href="{{ url('/departemen/notifikasi') }}" class="quick-action-btn quick-action-btn--violet">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Notifikasi</span>
                        <span class="qa-desc">Pengajuan lembur baru & peringatan</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

            </div>
        </div>

    </div>{{-- /row 2 --}}

    {{-- ══════════════════════════════════════════════════════════════════
         ROW 3: Statistik Bulan Ini
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d3">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Statistik Bulan Ini</h2>
                <p class="dash-panel-subtitle">
                    Ringkasan kehadiran & lembur departemen Anda bulan <span id="bulan-periode">—</span>
                </p>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                
                {{-- Total Menit Lembur Disetujui --}}
                <div class="stat-summary">
                    <div class="stat-summary-icon stat-summary-icon--teal">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <div class="stat-summary-content">
                        <span class="stat-summary-label">Total Lembur Disetujui</span>
                        <span class="stat-summary-value">
                            <span data-stat="total-menit-lembur">—</span>
                            <span class="stat-summary-unit">menit</span>
                        </span>
                    </div>
                </div>

                {{-- Izin Hari Ini --}}
                <div class="stat-summary">
                    <div class="stat-summary-icon stat-summary-icon--blue">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                        </svg>
                    </div>
                    <div class="stat-summary-content">
                        <span class="stat-summary-label">Izin Hari Ini</span>
                        <span class="stat-summary-value">
                            <span data-stat="izin-hari-ini">—</span>
                            <span class="stat-summary-unit">karyawan</span>
                        </span>
                    </div>
                </div>

                {{-- Alpa Hari Ini --}}
                <div class="stat-summary">
                    <div class="stat-summary-icon stat-summary-icon--rose">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div class="stat-summary-content">
                        <span class="stat-summary-label">Alpa Hari Ini</span>
                        <span class="stat-summary-value">
                            <span data-stat="alpa-hari-ini">—</span>
                            <span class="stat-summary-unit">karyawan</span>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>{{-- /dashboard-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/user-departemen/dashboard.js'])
@endpush
