@extends('layouts.app')

@section('title', 'Konfigurasi Area GPS')
@section('breadcrumb-parent', 'Super Admin')
@section('breadcrumb-current', 'Konfigurasi Area')
@section('sidebar-role', 'Super Administrator')

@section('sidebar-nav')
    @include('super-admin._sidebar-nav')
@endsection

@section('content')
<div class="dashboard-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">Konfigurasi Area GPS</h1>
            <p class="page-subtitle">Atur radius dan koordinat pusat area absensi PT Ecogreen (F19)</p>
        </div>
        <button class="btn-primary" id="btn-tambah-area">+ Tambah Area</button>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <h2 class="dash-panel-title">Daftar Area</h2>
        </div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="data-table" id="tabel-area">
                    <thead>
                        <tr>
                            <th>Nama Area</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Radius (m)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-area">
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
    @vite(['resources/js/super-admin/konfigurasi-area.js'])
@endpush
