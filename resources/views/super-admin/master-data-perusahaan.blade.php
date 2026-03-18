@extends('layouts.app')

@section('title', 'Perusahaan Outsourcing')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Perusahaan Outsourcing')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Perusahaan Outsourcing</h1>
            <p class="page-subtitle">Kelola data perusahaan penyedia tenaga kerja outsource (F18)</p>
        </div>
        <button class="btn-primary" id="btn-tambah-perusahaan">+ Tambah Perusahaan</button>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Daftar Perusahaan</h2>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-perusahaan">
                    <thead>
                        <tr>
                            <th>Nama Perusahaan</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-perusahaan">
                        <tr class="table-skeleton">
                            <td colspan="5">
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
            <div id="paginasi-perusahaan"></div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/super-admin/master-data.js'])
@endpush
