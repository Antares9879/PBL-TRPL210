@extends('layouts.admin')

@section('title', 'Kelola Izin')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Kelola Izin')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Izin</h1>
            <p class="page-subtitle">
                Persetujuan pengajuan izin dan verifikasi dokumen pendukung karyawan outsource.
                Klik <strong>Lihat Dokumen</strong> untuk preview dan validasi langsung dari popup.
            </p>
        </div>
    </div>

    {{-- Satu panel terpadu: tabel izin + kolom dokumen --}}
    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Pengajuan Izin</h2>
                <p class="dash-panel-subtitle">
                    Data dimuat via
                    <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">
                        GET /api/admin/validasi-izin
                    </code>
                </p>
            </div>
            <span class="dash-panel-tag">F04 · F05</span>
        </div>

        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jenis Izin</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Dokumen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-izin">
                        <tr>
                            <td colspan="7">
                                <div class="skeleton-wrap" style="padding:8px 0;">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                    <div class="skeleton-line skeleton-line--short"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="paginasi-izin"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/kelola-izin.js'])
@endpush

@push('scripts')
    @vite(['resources/js/admin-outsource/notifikasi.js'])
@endpush
