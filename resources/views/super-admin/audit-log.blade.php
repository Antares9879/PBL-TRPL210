@extends('layouts.app')

@section('title', 'Audit Log')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Audit Log')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Audit Log</h1>
            <p class="page-subtitle">Riwayat seluruh aktivitas sistem — approve, reject, create, update</p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Log Aktivitas</h2>
            <p class="dash-panel-subtitle">Data diperbarui real-time</p>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-audit">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Aksi</th>
                            <th>Modul</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-audit">
                        <tr class="table-skeleton">
                            <td colspan="6">
                                <div class="skeleton-wrap">
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                    <div class="skeleton-line skeleton-line--short"></div>
                                    <div class="skeleton-line"></div>
                                    <div class="skeleton-line skeleton-line--medium"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="paginasi-audit"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/audit-log.js'])
@endpush
