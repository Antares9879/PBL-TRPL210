@extends('layouts.app')

@section('title', 'Detail Dokumen Izin')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Detail Dokumen')

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

{{-- ══ CONTENT ═══════════════════════════════════════════════════════════════ --}}
@section('content')

<div class="dashboard-wrap">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Verifikasi Dokumen Izin</h1>
            <p class="page-subtitle">Kelola dan verifikasi kelengkapan dokumen pengajuan izin karyawan</p>
        </div>
    </div>

    {{-- ── PANEL FILTER ───────────────────────────────────────────────────── --}}
    <div class="hr-filter-panel">
        <div class="hr-filter-group">
            <label class="hr-filter-label">Filter Pencarian</label>
            <div class="hr-filter-row">
                <select id="filter-bulan" class="hr-filter-select">
                    <option value="">Semua Bulan</option>
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
                <select id="filter-jenis-izin" class="hr-filter-select">
                    <option value="">Semua Jenis Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                    <option value="keperluan_keluarga">Keperluan Keluarga</option>
                    <option value="keperluan_lain">Keperluan Lain</option>
                </select>
                <select id="filter-status-dokumen" class="hr-filter-select">
                    <option value="">Semua Status</option>
                    <option value="belum_upload">Belum Upload</option>
                    <option value="sudah_upload">Sudah Upload</option>
                    <option value="lengkap">Lengkap</option>
                    <option value="tidak_lengkap">Tidak Lengkap</option>
                </select>
            </div>
            <div class="hr-filter-row" style="margin-top:8px;">
                <select id="filter-perusahaan" class="hr-filter-select">
                    <option value="">Semua Perusahaan</option>
                    <!-- Diisi oleh JS -->
                </select>
                <select id="filter-departemen" class="hr-filter-select">
                    <option value="">Semua Departemen</option>
                    <!-- Diisi oleh JS -->
                </select>
                <input type="text" id="filter-search" class="hr-filter-select" placeholder="Cari nama karyawan..." style="flex:1;">
                <button id="btn-terapkan-filter-detail" class="hr-btn-primary">Terapkan Filter</button>
                <button id="btn-reset-filter" class="hr-btn-outline">Reset</button>
            </div>
        </div>
    </div>

    {{-- ── TABS CEPAT STATUS ──────────────────────────────────────────────── --}}
    <div id="tabs-status-dokumen" class="hr-tabs">
        <button class="hr-tab hr-tab--active" data-status="">Semua</button>
        <button class="hr-tab" data-status="sudah_upload">Belum Diverifikasi</button>
        <button class="hr-tab" data-status="lengkap">Lengkap</button>
        <button class="hr-tab" data-status="tidak_lengkap">Tidak Lengkap</button>
        <button class="hr-tab" data-status="belum_upload">Belum Upload</button>
    </div>

    {{-- ── TABEL PENGAJUAN IZIN ───────────────────────────────────────────── --}}
    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-pengajuan-izin">
                    <thead>
                        <tr>
                            <th>Nama Karyawan</th>
                            <th>Departemen</th>
                            <th>Perusahaan</th>
                            <th>Jenis Izin</th>
                            <th>Tanggal Izin</th>
                            <th>Jumlah Hari</th>
                            <th>Dokumen</th>
                            <th>Status Dokumen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-pengajuan-izin">
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
            <div id="paginasi-pengajuan"></div>
        </div>
    </div>

</div>{{-- /dashboard-wrap --}}

{{-- ══ MODAL DETAIL PENGAJUAN IZIN ══════════════════════════════════════════ --}}
<div id="modal-detail-izin" class="hr-modal" style="display:none;">
    <div class="hr-modal-content hr-modal-lg">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Detail Pengajuan Izin</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-detail-izin').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body" id="modal-detail-body">
            <!-- Diisi oleh JS -->
        </div>
    </div>
</div>

{{-- ══ LIGHTBOX PREVIEW DOKUMEN ═════════════════════════════════════════════ --}}
<div id="lightbox-dokumen" class="hr-lightbox" style="display:none;">
    <div class="hr-lightbox-toolbar">
        <span id="lightbox-nama-file">—</span>
        <div style="display:flex;gap:8px;">
            <button id="btn-lightbox-tab-baru" class="hr-lightbox-btn">Buka di Tab Baru</button>
            <button id="btn-lightbox-close" class="hr-lightbox-btn">×</button>
        </div>
    </div>
    <div id="lightbox-content" class="hr-lightbox-content">
        <!-- Diisi oleh JS -->
    </div>
</div>

{{-- ══ MODAL KONFIRMASI VERIFIKASI ══════════════════════════════════════════ --}}
<div id="modal-konfirmasi-verifikasi" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title" id="modal-verifikasi-title">Konfirmasi Verifikasi</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-konfirmasi-verifikasi').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-konfirmasi-verifikasi-body">
                <!-- Diisi oleh JS -->
            </div>
            <textarea id="input-catatan-dokumen" class="hr-textarea" placeholder="Tuliskan kekurangan dokumen..." style="display:none;margin-top:12px;" rows="4"></textarea>
        </div>
        <div class="hr-modal-footer">
            <button id="btn-batal-verifikasi" class="hr-btn-outline">Batal</button>
            <button id="btn-submit-verifikasi" class="hr-btn-primary">Konfirmasi</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@push('scripts')
    @vite('resources/js/hr/dokumen.js')
@endpush

