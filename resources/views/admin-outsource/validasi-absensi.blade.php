@extends('layouts.admin')

@section('title', 'Validasi Absensi')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Validasi Absensi')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Validasi Absensi</h1>
            <p class="page-subtitle">
                Approve atau reject kehadiran harian karyawan outsource.
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Absensi Pending</h2>
                <p class="dash-panel-subtitle">Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/validasi-absensi</code></p>
            </div>
            <span class="dash-panel-tag">F10</span>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Lokasi Valid</th>
                            <th>Menit Telat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-validasi-absensi">
                        <tr>
                            <td colspan="9">
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
            <div id="paginasi-absensi"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/validasi-absensi.js'])
@endpush

@push('scripts')
    @vite(['resources/js/admin-outsource/notifikasi.js'])
@endpush
