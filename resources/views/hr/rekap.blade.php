@extends('layouts.app')

@section('title', 'Rekap Absensi Bulanan')

@section('breadcrumb-parent', 'HR Ecogreen')
@section('breadcrumb-current', 'Rekap Absensi')

@section('sidebar-role', 'HR Ecogreen')

@section('sidebar-nav')
    <div class="nav-section-label">Beranda</div>
    <a href="{{ url('/hr/dashboard') }}"
       class="nav-item {{ request()->is('hr/dashboard') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/>
            </svg>
        </span>
        <span class="nav-item-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Verifikasi</div>
    <a href="{{ url('/hr/dokumen') }}"
       class="nav-item {{ request()->is('hr/dokumen*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Verifikasi Dokumen</span>
    </a>

    <div class="nav-section-label">Rekap & Laporan</div>
    <a href="{{ url('/hr/rekap') }}"
       class="nav-item {{ request()->is('hr/rekap*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </span>
        <span class="nav-item-label">Rekap Absensi</span>
    </a>

    <div class="nav-section-label">Sistem</div>
    <a href="{{ url('/hr/audit') }}"
       class="nav-item {{ request()->is('hr/audit*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <span class="nav-item-label">Audit Log</span>
    </a>

    <div class="nav-section-label">Lainnya</div>
    <a href="{{ url('/hr/notifikasi') }}"
       class="nav-item {{ request()->is('hr/notifikasi*') ? 'nav-item--active' : '' }}">
        <span class="nav-item-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
            </svg>
        </span>
        <span class="nav-item-label">Notifikasi</span>
    </a>
@endsection

@section('content')
<div class="dashboard-wrap">
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Rekap Absensi Bulanan</h1>
            <p class="page-subtitle">Generate dan kelola rekap absensi per bulan</p>
        </div>
    </div>

    {{-- Filter Tahun --}}
    <div class="hr-filter-panel">
        <div class="hr-filter-group">
            <label class="hr-filter-label">Pilih Tahun</label>
            <div class="hr-filter-row">
                <select id="filter-tahun-rekap" class="hr-filter-select"></select>
                <button id="btn-terapkan-filter-rekap" class="hr-btn-primary">Terapkan</button>
            </div>
        </div>
    </div>

    {{-- Grid 12 Kartu Bulan --}}
    <div id="grid-kartu-rekap" class="hr-grid-kartu-bulan">
        {{-- Diisi oleh JS dengan 12 kartu bulan --}}
    </div>
</div>

{{-- Modal Konfirmasi Generate --}}
<div id="modal-konfirmasi-generate" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Konfirmasi Generate Rekap</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-konfirmasi-generate').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-generate-body"></div>
        </div>
        <div class="hr-modal-footer">
            <button id="btn-batal-generate" class="hr-btn-outline">Batal</button>
            <button id="btn-submit-generate" class="hr-btn-primary">Ya, Generate</button>
        </div>
    </div>
</div>

{{-- Modal Peringatan Dokumen Belum Lengkap --}}
<div id="modal-peringatan-rekap" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Peringatan Dokumen Belum Lengkap</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-peringatan-rekap').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-peringatan-rekap-body"></div>
        </div>
        <div class="hr-modal-footer">
            <button id="btn-batal-peringatan-rekap" class="hr-btn-outline">Batal</button>
            <button id="btn-verifikasi-dulu" class="hr-btn-primary">Verifikasi Dulu</button>
            <button id="btn-lanjutkan-final" class="hr-btn-warning">Tetap Lanjutkan</button>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Final --}}
<div id="modal-konfirmasi-final" class="hr-modal" style="display:none;">
    <div class="hr-modal-content">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">Konfirmasi Tetapkan Final</h3>
            <button class="hr-modal-close" onclick="document.getElementById('modal-konfirmasi-final').style.display='none'">×</button>
        </div>
        <div class="hr-modal-body">
            <div id="modal-final-body"></div>
        </div>
        <div class="hr-modal-footer">
            <button id="btn-batal-final" class="hr-btn-outline">Batal</button>
            <button id="btn-submit-final" class="hr-btn-primary">Ya, Tetapkan Final</button>
        </div>
    </div>
</div>

{{-- Toast Notifikasi --}}
<div id="hr-toast-rekap" class="hr-toast" style="display:none;"></div>

@endsection

@push('styles')
    @vite('resources/css/hr.css')
@endpush

@push('scripts')
    @vite('resources/js/hr/rekap.js')
@endpush

@push('scripts')
    @vite('resources/js/hr/notifikasi.js')
@endpush
