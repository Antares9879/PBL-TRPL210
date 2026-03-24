@extends('layouts.karyawan')

@section('title', 'Absensi GPS')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Absensi GPS')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Absensi GPS</h1>
            <p class="k-page-subtitle">
                Tap tombol di bawah untuk mencatat kehadiran Anda.
            </p>
        </div>
        <span class="k-card-tag">F01</span>
    </div>

    {{-- ══ GPS STATUS PANEL ═════════════════════════════════════════════════ --}}
    <div class="k-gps-panel k-anim-up k-anim-up-d1" id="gps-panel">
        <div class="k-gps-status">
            {{-- Dot status: pending/ok/error -- diupdate JS --}}
            <span class="k-gps-dot k-gps-dot--pending" id="gps-dot"></span>
            <div class="k-gps-info">
                <p class="k-gps-label" id="gps-status-text">Mengakses lokasi GPS…</p>
                <p class="k-gps-coords" id="gps-coords">—</p>
            </div>
            {{-- Tombol refresh GPS --}}
            <button class="k-icon-btn k-icon-btn--view" id="btn-refresh-gps"
                    title="Perbarui lokasi GPS"
                    aria-label="Perbarui lokasi GPS">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        {{-- Jarak ke area PT Ecogreen --}}
        <div class="k-gps-distance" id="gps-distance-row" style="display:none;">
            <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;flex-shrink:0;">
                Jarak ke area
            </span>
            <div class="k-gps-distance-bar-wrap">
                <div class="k-gps-distance-bar" id="gps-distance-bar" style="width:0%"></div>
            </div>
            <span class="k-gps-distance-label" id="gps-distance-text">— m</span>
        </div>
    </div>

    {{-- ══ STATUS ABSENSI HARI INI ══════════════════════════════════════════ --}}
    {{--
        Diisi JS setelah GET /api/karyawan/riwayat?bulan=X&tahun=Y
        Kondisi:
        1. Belum check-in: tampilkan chip kosong (default Blade)
        2. Sudah check-in, belum check-out: tampilkan waktu check-in + badge telat jika ada
        3. Sudah check-out: tampilkan keduanya + menit kerja
    --}}
    <div class="k-anim-up k-anim-up-d2" id="absensi-today-panel">

        {{-- Skeleton sementara data dimuat --}}
        <div id="absensi-today-skeleton" style="display:flex;gap:var(--space-2);">
            <div class="k-skel k-skel--block" style="height:72px;flex:1;border-radius:var(--radius-lg);"></div>
            <div class="k-skel k-skel--block" style="height:72px;flex:1;border-radius:var(--radius-lg);"></div>
        </div>

        {{-- Konten aktual — disembunyikan awalnya, diisi JS --}}
        <div id="absensi-today-content" style="display:none;flex-direction:column;gap:var(--space-2);">

            {{-- Status check-in --}}
            <div class="k-absensi-status-card" id="checkin-card" style="display:none;">
                <div class="k-absensi-status-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
                    </svg>
                </div>
                <div class="k-absensi-status-body">
                    <p class="k-absensi-status-title">Check-In</p>
                    <p class="k-absensi-status-meta" id="checkin-time">—</p>
                    <p class="k-absensi-status-sub" id="checkin-jadwal">Jadwal: —</p>
                </div>
                {{-- Badge telat — ditampilkan JS jika menit_telat > 0 --}}
                <span class="k-telat-badge" id="telat-badge" style="display:none;">
                    <svg width="10" height="10" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    <span id="telat-menit">0 mnt</span>
                </span>
            </div>

            {{-- Status check-out --}}
            <div class="k-absensi-status-card k-absensi-status-card--checkout"
                 id="checkout-card" style="display:none;">
                <div class="k-absensi-status-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
                    </svg>
                </div>
                <div class="k-absensi-status-body">
                    <p class="k-absensi-status-title">Check-Out</p>
                    <p class="k-absensi-status-meta" id="checkout-time">—</p>
                    <p class="k-absensi-status-sub" id="menit-kerja-info">Menit kerja normal: —</p>
                </div>
            </div>

        </div>{{-- /absensi-today-content --}}

    </div>{{-- /absensi-today-panel --}}

    {{-- ══ TOMBOL CHECK-IN / CHECK-OUT ════════════════════════════════════ --}}
    <div class="k-absensi-center k-anim-up k-anim-up-d3">

        {{-- Ring animasi GPS aktif --}}
        <div class="k-absensi-btn-wrap" id="absensi-btn-container">
            <div class="k-absensi-ring" id="absensi-ring-1" style="display:none;"></div>
            <div class="k-absensi-ring" id="absensi-ring-2" style="display:none;"></div>
            <div class="k-absensi-ring" id="absensi-ring-3" style="display:none;"></div>

            {{-- Tombol check-in (default) --}}
            <button class="k-absensi-btn k-absensi-btn--checkin k-absensi-btn--disabled"
                    id="btn-checkin"
                    aria-label="Check-In"
                    disabled>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
                </svg>
                <span class="k-absensi-btn-label">CHECK-IN</span>
                <span class="k-absensi-btn-sub">Menunggu GPS…</span>
            </button>

        </div>{{-- /absensi-btn-container --}}

        {{-- Info di bawah tombol --}}
        <div style="text-align:center;max-width:280px;">
            <p style="font-size:13px;color:var(--text-muted);line-height:1.6;"
               id="absensi-info-text">
                Pastikan GPS aktif dan Anda berada dalam radius area PT Ecogreen Oleochemicals.
            </p>
        </div>

    </div>{{-- /k-absensi-center --}}

    {{-- ══ NOTIFIKASI PENDING LEMBUR (muncul setelah check-out) ══════════ --}}
    {{--
        Diisi JS jika response check-out: pending_lembur = true
        Endpoint: POST /api/karyawan/check-out → data.pending_lembur
    --}}
    <div id="pending-lembur-banner" style="display:none;" class="k-anim-up">
        <div class="k-alert k-alert--warning">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <div>
                <p style="font-weight:600;margin-bottom:2px;">
                    Kelebihan Waktu Kerja Terdeteksi!
                </p>
                <p id="pending-lembur-text">
                    Anda memiliki kelebihan waktu kerja. Ajukan form lembur paling lambat H+1.
                </p>
                <a href="{{ url('/karyawan/lembur') }}"
                   style="display:inline-flex;align-items:center;gap:4px;margin-top:8px;
                          font-weight:600;color:#92400e;font-size:12px;text-decoration:none;">
                    Ajukan Lembur Sekarang
                    <svg width="12" height="12" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    {{-- ══ RIWAYAT ABSENSI TERAKHIR (5 baris) ════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d4">
        <div class="k-card-header">
            <div>
                <h2 class="k-card-title">Riwayat Terkini</h2>
                <p class="k-card-subtitle">5 absensi terakhir</p>
            </div>
            <a href="{{ url('/karyawan/riwayat') }}"
               style="font-size:12px;font-weight:500;color:var(--eco-600);text-decoration:none;
                      display:inline-flex;align-items:center;gap:4px;padding:4px 8px;
                      border-radius:6px;transition:background 0.15s;"
               onmouseover="this.style.background='var(--eco-50)'"
               onmouseout="this.style.background='transparent'">
                Lihat semua
                <svg width="12" height="12" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="k-card-body k-card-body--tight">
            <div class="k-table-wrap">
                <table class="k-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-riwayat-absensi-mini">
                        {{-- Skeleton rows --}}
                        @for ($i = 0; $i < 3; $i++)
                            <tr>
                                <td><div class="k-skel k-skel--text" style="width:80px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:60px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:45px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:45px;"></div></td>
                                <td><div class="k-skel k-skel--text" style="width:55px;border-radius:999px;"></div></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/absensi.js'])
@endpush