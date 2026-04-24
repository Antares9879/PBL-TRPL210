@extends('layouts.app')

@section('title', 'Validasi Lembur')
@section('breadcrumb-parent', 'User Departemen')
@section('breadcrumb-current', 'Validasi Lembur')
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
            <h1 class="page-title">Validasi Lembur</h1>
            <p class="page-subtitle">
                Approve atau reject pengajuan lembur karyawan outsource di departemen Anda.
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Daftar Pengajuan Lembur</h2>
                <p class="dash-panel-subtitle">
                    Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/departemen/validasi-lembur</code>
                </p>
            </div>
            <span class="dash-panel-tag">F12</span>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Tanggal Lembur</th>
                            <th>Jam Estimasi</th>
                            <th>Menit Diajukan</th>
                            <th>Menit Resmi</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-validasi-lembur">
                        <tr>
                            <td colspan="8">
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
            <div id="paginasi-lembur"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/user-departemen/validasi-lembur.js'])
@endpush
