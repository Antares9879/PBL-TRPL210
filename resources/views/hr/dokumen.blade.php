@extends('layouts.app')

@section('title', 'Detail Dokumen Izin')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Detail Dokumen')

@section('sidebar-role', 'HR Ecogreen')

@section('sidebar-nav')
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

@section('content')
<div class="dashboard-wrap">
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Verifikasi Dokumen Izin</h1>
            <p class="page-subtitle">Kelola dan verifikasi kelengkapan dokumen pengajuan izin karyawan</p>
        </div>
    </div>

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
                <select id="filter-tahun" class="hr-filter-select"></select>
                <select id="filter-jenis-izin" class="hr-filter-select">
                    <option value="">Semua Jenis Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                    <option value="keperluan_keluarga">Keperluan Keluarga</option>
                    <option value="keperluan_lain">Keperluan Lain</option>
                </select>
                <select id="filter-status-dokumen" class="hr-filter-select">
                    <option value="">Semua Status</option>
                    <option value="sudah_upload">Sudah Upload</option>
                    <option value="lengkap">Lengkap</option>
                    <option value="tidak_lengkap">Tidak Lengkap</option>
                </select>
            </div>
            <div class="hr-filter-row" style="margin-top:8px;">
                <select id="filter-status-validasi-admin" class="hr-filter-select">
                    <option value="">Status Validasi Admin (Semua)</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                </select>
                <select id="filter-perusahaan" class="hr-filter-select">
                    <option value="">Semua Perusahaan</option>
                </select>
                <select id="filter-departemen" class="hr-filter-select">
                    <option value="">Semua Departemen</option>
                </select>
                <input type="text" id="filter-search" class="hr-filter-select" placeholder="Cari nama karyawan..." style="flex:1;">
                <button id="btn-terapkan-filter-detail" class="hr-btn-primary">Terapkan Filter</button>
                <button id="btn-reset-filter" class="hr-btn-outline">Reset</button>
            </div>
        </div>
    </div>

    <div id="tabs-status-dokumen" class="hr-tabs">
        <button class="hr-tab hr-tab--active" data-status="">Semua</button>
        <button class="hr-tab" data-status="sudah_upload">Belum Diverifikasi</button>
        <button class="hr-tab" data-status="lengkap">Lengkap</button>
        <button class="hr-tab" data-status="tidak_lengkap">Tidak Lengkap</button>
    </div>

    <div id="bulk-action-bar" 
        style="display:none; position:fixed; bottom:28px; left:50%; transform:translateX(-50%);
                z-index:200; background:var(--color-background-primary);
                border:0.5px solid var(--color-border-secondary);
                border-radius:12px; padding:10px 16px;
                box-shadow:0 4px 20px rgba(0,0,0,0.10);
                align-items:center; gap:12px;">
        <span style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:500;
                    padding-right:12px;border-right:0.5px solid var(--color-border-tertiary);">
            <span id="selected-count" style="background:var(--color-background-info);
                color:var(--color-text-info);border-radius:999px;
                font-size:11px;padding:2px 8px;font-weight:500;">0</span>
            <span style="color:var(--color-text-secondary);font-size:12px;">dipilih</span>
        </span>
        <button id="btn-bulk-lengkap" style="display:inline-flex;align-items:center;gap:6px;
                font-size:12px;font-weight:500;padding:6px 12px;border-radius:8px;
                border:0.5px solid #C0DD97;background:#EAF3DE;color:#3B6D11;cursor:pointer;">
            Tandai Lengkap
        </button>
        <button id="btn-bulk-tidak-lengkap" style="display:inline-flex;align-items:center;gap:6px;
                font-size:12px;font-weight:500;padding:6px 12px;border-radius:8px;
                border:0.5px solid #F7C1C1;background:#FCEBEB;color:#A32D2D;cursor:pointer;">
            Tidak Lengkap
        </button>
        <div style="width:0.5px;height:20px;background:var(--color-border-tertiary);"></div>
        <button onclick="clearBulkSelection()" 
        title="Batalkan pilihan"
        style="width:24px;height:24px;border-radius:999px;border:0.5px solid var(--color-border-tertiary);
               background:var(--color-background-secondary);color:var(--color-text-secondary);
               cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;">
            ×
        </button>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-pengajuan-izin">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all-header" class="hr-checkbox"></th>
                            <th>Nama Karyawan</th>
                            <th>Departemen</th>
                            <th>Perusahaan</th>
                            <th>Jenis Izin</th>
                            <th>Tanggal Izin</th>
                            <th>Jumlah Hari</th>
                            <th>Dokumen</th>
                            <th>Status Validasi Admin</th>
                            <th>Status Dokumen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-pengajuan-izin">
                        <tr class="table-skeleton">
                            <td colspan="11">
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
</div>

<div id="modal-bulk-konfirmasi" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title" id="modal-bulk-title">Konfirmasi Bulk Action</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-bulk-konfirmasi').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-bulk-body"></div>
            <textarea id="input-bulk-catatan" class="hr-textarea" placeholder="Tuliskan kekurangan dokumen (wajib diisi)..." style="display:none;margin-top:12px;" rows="4"></textarea>
        </div>
        <div class="hr-modal-footer">
            <button id="btn-bulk-batal" class="hr-btn-outline">Batal</button>
            <button id="btn-bulk-submit" class="hr-btn-primary">Konfirmasi</button>
        </div>
    </div>
</div>

<div id="modal-detail-izin" class="hr-modal" style="display:none;">
    <div class="hr-modal-content hr-modal-lg">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Detail Pengajuan Izin</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-detail-izin').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body" id="modal-detail-body"></div>
    </div>
</div>

<div id="lightbox-dokumen" class="hr-lightbox" style="display:none;">
    <div class="hr-lightbox-toolbar">
        <span id="lightbox-nama-file">-</span>
        <div style="display:flex;gap:8px;">
            <button id="btn-lightbox-tab-baru" class="hr-lightbox-btn">Buka di Tab Baru</button>
            <button id="btn-lightbox-close" class="hr-lightbox-btn">×</button>
        </div>
    </div>
    <div id="lightbox-content" class="hr-lightbox-content"></div>
</div>

<div id="modal-konfirmasi-verifikasi" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title" id="modal-verifikasi-title">Konfirmasi Verifikasi</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-konfirmasi-verifikasi').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-konfirmasi-verifikasi-body"></div>
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

@push('scripts')
    @vite('resources/js/hr/notifikasi.js')
@endpush