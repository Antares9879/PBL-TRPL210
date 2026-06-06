@extends('layouts.karyawan')

@section('title', 'Notifikasi')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Notifikasi')

@section('content')
<div class="k-wrap">

    {{-- Page Header --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Notifikasi</h1>
            <p class="k-page-subtitle">Semua notifikasi dan pembaruan aktivitas Anda.</p>
        </div>
        <button id="btn-tandai-semua-halaman" class="k-btn k-btn--outline k-btn--sm">
            Tandai semua dibaca
        </button>
    </div>

    {{-- Tab Filter --}}
    <div class="k-notif-page-tabs" role="tablist">
        <button class="k-notif-page-tab k-notif-page-tab--active"
                data-filter="" role="tab">Semua</button>
        <button class="k-notif-page-tab"
                data-filter="false" role="tab">Belum Dibaca</button>
        <button class="k-notif-page-tab"
                data-filter="true" role="tab">Sudah Dibaca</button>
    </div>

    {{-- List Notifikasi --}}
    <div class="k-card k-anim-up k-anim-up-d1">
        <div id="notif-halaman-list">
            {{-- Skeleton placeholder --}}
            @for ($i = 0; $i < 5; $i++)
                <div class="k-notif-item">
                    <div class="k-skel k-skel--block"
                         style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);">
                    </div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                        <div class="k-skel k-skel--text" style="width:70%;"></div>
                        <div class="k-skel k-skel--text" style="width:40%;"></div>
                    </div>
                </div>
            @endfor
        </div>
        {{-- Paginasi --}}
        <div id="paginasi-notif-halaman"></div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/notifikasi.js'])
@endpush
