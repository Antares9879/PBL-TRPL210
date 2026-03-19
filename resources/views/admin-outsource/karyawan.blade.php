@extends('layouts.admin')

@section('title', 'Data Karyawan')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Data Karyawan')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Data Karyawan</h1>
            <p class="page-subtitle">
                Kelola karyawan outsource — tambah, edit, aktif/nonaktif, reset password (F07)
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Karyawan</h2>
                <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/karyawan</code></p>
            </div>
            <span class="dash-panel-tag">F07</span>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>NIK</th>
                            <th>Posisi</th>
                            <th>Departemen</th>
                            <th>Tgl. Bergabung</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 6; $i++)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="skeleton-line" style="width:32px;height:32px;border-radius:8px;flex-shrink:0;"></div>
                                    <div style="display:flex;flex-direction:column;gap:5px;flex:1;">
                                        <div class="skeleton-line" style="width:120px;height:10px;"></div>
                                        <div class="skeleton-line skeleton-line--short" style="height:8px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:100px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:90px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                            <td><div class="skeleton-line" style="width:50px;height:20px;border-radius:999px;"></div></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <div class="skeleton-line" style="width:28px;height:28px;border-radius:7px;"></div>
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