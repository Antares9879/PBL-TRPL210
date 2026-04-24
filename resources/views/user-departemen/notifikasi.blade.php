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
                Pengajuan lembur baru dan peringatan sistem.
            </p>
        </div>
    </div>

    <div class="dash-panel dash-panel--full">
        <div class="dash-panel-header">
            <div>
                <h2 class="dash-panel-title">Semua Notifikasi</h2>
                <p class="dash-panel-subtitle">
                    Data dimuat via <code style="font-size:11px;background:#f8fafc;padding:1px 6px;border-radius:4px;">GET /api/notifikasi</code>
                </p>
            </div>
            <button id="btn-mark-all-read" class="btn-secondary" style="display:none;">
                Tandai Semua Dibaca
            </button>
        </div>
        <div class="dash-panel-body" style="padding-top:12px;padding-bottom:12px;">
            <div class="notif-list" id="notif-list">
                <div class="notif-item">
                    <div class="notif-icon notif-icon--lembur">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <div class="notif-content">
                        <span class="notif-title">Memuat notifikasi…</span>
                        <span class="notif-meta">Terhubung ke server</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script type="module">
        // Placeholder — akan diimplementasikan jika endpoint notifikasi sudah siap
        console.log('Notifikasi page loaded');
    </script>
@endpush
