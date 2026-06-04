@extends('layouts.app')

@section('title', 'Audit Log')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Audit Log')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Audit Log</h1>
            <p class="page-subtitle">Riwayat seluruh aktivitas sistem — login, logout, check-in, check-out, approve, reject, create, update, activate, deactivate</p>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="dash-panel dash-panel--full anim-fade-up">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Filter & Pencarian</h2>
            <button type="button" id="btn-reset-filter" class="btn btn--sm btn--neutral">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Filter
            </button>
        </div>
        <div class="dash-panel-body">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="filter-search" class="form-label">Pencarian</label>
                    <input type="text" id="filter-search" class="form-input" placeholder="Cari nama pengguna atau catatan...">
                </div>
                
                <div class="form-group">
                    <label for="filter-tanggal-dari" class="form-label">Tanggal Dari</label>
                    <input type="date" id="filter-tanggal-dari" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="filter-tanggal-sampai" class="form-label">Tanggal Sampai</label>
                    <input type="date" id="filter-tanggal-sampai" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="filter-aksi" class="form-label">Aksi</label>
                    <select id="filter-aksi" class="form-input">
                        <option value="">Semua Aksi</option>
                        <optgroup label="Autentikasi">
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                        </optgroup>
                        <optgroup label="Absensi">
                            <option value="create" data-jenis="absensi">Check In (Absensi)</option>
                            <option value="update" data-jenis="absensi">Check Out (Absensi)</option>
                        </optgroup>
                        <optgroup label="Validasi">
                            <option value="approve">Approve</option>
                            <option value="reject">Reject</option>
                        </optgroup>
                        <optgroup label="Data Management">
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="upload">Upload</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter-role" class="form-label">Role</label>
                    <select id="filter-role" class="form-input">
                        <option value="">Semua Role</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="admin_outsource">Admin Outsource</option>
                        <option value="user_departemen">User Departemen</option>
                        <option value="hr">HR</option>
                        <option value="karyawan">Karyawan</option>
                        <option value="sistem">Sistem</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filter-jenis" class="form-label">Jenis Data</label>
                    <select id="filter-jenis" class="form-input">
                        <option value="">Semua Jenis</option>
                        <option value="auth">Autentikasi</option>
                        <option value="absensi">Absensi</option>
                        <option value="lembur">Lembur</option>
                        <option value="izin">Izin</option>
                        <option value="planning">Planning Kerja</option>
                        <option value="akun">Akun</option>
                        <option value="master_data">Master Data</option>
                        <option value="konfigurasi">Konfigurasi</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <button type="button" id="btn-apply-filter" class="btn btn--primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Terapkan Filter
                </button>
                <button type="button" id="btn-export" class="btn btn--neutral">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="dash-panel dash-panel--full anim-fade-up anim-d1">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Log Aktivitas</h2>
                <p class="dash-panel-subtitle" id="subtitle-info">Memuat data...</p>
            </div>
            <div class="form-group" style="margin: 0; min-width: 120px;">
                <select id="per-page" class="form-input form-input--sm">
                    <option value="10">10 / halaman</option>
                    <option value="25" selected>25 / halaman</option>
                    <option value="50">50 / halaman</option>
                    <option value="100">100 / halaman</option>
                </select>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-audit">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Waktu</th>
                            <th>Pengguna</th>
                            <th style="width: 140px;">Role</th>
                            <th style="width: 120px;">Aksi</th>
                            <th style="width: 140px;">Modul</th>
                            <th>Catatan</th>
                            <th style="width: 80px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-audit">
                        <tr class="table-skeleton">
                            <td colspan="7">
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

</div>

{{-- Modal Detail --}}
<div id="modal-detail" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3 class="modal-title">Detail Audit Log</h3>
            <button type="button" class="modal-close" data-close-modal>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body" id="modal-detail-body">
            <div class="skeleton-wrap">
                <div class="skeleton-line"></div>
                <div class="skeleton-line skeleton-line--medium"></div>
                <div class="skeleton-line"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/audit-log.js'])
@endpush

@push('scripts')
    @vite(['resources/js/super-admin/notifikasi.js'])
@endpush
