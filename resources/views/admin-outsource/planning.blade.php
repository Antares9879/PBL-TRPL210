@extends('layouts.admin')

@section('title', 'Planning Kerja')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Planning Kerja')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Planning Kerja</h1>
            <p class="page-subtitle">
                Input dan upload jadwal kerja bulanan karyawan outsource (F08–F09)
            </p>
        </div>
    </div>

    {{-- Dua panel: daftar planning + detail jadwal --}}
    <div class="dashboard-secondary">

        {{-- Panel kiri: daftar planning bulanan --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Riwayat Planning</h2>
                    <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/planning</code></p>
                </div>
                <span class="dash-panel-tag">F08</span>
            </div>
            <div class="dash-panel-body" style="display:flex;flex-direction:column;gap:10px;">
                @for ($i = 0; $i < 4; $i++)
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;background:#f8fafc;border:1px solid var(--surface-border);">
                    <div class="skeleton-line" style="width:34px;height:34px;border-radius:9px;flex-shrink:0;"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                        <div class="skeleton-line" style="width:100px;height:10px;"></div>
                        <div class="skeleton-line skeleton-line--short" style="height:8px;"></div>
                    </div>
                    <div class="skeleton-line" style="width:60px;height:22px;border-radius:999px;"></div>
                </div>
                @endfor
            </div>
        </div>

        {{-- Panel kanan: detail jadwal karyawan --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Detail Jadwal</h2>
                    <p class="dash-panel-subtitle">Menunggu endpoint <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/jadwal-kerja</code></p>
                </div>
                <span class="dash-panel-tag">F09</span>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Tanggal</th>
                                <th>Shift</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td><div class="skeleton-line" style="width:110px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:80px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:70px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:50px;height:10px;"></div></td>
                                <td><div class="skeleton-line" style="width:50px;height:10px;"></div></td>
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