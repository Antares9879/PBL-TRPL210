@extends('layouts.app')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'User Departemen')
@section('breadcrumb-current', 'Notifikasi')
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
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">
                Semua notifikasi dan pembaruan aktivitas Anda.
            </p>
        </div>
        <button id="btn-tandai-semua-halaman" class="btn-secondary">
            Tandai semua dibaca
        </button>
    </div>

    <!-- Tab Filter -->
    <div class="app-notif-page-tabs" role="tablist">
        <button class="app-notif-page-tab app-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="app-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="app-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    <!-- List -->
    <div class="dash-panel dash-panel--full">
        <div id="notif-halaman-list" class="dash-panel-body">
            @for ($i = 0; $i < 5; $i++)
                <div class="app-notif-item" style="display:flex;gap:12px;padding:12px 20px;">
                    <div class="skeleton-line"
                         style="width:36px;height:36px;flex-shrink:0;border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line skeleton-line--medium"></div>
                    </div>
                </div>
            @endfor
        </div>
        <div id="paginasi-notif-halaman" style="padding:12px 20px;"></div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/user-departemen/notifikasi.js'])
@endpush
