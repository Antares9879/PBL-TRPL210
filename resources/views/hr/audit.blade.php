@extends('layouts.app')

@section('title', 'Audit Log Approval')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Audit Log')

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

@endsection

{{-- ══ AUDIT LOG CONTENT ════════════════════════════════════════════════════ --}}
@section('content')

<div class="dashboard-wrap">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Audit Log Approval</h1>
            <p class="page-subtitle">Riwayat seluruh aktivitas validasi absensi, lembur, dan izin karyawan</p>
        </div>
    </div>

    {{-- ── PANEL RINGKASAN (stat cards) ───────────────────────────────────── --}}
    <div id="panel-ringkasan-audit" style="display:none;">
        <div class="hr-filter-panel">
            <div class="hr-filter-group">
                <label class="hr-filter-label">Periode Ringkasan</label>
                <div class="hr-filter-row">
                    <select id="filter-bulan-ringkasan" class="hr-filter-select">
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
                    <select id="filter-tahun-ringkasan" class="hr-filter-select">
                        <!-- Diisi oleh JS -->
                    </select>
                    <button id="btn-load-ringkasan" class="hr-btn-primary">Muat Ringkasan</button>
                    <button id="btn-tutup-ringkasan" class="hr-btn-outline hr-btn-sm">Tutup</button>
                </div>
            </div>
        </div>

        {{-- Stat cards ringkasan --}}
        <div class="hr-stats-grid hr-stats-grid--3" style="margin-bottom: 16px;">
            <div id="card-total-approve" class="hr-ringkasan-card">
                <div style="font-size: 11px; font-weight: 600; color: #14532D; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Approve</div>
                <div style="font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 700; color: #0f172a;">—</div>
            </div>
            <div id="card-total-reject" class="hr-ringkasan-card reject">
                <div style="font-size: 11px; font-weight: 600; color: #7F1D1D; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Reject</div>
                <div style="font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 700; color: #0f172a;">—</div>
            </div>
            <div id="card-total-semua" class="hr-ringkasan-card" style="border-left-color: #3B82F6; background: #EFF6FF;">
                <div style="font-size: 11px; font-weight: 600; color: #1E3A5F; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Semua</div>
                <div style="font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 700; color: #0f172a;">—</div>
            </div>
        </div>

        {{-- Breakdown per jenis data --}}
        <div class="hr-stats-grid hr-stats-grid--3" style="margin-bottom: 16px;">
            <div id="card-stat-absensi"></div>
            <div id="card-stat-lembur"></div>
            <div id="card-stat-izin"></div>
        </div>

        {{-- Breakdown per role --}}
        <div class="dash-panel dash-panel--full" style="margin-bottom: 24px;">
            <div class="dash-panel-header">
                <h2 class="dash-panel-title">Breakdown Per Role</h2>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table" id="tabel-ringkasan-role">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Approve</th>
                                <th>Reject</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-ringkasan-role">
                            <tr><td colspan="4" style="text-align:center;color:#94a3b8;">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── PANEL FILTER TABEL ─────────────────────────────────────────────── --}}
    <div class="dash-panel dash-panel--full anim-fade-up">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Filter & Pencarian</h2>
            <div style="display: flex; gap: 8px;">
                <button type="button" id="btn-toggle-ringkasan" class="btn btn--sm btn--neutral">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Lihat Ringkasan
                </button>
                <button type="button" id="btn-reset-filter-audit" class="btn btn--sm btn--neutral">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filter
                </button>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="filter-search-audit" class="form-label">Pencarian</label>
                    <input type="text" id="filter-search-audit" class="form-input" placeholder="Cari nama pelaku atau catatan...">
                </div>
                
                <div class="form-group">
                    <label for="filter-tanggal-dari-audit" class="form-label">Tanggal Dari</label>
                    <input type="date" id="filter-tanggal-dari-audit" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="filter-tanggal-sampai-audit" class="form-label">Tanggal Sampai</label>
                    <input type="date" id="filter-tanggal-sampai-audit" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="filter-aksi-audit" class="form-label">Aksi</label>
                    <select id="filter-aksi-audit" class="form-input">
                        <option value="">Semua Aksi</option>
                        <option value="approve">Approve</option>
                        <option value="reject">Reject</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter-jenis-data-audit" class="form-label">Jenis Data</label>
                    <select id="filter-jenis-data-audit" class="form-input">
                        <option value="">Semua Jenis Data</option>
                        <option value="absensi">Absensi</option>
                        <option value="lembur">Lembur</option>
                        <option value="izin">Izin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter-role-pelaku-audit" class="form-label">Role Pelaku</label>
                    <select id="filter-role-pelaku-audit" class="form-input">
                        <option value="">Semua Role</option>
                        <option value="admin_outsource">Admin Outsource</option>
                        <option value="user_departemen">User Departemen</option>
                        <option value="hr">HR</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <button type="button" id="btn-terapkan-filter-audit" class="btn btn--primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Terapkan Filter
                </button>
                
                <label class="hr-checkbox-label">
                    <input type="checkbox" id="auto-refresh-audit" class="hr-checkbox">
                    <span>Auto refresh (30 detik)</span>
                </label>
                
                <div id="refresh-indicator" class="hr-refresh-indicator" style="display:none;">
                    <span class="hr-refresh-dot"></span>
                    <span>Auto refresh aktif</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TABS CEPAT AKSI ────────────────────────────────────────────────── --}}
    <div id="tabs-aksi-audit" class="hr-tabs">
        <button class="hr-tab hr-tab--active" data-aksi="">Semua</button>
        <button class="hr-tab" data-aksi="approve">Approve</button>
        <button class="hr-tab" data-aksi="reject">Reject</button>
        <button class="hr-tab" data-aksi="update">Lainnya</button>
    </div>

    {{-- ── TABEL AUDIT LOG ────────────────────────────────────────────────── --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d1">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Log Aktivitas</h2>
                <p class="dash-panel-subtitle" id="subtitle-info-audit">Memuat data...</p>
            </div>
            <div class="form-group" style="margin: 0; min-width: 120px;">
                <select id="per-page-audit" class="form-input form-input--sm">
                    <option value="10">10 / halaman</option>
                    <option value="25" selected>25 / halaman</option>
                    <option value="50">50 / halaman</option>
                </select>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-audit-log">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Waktu</th>
                            <th>Pelaku</th>
                            <th style="width: 140px;">Role</th>
                            <th style="width: 120px;">Aksi</th>
                            <th style="width: 140px;">Jenis Data</th>
                            <th style="width: 100px;">Referensi ID</th>
                            <th>Catatan</th>
                            <th style="width: 80px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-audit-log">
                        <tr class="table-skeleton">
                            <td colspan="8">
                                <div class="skeleton-wrap">
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
            
            {{-- Pagination --}}
            <div id="paginasi-audit" class="pagination-wrap"></div>
        </div>
    </div>

</div>{{-- /dashboard-wrap --}}

{{-- ── MODAL DETAIL AUDIT LOG ─────────────────────────────────────────────── --}}
<div id="modal-detail-audit" class="hr-modal" style="display:none;">
    <div class="hr-modal-content hr-modal-lg">
        <div class="hr-modal-header">
            <h3>Detail Audit Log</h3>
            <button id="btn-tutup-modal-audit" class="hr-modal-close">×</button>
        </div>
        <div id="modal-audit-body" class="hr-modal-body">
            <div class="skeleton-wrap">
                <div class="skeleton-line"></div>
                <div class="skeleton-line skeleton-line--medium"></div>
                <div class="skeleton-line"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@push('scripts')
    @vite('resources/js/hr/audit.js')
@endpush
