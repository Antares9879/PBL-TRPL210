@extends('layouts.admin')

@section('title', 'Riwayat Absensi')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Riwayat Absensi')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Riwayat Absensi</h1>
            <p class="page-subtitle">
                Rekap dan riwayat kehadiran seluruh karyawan outsource (F11)
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Rekap Absensi</h2>
                <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/riwayat-absensi</code></p>
            </div>
            <span class="dash-panel-tag">F11</span>
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
                            <th>Status Kehadiran</th>
                            <th>Status Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 8; $i++)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="skeleton-line" style="width:30px;height:30px;border-radius:7px;flex-shrink:0;"></div>
                                    <div class="skeleton-line" style="width:100px;height:10px;"></div>
                                </div>
                            </td>
                            <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:70px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:48px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:48px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:50px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:50px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:60px;height:20px;border-radius:999px;"></div></td>
                            <td><div class="skeleton-line" style="width:60px;height:20px;border-radius:999px;"></div></td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            {{-- Placeholder paginasi --}}
            <div style="
                display:flex;align-items:center;justify-content:space-between;
                padding-top:16px;margin-top:4px;
                border-top:1px solid var(--surface-border);
            ">
                <div class="skeleton-line" style="width:160px;height:10px;"></div>
                <div style="display:flex;gap:8px;">
                    <div class="skeleton-line" style="width:60px;height:30px;border-radius:8px;"></div>
                    <div class="skeleton-line" style="width:60px;height:30px;border-radius:8px;"></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/validasi-absensi.js'])
@endpush