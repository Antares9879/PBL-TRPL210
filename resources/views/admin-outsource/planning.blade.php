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
                Input dan upload jadwal kerja bulanan karyawan outsource.
            </p>
        </div>
        {{-- Tombol buat planning — diisi oleh JS via injectToolbars() --}}
    </div>

    {{-- Dua panel: daftar planning + detail jadwal --}}
    <div class="dashboard-secondary">

        {{-- Panel kiri: daftar planning bulanan --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div>
                    <h2 class="dash-panel-title">Riwayat Planning</h2>
                    <p class="dash-panel-subtitle">Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/planning</code></p>
                </div>
                <span class="dash-panel-tag">F08</span>
            </div>
            <div class="dash-panel-body">
                {{-- Container ini diisi oleh planning.js via injectToolbars() --}}
                <div id="planning-list-panel" class="planning-list" style="display:flex;flex-direction:column;gap:8px;">
                    {{-- Skeleton awal --}}
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
                <div id="paginasi-planning"></div>
            </div>
        </div>

        {{-- Panel kanan: detail jadwal karyawan --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                {{-- Header ini diisi oleh planning.js via renderDetailHeader() --}}
                <div id="detail-planning-header">
                    <div class="dash-panel-title">Pilih planning untuk melihat detail jadwal</div>
                </div>
                <span class="dash-panel-tag">F09</span>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Tanggal Kerja</th>
                                <th>Shift</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-jadwal-planning">
                            <tr>
                                <td colspan="5">
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
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/planning.js'])
@endpush