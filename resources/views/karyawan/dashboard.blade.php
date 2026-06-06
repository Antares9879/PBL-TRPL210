@extends('layouts.karyawan')

@section('title', 'Dashboard')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Dashboard')

@section('content')
<div class="k-wrap">

    {{-- ══ TODAY CARD — Greeting + Shift Info ══════════════════════════════ --}}
    <div class="k-today-card k-anim-up">

        {{-- Header: nama + avatar --}}
        <div class="k-today-header">
            <div>
                <p class="k-today-greeting">Selamat datang kembali</p>
                <h1 class="k-today-name" id="nama-karyawan">
                    {{--
                        Data nama diisi via AJAX: GET /api/auth/me
                        atau dari auth()->user()->nama_lengkap
                    --}}
                    {{ auth()->user()->nama_lengkap ?? '—' }}
                </h1>
                <p class="k-today-date" id="live-date">—</p>
            </div>
            <div class="k-today-avatar">
                {{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'K', 0, 1)) }}
            </div>
        </div>

        {{-- Shift hari ini --}}
        <div class="k-today-shift" id="today-shift-card">
            <p class="k-today-shift-label">Shift Hari Ini</p>
            {{--
                Data shift dimuat via AJAX: GET /api/karyawan/jadwal?bulan=X&tahun=Y
                JS akan update elemen-elemen ini.
            --}}
            <p class="k-today-shift-name" id="today-shift-name">Memuat jadwal…</p>
            <p class="k-today-shift-time" id="today-shift-time">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <span>—</span>
            </p>
        </div>

        {{-- Absensi hari ini — check-in & check-out --}}
        <div class="k-today-absensi-row">
            <div class="k-today-absensi-chip">
                <p class="k-today-absensi-chip-label">Check-In</p>
                <p class="k-today-absensi-chip-val k-today-absensi-chip-val--empty"
                   id="today-checkin-time">—</p>
            </div>
            <div class="k-today-absensi-chip">
                <p class="k-today-absensi-chip-label">Check-Out</p>
                <p class="k-today-absensi-chip-val k-today-absensi-chip-val--empty"
                   id="today-checkout-time">—</p>
            </div>
            <div class="k-today-absensi-chip">
                <p class="k-today-absensi-chip-label">Menit Kerja</p>
                <p class="k-today-absensi-chip-val k-today-absensi-chip-val--empty"
                   id="today-menit-kerja">—</p>
            </div>
        </div>

    </div>

    {{-- ══ STAT CARDS — Ringkasan Bulan Ini ════════════════════════════════ --}}
    <div class="k-stats-grid k-anim-up k-anim-up-d1">

        {{-- Total Hari Hadir --}}
        <div class="k-stat k-stat--green">
            <div class="k-stat-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div class="k-stat-value">
                <span data-stat="total-hadir">—</span>
                <span class="k-stat-unit">hari</span>
            </div>
            <p class="k-stat-label">Hadir</p>
        </div>

        {{-- Total Menit Lembur --}}
        <div class="k-stat k-stat--violet">
            <div class="k-stat-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div class="k-stat-value">
                <span data-stat="total-lembur">—</span>
                <span class="k-stat-unit">mnt</span>
            </div>
            <p class="k-stat-label">Lembur Resmi</p>
        </div>

        {{-- Hari Izin --}}
        <div class="k-stat k-stat--blue">
            <div class="k-stat-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
            </div>
            <div class="k-stat-value">
                <span data-stat="total-izin">—</span>
                <span class="k-stat-unit">hari</span>
            </div>
            <p class="k-stat-label">Izin</p>
        </div>

        {{-- Menit Telat --}}
        <div class="k-stat k-stat--amber">
            <div class="k-stat-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="k-stat-value">
                <span data-stat="total-telat">—</span>
                <span class="k-stat-unit">mnt</span>
            </div>
            <p class="k-stat-label">Total Telat</p>
        </div>

    </div>

    {{-- ══ PROGRESS MENIT KERJA NORMAL ════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d2">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title">Rekap Menit Kerja Normal</h2>
                <p class="k-card-subtitle" id="progress-periode-label">Bulan ini</p>
            </div>
            <span class="k-card-tag">F06</span>
        </div>
        <div class="k-card-body">
            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:6px;">
                <span style="font-family:var(--font-display);font-size:26px;font-weight:700;
                    color:var(--text-primary);letter-spacing:-0.03em;"
                    data-stat="total-menit-normal">—</span>
                <span style="font-size:12px;color:var(--text-muted);">
                    Target: <span data-stat="target-menit-normal" style="font-weight:600;">—</span> mnt
                </span>
            </div>
            <div class="k-progress-bar">
                <div class="k-progress-fill" id="progress-menit" style="width:0%"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:6px;">
                <span style="font-size:11px;color:var(--text-muted);">
                    <span data-stat="pct-hadir" style="font-weight:600;color:var(--eco-600);">—%</span> tercapai
                </span>
                <span style="font-size:11px;color:var(--text-muted);">
                    Pending validasi: <span data-stat="pending-validasi">—</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ══ STATUS ABSENSI HARI INI + QUICK ACTION ═════════════════════════ --}}
    <div style="display:grid;grid-template-columns:1fr;gap:var(--space-3);"
         class="k-anim-up k-anim-up-d3">

        {{-- Status absensi card --}}
        <div class="k-card">
            <div class="k-card-header">
                <h2 class="k-card-title">Status Absensi Hari Ini</h2>
                <a href="{{ url('/karyawan/absensi') }}"
                   style="font-size:12px;font-weight:500;color:var(--eco-600);text-decoration:none;
                          display:inline-flex;align-items:center;gap:4px;padding:4px 8px;
                          border-radius:6px;transition:background 0.15s;"
                   onmouseover="this.style.background='var(--eco-50)'"
                   onmouseout="this.style.background='transparent'">
                    Absensi
                    <svg width="12" height="12" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="k-card-body" id="absensi-status-body">
                {{-- Diisi JS berdasarkan GET /api/karyawan/riwayat/ringkasan --}}
                <div style="display:flex;gap:var(--space-2);">
                    <div class="k-skel k-skel--block" style="height:64px;flex:1;"></div>
                    <div class="k-skel k-skel--block" style="height:64px;flex:1;"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ PENGAJUAN PENDING — Lembur & Izin ══════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d4">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title">Pengajuan Aktif</h2>
                <p class="k-card-subtitle">Lembur & izin yang sedang diproses</p>
            </div>
        </div>
        <div id="pengajuan-pending-list">
            {{-- Skeleton --}}
            <div class="k-pengajuan-item">
                <div class="k-skel k-skel--block" style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                    <div class="k-skel k-skel--text" style="width:60%;"></div>
                    <div class="k-skel k-skel--text" style="width:40%;"></div>
                </div>
            </div>
            <div class="k-pengajuan-item">
                <div class="k-skel k-skel--block" style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                    <div class="k-skel k-skel--text" style="width:55%;"></div>
                    <div class="k-skel k-skel--text" style="width:35%;"></div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/dashboard.js'])
@endpush

@push('scripts')
    @vite(['resources/js/karyawan/notifikasi.js'])
@endpush