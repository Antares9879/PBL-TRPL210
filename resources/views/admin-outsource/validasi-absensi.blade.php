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
                Approve atau reject kehadiran harian karyawan outsource (F10)
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Absensi Pending</h2>
                <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/validasi-absensi</code></p>
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
                    <tbody>
                        @for ($i = 0; $i < 7; $i++)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="skeleton-line" style="width:30px;height:30px;border-radius:7px;flex-shrink:0;"></div>
                                    <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                                        <div class="skeleton-line" style="width:100px;height:10px;"></div>
                                        <div class="skeleton-line skeleton-line--short" style="height:8px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:70px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:48px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:48px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:40px;height:20px;border-radius:999px;"></div></td>
                            <td><div class="skeleton-line" style="width:50px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:60px;height:20px;border-radius:999px;"></div></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <div class="skeleton-line" style="width:28px;height:28px;border-radius:7px;"></div>
                                    <div class="skeleton-line" style="width:28px;height:28px;border-radius:7px;"></div>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection