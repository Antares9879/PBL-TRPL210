@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Pengguna')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-subtitle">Kelola akun seluruh pengguna sistem (F17)</p>
        </div>
        <button class="btn-primary" id="btn-tambah-pengguna">
            + Tambah Pengguna
        </button>
    </div>

    {{-- Tabel data — diisi via AJAX dari /api/super-admin/akun --}}
    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Daftar Pengguna</h2>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-pengguna">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-pengguna">
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
            <div id="paginasi-pengguna"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/akun.js'])
@endpush
