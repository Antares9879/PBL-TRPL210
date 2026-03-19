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
                Persetujuan pengajuan izin dan verifikasi dokumen pendukung karyawan (F04–F05)
            </p>
        </div>
    </div>

    {{-- Dua panel: izin pending + dokumen perlu verifikasi --}}
    <div class="dashboard-secondary">

        {{-- Panel kiri: daftar pengajuan izin --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Pengajuan Izin</h2>
                    <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/izin</code></p>
                </div>
                <span class="dash-panel-tag">F04</span>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Jenis Izin</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 6; $i++)
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="skeleton-line" style="width:30px;height:30px;border-radius:7px;flex-shrink:0;"></div>
                                        <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                                            <div class="skeleton-line" style="width:90px;height:10px;"></div>
                                            <div class="skeleton-line skeleton-line--short" style="height:8px;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:72px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:55px;height:20px;border-radius:999px;"></div></td>
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

        {{-- Panel kanan: dokumen perlu verifikasi --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Verifikasi Dokumen</h2>
                    <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/izin/dokumen</code></p>
                </div>
                <span class="dash-panel-tag">F05</span>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Jenis Izin</th>
                                <th>Dokumen</th>
                                <th>Status Dok.</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 6; $i++)
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="skeleton-line" style="width:30px;height:30px;border-radius:7px;flex-shrink:0;"></div>
                                        <div class="skeleton-line" style="width:90px;height:10px;"></div>
                                    </div>
                                </td>
                                <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:100px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:70px;height:20px;border-radius:999px;"></div></td>
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

</div>
@endsection