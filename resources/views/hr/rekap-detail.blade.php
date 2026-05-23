@extends('layouts.app')

@section('title', 'Detail Rekap Bulanan')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Detail Rekap')

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
@endsection

@section('content')
<div class="dashboard-wrap">
    {{-- Header & Navigasi --}}
    <div class="page-header">
        <div class="page-header-left">
            <a href="/hr/rekap" id="btn-kembali-rekap" class="hr-btn-back">
                ← Kembali ke Daftar Rekap
            </a>
            <h1 class="page-title" id="info-periode-rekap-detail" style="margin-top:12px;">Detail Rekap — ...</h1>
            <p class="page-subtitle">Kelola rekap per karyawan untuk periode terpilih</p>
        </div>
    </div>

    {{-- Panel Aksi Atas --}}
    <div class="hr-panel-aksi-atas">
        <button id="btn-unduh-excel-detail" class="hr-btn-outline">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:4px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Unduh Excel
        </button>
        <button id="btn-generate-ulang-detail" class="hr-btn-primary">Generate Ulang</button>
        <button id="btn-final-semua-detail" class="hr-btn-primary">Tetapkan Semua Final</button>
    </div>

    {{-- Panel Filter --}}
    <div class="hr-filter-panel">
        <div class="hr-filter-group">
            <label class="hr-filter-label">Filter Pencarian</label>
            <div class="hr-filter-row">
                <select id="filter-departemen-detail" class="hr-filter-select">
                    <option value="">Semua Departemen</option>
                </select>
                <select id="filter-perusahaan-detail" class="hr-filter-select">
                    <option value="">Semua Perusahaan</option>
                </select>
                <select id="filter-status-rekap-detail" class="hr-filter-select">
                    <option value="">Semua Status</option>
                    <option value="belum_generate">Belum Generate</option>
                    <option value="draft">Draft</option>
                    <option value="final">Final</option>
                </select>
                <input type="text" id="filter-search-rekap" class="hr-filter-select" placeholder="Cari nama atau nomor karyawan..." style="flex:1;">
                <button id="btn-terapkan-filter-detail" class="hr-btn-primary">Terapkan Filter</button>
                <button id="btn-reset-filter-detail" class="hr-btn-outline">Reset</button>
            </div>
        </div>
    </div>

    {{-- Tabs Cepat Status --}}
    <div id="tabs-status-rekap" class="hr-tabs">
        <button class="hr-tab hr-tab--active" data-status="">Semua</button>
        <button class="hr-tab" data-status="belum_generate">Belum Generate</button>
        <button class="hr-tab" data-status="draft">Draft</button>
        <button class="hr-tab" data-status="final">Final</button>
    </div>

    {{-- Stat Cards Agregat --}}
    <div id="panel-agregat-detail" class="hr-stats-grid hr-stats-grid--4">
        <div class="hr-stat-card hr-stat-card--green">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Total Karyawan</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" id="card-total-karyawan-detail">—</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card--blue">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Total Menit Lembur</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" id="card-total-lembur-detail">—</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card--amber">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Total Hari Hadir</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" id="card-total-hadir-detail">—</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card--violet">
            <div class="hr-stat-card-header">
                <div class="hr-stat-card-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <span class="hr-stat-card-label">Total Hari Alpa</span>
            </div>
            <div class="hr-stat-card-value">
                <span class="nilai" id="card-total-alpa-detail">—</span>
            </div>
        </div>
    </div>

    {{-- Tabel Rekap Per Karyawan --}}
    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-rekap-detail">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all-rekap" class="hr-checkbox"></th>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>No. Karyawan</th>
                            <th>Departemen</th>
                            <th>Perusahaan</th>
                            <th>Hari Kerja</th>
                            <th>Hari Hadir</th>
                            <th>Hari Izin</th>
                            <th>Hari Alpa</th>
                            <th>Menit Normal</th>
                            <th>Menit Lembur</th>
                            <th>Menit Telat</th>
                            <th>Status</th>
                            <th>Digenerate Pada</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rekap-detail">
                        <tr class="table-skeleton">
                            <td colspan="16">
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
            <div id="paginasi-rekap-detail"></div>
        </div>
    </div>
</div>

{{-- Bulk Action Bar Sticky --}}
<div id="bulk-action-bar" class="hr-bulk-action-bar" style="display:none;">
    <span id="bulk-count-label"><span id="bulk-count">0</span> rekap dipilih</span>
    <button id="btn-bulk-final" class="hr-btn-primary">
        Tetapkan Final (<span id="bulk-count-2">0</span>)
    </button>
    <button id="btn-batal-bulk" class="hr-btn-outline">Batalkan Pilihan</button>
</div>

{{-- Modal Detail Rekap Karyawan --}}
<div id="modal-detail-rekap" class="hr-modal" style="display:none;">
    <div class="hr-modal-content hr-modal-lg">
        <div class="hr-modal-header">
            <h3 id="modal-detail-rekap-title" class="hr-modal-title">Detail Rekap</h3>
            <button id="btn-tutup-modal-detail-rekap" class="hr-modal-close">&times;</button>
        </div>
        <div id="modal-detail-rekap-body" class="hr-modal-body"></div>
    </div>
</div>

{{-- Modal Peringatan Dokumen (Halaman B) --}}
<div id="modal-peringatan-rekap-detail" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">⚠️ Peringatan Dokumen Belum Lengkap</h3>
            <button class="hr-modal-close" id="btn-tutup-peringatan-detail">&times;</button>
        </div>
        <div id="modal-peringatan-rekap-detail-body" class="hr-modal-body"></div>
        <div class="hr-modal-footer">
            <button id="btn-batal-peringatan-detail" class="hr-btn-outline">Batal</button>
            <button id="btn-verifikasi-dulu-detail" class="hr-btn-primary">Verifikasi Dulu</button>
            <button id="btn-lanjutkan-final-detail" class="hr-btn-warning">Tetap Lanjutkan</button>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Final Per Baris --}}
<div id="modal-konfirmasi-final-detail" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">✅ Konfirmasi Tetapkan Final</h3>
            <button class="hr-modal-close" id="btn-tutup-final-detail">&times;</button>
        </div>
        <div id="modal-konfirmasi-final-detail-body" class="hr-modal-body"></div>
        <div class="hr-modal-footer">
            <button id="btn-batal-final-detail" class="hr-btn-outline">Batal</button>
            <button id="btn-submit-final-detail" class="hr-btn-primary">Ya, Final</button>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Generate Ulang --}}
<div id="modal-konfirmasi-generate-ulang-detail" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Konfirmasi Generate Ulang</h3>
            <button class="hr-modal-close" id="btn-tutup-generate-ulang-detail">&times;</button>
        </div>
        <div id="modal-konfirmasi-generate-ulang-detail-body" class="hr-modal-body"></div>
        <div class="hr-modal-footer">
            <button id="btn-batal-generate-ulang-detail" class="hr-btn-outline">Batal</button>
            <button id="btn-submit-generate-ulang-detail" class="hr-btn-primary">Ya, Generate Ulang</button>
        </div>
    </div>
</div>

{{-- Modal Progress Bulk Final --}}
<div id="modal-progress-bulk" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-body">
            <p id="modal-progress-label" style="font-weight:500;margin-bottom:12px;">Menetapkan Final...</p>
            <div class="hr-progress-modal">
                <div class="hr-progress-modal-fill" id="progress-bulk-fill" style="width:0%"></div>
            </div>
            <p id="modal-progress-count" style="font-size:12px;color:#64748b;margin-top:8px;"></p>
        </div>
    </div>
</div>

{{-- Toast Notifikasi --}}
<div id="hr-toast-rekap-detail" class="hr-toast" style="display:none;"></div>

@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@push('scripts')
    @vite('resources/js/hr/rekap-detail.js')
@endpush
