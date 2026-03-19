@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Dashboard')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    {{-- ══════════════════════════════════════════════════════════════════
         COMPANY BANNER — Identitas perusahaan outsource yang dikelola
         Data diambil dari relasi: auth()->user()->adminOutsourceProfile->perusahaan
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="company-banner anim-fade-up">
        <div class="company-banner-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/>
            </svg>
        </div>
        <div class="company-banner-info">
            {{--
                Ganti placeholder setelah autentikasi + relasi DB berjalan:
                {{ auth()->user()->adminOutsourceProfile->perusahaan->nama_perusahaan ?? '—' }}
            --}}
            <span class="company-banner-name" id="nama-perusahaan">
                — Memuat data perusahaan…
            </span>
            <span class="company-banner-meta" id="meta-perusahaan">
                Anda mengelola karyawan dan absensi untuk perusahaan ini
            </span>
        </div>
        <span class="company-banner-tag">Perusahaan Anda</span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">
                Ringkasan status karyawan, absensi, dan pengajuan izin hari ini.
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
         Data placeholder — diisi via AJAX: GET /api/admin/dashboard/stats
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="stats-grid">

        {{-- Card 1: Total Karyawan Aktif — F07 --}}
        <div class="stat-card stat-card--amber anim-fade-up">
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
                <span data-stat="total-karyawan">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral">
                    <span data-stat="karyawan-total">—</span> terdaftar
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 2: Absensi Pending Validasi — F10 --}}
        <div class="stat-card stat-card--rose anim-fade-up anim-d1">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Absensi Pending</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="absensi-pending">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--danger">
                    Menunggu validasi
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
        </div>

        {{-- Card 3: Planning Kerja Bulan Ini — F08–F09 --}}
        <div class="stat-card stat-card--teal anim-fade-up anim-d2">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Planning Bulan Ini</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="planning-status" style="font-size:22px;letter-spacing:-0.01em;">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--neutral">
                    <span data-stat="planning-periode">—</span>
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                </svg>
            </div>
        </div>

        {{-- Card 4: Izin Pending — relasi F04–F05 --}}
        <div class="stat-card stat-card--blue anim-fade-up anim-d3">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                </div>
                <span class="stat-card-label">Izin Pending</span>
            </div>
            <div class="stat-card-value">
                <span data-stat="izin-pending">—</span>
            </div>
            <div class="stat-card-footer">
                <span class="stat-card-badge stat-card-badge--info" id="izin-badge-footer">
                    Menunggu persetujuan
                </span>
            </div>
            <div class="stat-card-bg-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
            </div>
        </div>

    </div>{{-- /stats-grid --}}

    {{-- ══════════════════════════════════════════════════════════════════
         ROW 2: Preview Absensi Hari Ini + Quick Actions
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="dashboard-secondary dashboard-secondary--wide">

        {{-- Panel Kiri (lebar): Absensi Hari Ini — preview 5 baris --}}
        <div class="dash-panel anim-fade-up anim-d2">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Absensi Hari Ini</h2>
                    <p class="dash-panel-subtitle">
                        Absensi masuk yang menunggu validasi Admin Outsource
                    </p>
                </div>
                <a href="{{ url('/admin/validasi-absensi') }}" class="dash-panel-link">
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
                                <th>Menit Telat</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-absensi-preview">
                            {{--
                                Placeholder skeleton — diganti JS setelah endpoint siap.
                                Endpoint: GET /api/admin/validasi-absensi?limit=5&status=menunggu
                            --}}
                            <tr>
                                <td colspan="6">
                                    <div class="skeleton-wrap" style="padding: 8px 0;">
                                        <div class="skeleton-line"></div>
                                        <div class="skeleton-line skeleton-line--medium"></div>
                                        <div class="skeleton-line skeleton-line--short"></div>
                                        <div class="skeleton-line"></div>
                                        <div class="skeleton-line skeleton-line--medium"></div>
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
                <span class="dash-panel-tag">F07–F11</span>
            </div>
            <div class="dash-panel-body quick-actions">

                {{-- F07 — Tambah karyawan + CRUD + akun --}}
                <a href="{{ url('/admin/karyawan') }}" class="quick-action-btn quick-action-btn--amber">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM3 20a6 6 0 0 1 12 0v1H3v-1z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Kelola Karyawan</span>
                        <span class="qa-desc">CRUD + akun + reset password (F07)</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- F08–F09 — Buat / upload planning --}}
                <a href="{{ url('/admin/planning') }}" class="quick-action-btn quick-action-btn--teal">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Buat / Upload Planning</span>
                        <span class="qa-desc">Jadwal kerja bulanan (F08–F09)</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- F10 — Validasi absensi --}}
                <a href="{{ url('/admin/validasi-absensi') }}" class="quick-action-btn quick-action-btn--rose">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Validasi Absensi</span>
                        <span class="qa-desc">Approve / Reject kehadiran (F10)</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- F04–F05 ↔ — Kelola izin + dokumen karyawan --}}
                <a href="{{ url('/admin/kelola-izin') }}" class="quick-action-btn quick-action-btn--violet">
                    <span class="qa-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                        </svg>
                    </span>
                    <span class="qa-text">
                        <span class="qa-label">Kelola Izin & Dokumen</span>
                        <span class="qa-desc">Approve izin + verifikasi dokumen</span>
                    </span>
                    <svg class="qa-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

            </div>
        </div>

    </div>{{-- /row 2 --}}

    {{-- ══════════════════════════════════════════════════════════════════
         ROW 3: Status Planning + Notifikasi
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="dashboard-secondary">

        {{-- Panel Kiri: Status Planning 3 Bulan --}}
        <div class="dash-panel anim-fade-up anim-d3">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Status Planning Kerja</h2>
                    <p class="dash-panel-subtitle">3 periode terkini perusahaan Anda</p>
                </div>
                <a href="{{ url('/admin/planning') }}" class="dash-panel-link">
                    Kelola
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="dash-panel-body">
                {{--
                    Diisi via JS dari: GET /api/admin/planning?limit=3
                    Tampilkan 3 planning terbaru (bulan ini, lalu, 2 bulan lalu)
                --}}
                <div class="planning-list" id="planning-list">

                    {{-- Bulan ini — placeholder --}}
                    <div class="planning-item">
                        <div class="planning-icon planning-icon--aktif">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div class="planning-info">
                            <span class="planning-label" data-stat="planning-label-1">Bulan Ini</span>
                            <span class="planning-meta">
                                Versi <span data-stat="planning-versi-1">—</span> ·
                                <span data-stat="planning-karyawan-1">—</span> karyawan terjadwal
                            </span>
                        </div>
                        <span class="planning-badge planning-badge--aktif" id="planning-badge-1">Aktif</span>
                    </div>

                    {{-- Bulan lalu — placeholder --}}
                    <div class="planning-item">
                        <div class="planning-icon planning-icon--diperbarui">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div class="planning-info">
                            <span class="planning-label" data-stat="planning-label-2">Bulan Lalu</span>
                            <span class="planning-meta">Periode telah selesai</span>
                        </div>
                        <span class="planning-badge planning-badge--diperbarui" id="planning-badge-2">
                            Selesai
                        </span>
                    </div>

                    {{-- Bulan depan — CTA buat planning --}}
                    <div class="planning-item">
                        <div class="planning-icon planning-icon--belum">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div class="planning-info">
                            <span class="planning-label" data-stat="planning-label-3">Bulan Depan</span>
                            <span class="planning-meta" id="planning-meta-3">
                                Belum ada planning — segera buat
                            </span>
                        </div>
                        <a href="{{ url('/admin/planning') }}" class="planning-cta">
                            + Buat
                        </a>
                    </div>

                </div>
            </div>
        </div>

        {{-- Panel Kanan: Notifikasi --}}
        <div class="dash-panel anim-fade-up anim-d4">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Notifikasi</h2>
                    <p class="dash-panel-subtitle">Pengajuan &amp; peringatan terbaru</p>
                </div>
                {{-- Badge jumlah notifikasi belum dibaca --}}
                <span class="dash-panel-tag" id="notif-count-tag" style="display:none;">
                    0 baru
                </span>
            </div>
            <div class="dash-panel-body" style="padding-top:12px;padding-bottom:12px;">

                {{--
                    Tiga jenis notifikasi yang ditampilkan di dashboard:
                    1. Pengajuan izin baru dari karyawan
                    2. Izin yang dokumen pendukungnya belum lengkap/belum diupload
                    3. Planning bulan depan mendekati akhir bulan (reminder)

                    Diisi via JS dari: GET /api/admin/notifikasi?limit=6
                    Jenis filter: izin_baru | dokumen_kurang | planning_reminder
                --}}
                <div class="notif-list" id="notif-list">

                    {{-- Placeholder saat data belum dimuat --}}
                    <div class="notif-item" id="notif-placeholder">
                        <div class="notif-icon notif-icon--izin">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="notif-content">
                            <span class="notif-title">Memuat notifikasi…</span>
                            <span class="notif-meta">
                                Terhubung ke <code style="font-size:10px;background:#f8fafc;padding:1px 4px;border-radius:3px;">GET /api/admin/notifikasi</code>
                            </span>
                        </div>
                    </div>

                    {{-- Template baris notifikasi — diklon & diisi JS saat data tiba:

                    Contoh baris untuk jenis "Pengajuan izin baru":
                    <div class="notif-item">
                        <div class="notif-icon notif-icon--izin">
                            <svg>...</svg>
                        </div>
                        <div class="notif-content">
                            <span class="notif-title">Ahmad Surya mengajukan izin sakit</span>
                            <span class="notif-meta">5 menit yang lalu · Perlu persetujuan</span>
                        </div>
                        <span class="notif-unread-dot"></span>
                    </div>

                    Contoh baris untuk jenis "Dokumen belum lengkap":
                    <div class="notif-item">
                        <div class="notif-icon notif-icon--dokumen">
                            <svg>...</svg>
                        </div>
                        <div class="notif-content">
                            <span class="notif-title">Surat dokter belum diupload — Budi Santoso</span>
                            <span class="notif-meta">Izin 18 Mar · Dokumen tidak lengkap</span>
                        </div>
                        <span class="notif-unread-dot"></span>
                    </div>

                    Contoh baris untuk jenis "Planning reminder":
                    <div class="notif-item">
                        <div class="notif-icon notif-icon--planning">
                            <svg>...</svg>
                        </div>
                        <div class="notif-content">
                            <span class="notif-title">Planning April 2025 belum dibuat</span>
                            <span class="notif-meta">Tersisa 8 hari sebelum bulan baru</span>
                        </div>
                    </div>
                    --}}

                </div>

                {{-- Link ke semua notifikasi --}}
                <div style="
                    padding-top: 14px; margin-top: 4px;
                    border-top: 1px solid var(--surface-border);
                    display: flex; justify-content: center;
                ">
                    <a href="{{ url('/admin/kelola-izin') }}"
                       style="font-size:12px;font-weight:500;color:var(--amber-700);text-decoration:none;
                              display:inline-flex;align-items:center;gap:4px;padding:4px 8px;
                              border-radius:6px;transition:background 0.15s;">
                        Lihat semua pengajuan izin
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

            </div>
        </div>

    </div>{{-- /row 3 --}}

</div>{{-- /dashboard-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/dashboard.js'])
@endpush