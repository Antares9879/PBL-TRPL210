@extends('layouts.app')

@section('title', 'Departemen')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Departemen')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Departemen</h1>
            <p class="page-subtitle">Kelola data departemen PT Ecogreen sebagai area penugasan (F18)</p>
        </div>
        <button class="btn-primary" id="btn-tambah-departemen">+ Tambah Departemen</button>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Daftar Departemen</h2>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-departemen">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Departemen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-departemen">
                        <tr class="table-skeleton">
                            <td colspan="4">
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
            <div id="paginasi-departemen"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/master-data.js'])
@endpush

@push('scripts')
    @vite(['resources/js/super-admin/notifikasi.js'])
@endpush