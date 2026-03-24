@extends('layouts.karyawan')

@section('title', 'Riwayat Absensi')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Riwayat Absensi')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Riwayat Absensi</h1>
            <p class="k-page-subtitle">
                Rekap kehadiran, menit kerja, dan status validasi per periode.
            </p>
        </div>
        <span class="k-card-tag">F06</span>
    </div>

    {{-- ══ FILTER PERIODE ══════════════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d1">
        <div class="k-card-body k-card-body--tight">
            <div class="k-toolbar">

                {{-- Bulan --}}
                <select id="filter-bulan" class="k-filter-select"
                        aria-label="Filter bulan">
                    @foreach([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April',   5 => 'Mei',      6 => 'Juni',
                        7 => 'Juli',    8 => 'Agustus',  9 => 'September',
                        10 => 'Oktober',11 => 'November',12 => 'Desember'
                    ] as $num => $nama)
                        <option value="{{ $num }}"
                                {{ $num == now()->month ? 'selected' : '' }}>
                            {{ $nama }}
                        </option>
                    @endforeach
                </select>

                {{-- Tahun --}}
                <select id="filter-tahun" class="k-filter-select"
                        aria-label="Filter tahun">
                    @for ($y = now()->year; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}"
                                {{ $y == now()->year ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <button class="k-btn k-btn--outline k-btn--sm"
                        id="btn-load-riwayat"
                        aria-label="Muat data riwayat">
                    <svg fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/>
                    </svg>
                    Muat
                </button>

            </div>
        </div>
    </div>

    {{-- ══ RINGKASAN BULANAN ════════════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d2">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title">Ringkasan</h2>
                <p class="k-card-subtitle" id="ringkasan-periode-label">—</p>
            </div>
        </div>
        <div class="k-card-body">

            <div class="k-summary-grid" id="ringkasan-grid">
                {{-- Skeleton --}}
                @for ($i = 0; $i < 4; $i++)
                    <div class="k-summary-item">
                        <div class="k-skel k-skel--text"
                             style="width:36px;height:24px;margin:0 auto;border-radius:4px;"></div>
                        <div class="k-skel k-skel--text"
                             style="width:60px;height:8px;margin:6px auto 0;"></div>
                    </div>
                @endfor
            </div>

            {{-- Progress menit --}}
            <div style="margin-top:var(--space-4);" id="progress-section">
                <div style="display:flex;justify-content:space-between;
                            align-items:center;margin-bottom:6px;">
                    <span style="font-size:12px;color:var(--text-muted);">
                        Menit Kerja Normal
                    </span>
                    <span style="font-size:12px;font-weight:600;color:var(--text-primary);"
                          id="ringkasan-menit-normal">— mnt</span>
                </div>
                <div class="k-progress-bar">
                    <div class="k-progress-fill" id="ringkasan-progress"
                         style="width:0%;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:5px;">
                    <span style="font-size:11px;color:var(--text-muted);">
                        Lembur resmi: <strong id="ringkasan-lembur"
                        style="color:var(--status-lembur);">— mnt</strong>
                    </span>
                    <span style="font-size:11px;color:var(--text-muted);">
                        Telat: <strong id="ringkasan-telat"
                        style="color:var(--status-telat);">— mnt</strong>
                    </span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══ TABEL RIWAYAT ════════════════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d3">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title">Detail Absensi Harian</h2>
                <p class="k-card-subtitle" id="tabel-subtitle">Data dimuat via
                    <code style="font-size:11px;background:#f8fafc;padding:1px 5px;border-radius:4px;">
                        GET /api/karyawan/riwayat
                    </code>
                </p>
            </div>
        </div>

        <div class="k-card-body k-card-body--tight">
            <div class="k-table-wrap">
                <table class="k-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Normal</th>
                            <th>Telat</th>
                            <th>Lembur</th>
                            <th>Status</th>
                            <th>Validasi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-riwayat">
                        {{-- Skeleton rows --}}
                        @for ($i = 0; $i < 6; $i++)
                            <tr>
                                <td><div class="k-skel k-skel--text" style="width:80px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:60px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:40px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:40px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:50px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:40px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:40px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:50px;border-radius:999px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:55px;border-radius:999px;"></div></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginasi --}}
        <div id="paginasi-riwayat"
             style="padding:0 var(--space-4) var(--space-3);"></div>

    </div>{{-- /tabel card --}}

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/riwayat.js'])
@endpush