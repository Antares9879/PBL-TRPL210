@extends('layouts.karyawan')

@section('title', 'Jadwal Kerja')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Jadwal Kerja')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Jadwal Kerja</h1>
            <p class="k-page-subtitle">
                Jadwal shift yang ditetapkan oleh Admin Outsource.
            </p>
        </div>
        {{-- Toggle view: kalender / list --}}
        <div class="k-view-toggle" role="group" aria-label="Pilih tampilan">
            <button class="k-view-toggle-btn k-view-toggle-btn--active"
                    id="btn-view-cal"
                    aria-pressed="true">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                </svg>
                <span>Kalender</span>
            </button>
            <button class="k-view-toggle-btn"
                    id="btn-view-list"
                    aria-pressed="false">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span>List</span>
            </button>
        </div>
    </div>

    {{-- ══ MAIN JADWAL CARD ════════════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d1">

        {{-- Navigasi periode bulan --}}
        <div class="k-period-nav">
            <button class="k-period-btn" id="btn-prev-month"
                    aria-label="Bulan sebelumnya">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <h2 class="k-period-label" id="period-label">— —</h2>

            <button class="k-period-btn" id="btn-next-month"
                    aria-label="Bulan berikutnya">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        {{-- ── VIEW: KALENDER ──────────────────────────────────────────── --}}
        <div id="view-calendar">
            <div class="k-calendar">

                {{-- Header hari dalam seminggu --}}
                <div class="k-calendar-head">
                    @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $hari)
                        <div class="k-calendar-day-name">{{ $hari }}</div>
                    @endforeach
                </div>

                {{-- Grid tanggal — diisi JS --}}
                <div class="k-calendar-grid" id="calendar-grid" role="grid"
                     aria-label="Kalender jadwal kerja">
                    {{-- Skeleton kalender --}}
                    @for ($i = 0; $i < 35; $i++)
                        <div class="k-cal-day">
                            <div class="k-skel k-skel--text"
                                 style="width:18px;height:12px;border-radius:3px;"></div>
                        </div>
                    @endfor
                </div>

            </div>

            {{-- Legend shift --}}
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);
                        padding:0 var(--space-4) var(--space-4);align-items:center;">
                <span style="font-size:11px;color:var(--text-muted);font-weight:600;
                             text-transform:uppercase;letter-spacing:0.07em;">Keterangan:</span>
                @foreach([
                    ['pagi',   'Shift Pagi'],
                    ['siang',  'Shift Siang'],
                    ['malam',  'Shift Malam'],
                    ['normal', 'Shift Normal'],
                    ['libur',  'Libur'],
                ] as [$key, $label])
                    <span style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-secondary);">
                        <span class="k-cal-shift-dot k-cal-shift-dot--{{ $key }}"
                              style="display:inline-block;"></span>
                        {{ $label }}
                    </span>
                @endforeach
            </div>

        </div>{{-- /view-calendar --}}

        {{-- ── VIEW: LIST ──────────────────────────────────────────────── --}}
        <div id="view-list" style="display:none;">
            <div class="k-jadwal-list" id="jadwal-list-container">
                {{-- Skeleton --}}
                @for ($i = 0; $i < 5; $i++)
                    <div class="k-jadwal-item">
                        <div style="display:flex;flex-direction:column;align-items:center;min-width:40px;gap:4px;">
                            <div class="k-skel k-skel--text" style="width:24px;height:8px;"></div>
                            <div class="k-skel k-skel--text" style="width:28px;height:20px;"></div>
                        </div>
                        <div style="width:1px;height:36px;background:var(--surface-border);flex-shrink:0;"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                            <div class="k-skel k-skel--text" style="width:70%;"></div>
                            <div class="k-skel k-skel--text" style="width:45%;"></div>
                        </div>
                        <div class="k-skel" style="width:42px;height:20px;border-radius:999px;"></div>
                    </div>
                @endfor
            </div>
        </div>{{-- /view-list --}}

    </div>{{-- /main card --}}

    {{-- ══ DETAIL HARI TERPILIH ════════════════════════════════════════════ --}}
    {{--
        Ditampilkan ketika user klik salah satu hari di kalender.
        JS akan mengisi detail: nama shift, jam masuk/pulang, status absensi.
    --}}
    <div class="k-card k-anim-up k-anim-up-d2" id="jadwal-detail-card" style="display:none;">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title" id="detail-card-title">Detail Hari</h2>
                <p class="k-card-subtitle" id="detail-card-date">—</p>
            </div>
            <button class="k-icon-btn" id="btn-close-detail"
                    aria-label="Tutup detail">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="k-card-body" id="jadwal-detail-body">
            {{-- Diisi JS --}}
        </div>
    </div>

    {{-- ══ SUMMARY JADWAL BULAN INI ════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d3">
        <div class="k-card-header">
            <h2 class="k-card-title">Ringkasan Bulan Ini</h2>
        </div>
        <div class="k-card-body">
            <div class="k-summary-grid">
                <div class="k-summary-item">
                    <p class="k-summary-val" data-summary="total-hari-kerja">—</p>
                    <p class="k-summary-label">Hari Kerja</p>
                </div>
                <div class="k-summary-item">
                    <p class="k-summary-val" data-summary="total-libur">—</p>
                    <p class="k-summary-label">Hari Libur</p>
                </div>
                <div class="k-summary-item">
                    <p class="k-summary-val" data-summary="shift-pagi">—</p>
                    <p class="k-summary-label">Shift Pagi</p>
                </div>
                <div class="k-summary-item">
                    <p class="k-summary-val" data-summary="shift-malam">—</p>
                    <p class="k-summary-label">Shift Malam</p>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/jadwal.js'])
@endpush