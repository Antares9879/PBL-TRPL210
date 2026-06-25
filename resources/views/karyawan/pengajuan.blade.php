@extends('layouts.karyawan')

@section('title', 'Pengajuan')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Pengajuan')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Pengajuan</h1>
            <p class="k-page-subtitle">Ajukan lembur atau izin tidak masuk.</p>
        </div>
    </div>

    {{-- ══ PILIHAN PENGAJUAN ════════════════════════════════════════════════ --}}
    <div class="k-anim-up k-anim-up-d1"
         style="display:flex;flex-direction:column;gap:var(--space-3);">

        {{-- Lembur --}}
        <a href="{{ url('/karyawan/lembur') }}"
           style="text-decoration:none;"
           aria-label="Pengajuan Lembur">
            <div class="k-card"
                 style="padding:var(--space-5);display:flex;align-items:center;
                        gap:var(--space-4);transition:box-shadow var(--t-fast),
                        transform var(--t-fast);cursor:pointer;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-md)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">

                <div style="width:52px;height:52px;border-radius:var(--radius-lg);
                            background:#f5f3ff;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" fill="none" stroke="#7c3aed"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>

                <div style="flex:1;min-width:0;">
                    <p style="font-family:var(--font-display);font-size:16px;
                               font-weight:700;color:var(--text-primary);
                               letter-spacing:-0.01em;margin-bottom:3px;">
                        Pengajuan Lembur
                    </p>
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.5;">
                        Ajukan kelebihan jam kerja. Batas pengajuan H+1 setelah tanggal lembur.
                    </p>
                </div>

                <svg width="16" height="16" fill="none" stroke="var(--text-muted)"
                     stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        {{-- Izin --}}
        <a href="{{ url('/karyawan/izin') }}"
           style="text-decoration:none;"
           aria-label="Pengajuan Izin">
            <div class="k-card"
                 style="padding:var(--space-5);display:flex;align-items:center;
                        gap:var(--space-4);transition:box-shadow var(--t-fast),
                        transform var(--t-fast);cursor:pointer;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-md)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">

                <div style="width:52px;height:52px;border-radius:var(--radius-lg);
                            background:var(--eco-50);display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" fill="none" stroke="var(--eco-600)"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586
                                 a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19
                                 a2 2 0 0 1-2 2z"/>
                    </svg>
                </div>

                <div style="flex:1;min-width:0;">
                    <p style="font-family:var(--font-display);font-size:16px;
                               font-weight:700;color:var(--text-primary);
                               letter-spacing:-0.01em;margin-bottom:3px;">
                        Pengajuan Izin
                    </p>
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.5;">
                        Ajukan izin tidak masuk untuk satu hari atau beberapa hari sekaligus.
                    </p>
                </div>

                <svg width="16" height="16" fill="none" stroke="var(--text-muted)"
                     stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

    </div>

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/notifikasi.js'])
@endpush