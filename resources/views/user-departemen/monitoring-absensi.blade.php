@extends('layouts.app')

@section('title', 'Monitoring Absensi')
@section('breadcrumb-parent', 'User Departemen')
@section('breadcrumb-current', 'Monitoring Absensi')
@section('sidebar-role', 'User Departemen')

@section('sidebar-nav')
    @include('user-departemen._sidebar-nav')
@endsection

@push('styles')
    @vite('resources/css/departemen.css')
@endpush

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Monitoring Absensi</h1>
            <p class="page-subtitle">
                Pantau kehadiran karyawan outsource di departemen Anda (read-only).
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Absensi Karyawan</h2>
                <p class="dash-panel-subtitle">
                    Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/departemen/dashboard/absensi</code>
                </p>
            </div>
            <span class="dash-panel-tag">Monitoring</span>
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
                            <th>Menit Normal</th>
                            <th>Menit Telat</th>
                            <th>Status</th>
                            <th>Validasi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-monitoring-absensi">
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
    @vite(['resources/js/user-departemen/monitoring-absensi.js', 'resources/js/user-departemen/notifikasi.js'])
@endpush
