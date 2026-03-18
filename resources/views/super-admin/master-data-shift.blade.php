@extends('layouts.app')

@section('title', 'Shift & Waktu')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Shift & Waktu')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Shift & Waktu</h1>
            <p class="page-subtitle">Kelola definisi shift kerja — jam masuk dan jam pulang (F18)</p>
        </div>
        <button class="btn-primary" id="btn-tambah-shift">+ Tambah Shift</button>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Daftar Shift</h2>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-shift">
                    <thead>
                        <tr>
                            <th>Nama Shift</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Durasi Normal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-shift">
                        <tr class="table-skeleton">
                            <td colspan="6">
                                <div class="skeleton-wrap">
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
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/master-data.js'])
@endpush
