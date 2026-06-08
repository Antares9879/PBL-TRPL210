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
                            <th style="width:40px;">
                                <input type="checkbox" id="select-all-checkbox" 
                                    style="width:16px;height:16px;cursor:pointer;" 
                                    title="Pilih semua">
                            </th>
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
                            <td colspan="10">
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
            
            <!-- Bulk Action Buttons -->
            <div id="bulk-action-bar" style="display:none;padding:12px 16px;background:#f8fafc;
                border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:13px;color:#64748b;">
                    <span id="selected-count">0</span> absensi dipilih
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="btn-bulk-approve" class="btn-approve" style="padding:8px 16px;">
                        ✓ Approve Selected
                    </button>
                    <button id="btn-bulk-reject" class="btn-reject" style="padding:8px 16px;">
                        ✕ Reject Selected
                    </button>
                </div>
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
