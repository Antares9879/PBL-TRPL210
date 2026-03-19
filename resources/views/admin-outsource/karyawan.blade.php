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
                Kelola karyawan outsource — tambah, edit, aktif/nonaktif, reset password, dan pastikan data karyawan selalu akurat untuk administrasi yang lancar.
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Karyawan</h2>
                <p class="dash-panel-subtitle">Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/admin/karyawan</code></p>
            </div>
            <span class="dash-panel-tag">F07</span>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-karyawan">
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
                    <tbody id="tbody-karyawan">
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
            <div id="paginasi-karyawan"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-outsource/karyawan.js'])
@endpush