@extends('layouts.karyawan')

@section('title', 'Absensi GPS')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Absensi GPS')

{{-- Load Leaflet CSS --}}
@push('styles')
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          crossorigin="anonymous">
    <style>
        /* ── Pulse animasi marker karyawan di peta ── */
        @keyframes pulse-blue {
            0%   { box-shadow: 0 0 0 2px #3b82f6, 0 0  4px rgba(37,99,235,0.4); }
            50%  { box-shadow: 0 0 0 5px #3b82f6, 0 0 14px rgba(37,99,235,0.25); }
            100% { box-shadow: 0 0 0 2px #3b82f6, 0 0  4px rgba(37,99,235,0.4); }
        }

        /* ── Modal peta ── */
        #map-modal-overlay {
            position: fixed; inset: 0; z-index: 9000;
            background: rgba(0,0,0,0.55);
            display: flex; align-items: flex-end;
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease;
        }
        #map-modal-overlay.k-modal--open {
            opacity: 1; pointer-events: auto;
        }
        #map-modal-box {
            width: 100%; max-width: 640px; margin: 0 auto;
            background: var(--surface-card, #fff);
            border-radius: 20px 20px 0 0;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
            max-height: 92vh;
            display: flex; flex-direction: column;
        }
        #map-modal-overlay.k-modal--open #map-modal-box {
            transform: translateY(0);
        }
        #leaflet-map-container {
            width: 100%; height: 320px; flex-shrink: 0;
            background: #e8f4e8;
        }
        .k-map-info-bar {
            padding: 10px 16px;
            font-size: 12px;
            color: var(--text-secondary, #475569);
            background: var(--surface-bg, #f8fafc);
            border-top: 1px solid var(--surface-border, #e2e8f0);
            min-height: 36px;
            display: flex; align-items: center;
        }
        .k-map-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px 10px;
            border-bottom: 1px solid var(--surface-border, #e2e8f0);
            flex-shrink: 0;
        }
        .k-map-title {
            font-size: 15px; font-weight: 700;
            color: var(--text-primary, #0f172a);
        }

        /* ── Modal konfirmasi ── */
        #confirm-modal-overlay {
            position: fixed; inset: 0; z-index: 9100;
            background: rgba(0,0,0,0.55);
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s ease;
        }
        #confirm-modal-overlay.k-modal--open {
            opacity: 1; pointer-events: auto;
        }
        #confirm-modal-box {
            width: 100%; max-width: 400px;
            background: var(--surface-card, #fff);
            border-radius: 16px;
            overflow: hidden;
            transform: scale(0.92);
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        #confirm-modal-overlay.k-modal--open #confirm-modal-box {
            transform: scale(1);
        }
        .k-confirm-header {
            padding: 20px 20px 0;
        }
        .k-confirm-title {
            font-size: 17px; font-weight: 700;
            color: var(--text-primary, #0f172a);
        }
        .k-confirm-subtitle {
            font-size: 12px; color: var(--text-muted, #94a3b8);
            margin-top: 2px;
        }
        .k-confirm-body { padding: 16px 20px; }
        .k-confirm-info-list {
            display: flex; flex-direction: column; gap: 8px;
            margin-bottom: 12px;
        }
        .k-confirm-info-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-secondary, #475569);
            background: var(--surface-bg, #f8fafc);
            padding: 8px 12px; border-radius: 8px;
        }
        .k-confirm-info-row svg { width: 15px; height: 15px; flex-shrink: 0; }
        .k-confirm-info-row--success { color: #15803d; background: #f0fdf4; }
        .k-confirm-info-row--danger  { color: #dc2626; background: #fef2f2; }
        .k-confirm-info-row--warning { color: #92400e; background: #fffbeb; }
        .k-confirm-note {
            font-size: 11px; color: var(--text-muted, #94a3b8);
            line-height: 1.5;
        }
        .k-confirm-footer {
            display: flex; gap: 10px;
            padding: 0 20px 20px;
        }
        .k-btn--checkout { background: #ea580c; }
        .k-btn--checkout:hover { background: #c2410c; }
    </style>
@endpush

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
        {{-- Tombol Lihat Peta — selalu tersedia --}}
        <button class="k-btn k-btn--outline k-btn--sm"
                id="btn-lihat-peta"
                style="gap:5px;"
                aria-label="Lihat peta radius area absensi">
            <svg width="14" height="14" fill="none" stroke="currentColor"
                 stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13l6-3m-6 3V7m6 13l4.553 2.276A1 1 0 0 0 21 21.382V10.618a1 1 0 0 0-.553-.894L15 7m0 13V7m0 0L9 4"/>
            </svg>
            Lihat Peta
        </button>
    </div>

    {{-- ══ GPS STATUS PANEL ═════════════════════════════════════════════════ --}}
    <div class="k-gps-panel k-anim-up k-anim-up-d1" id="gps-panel">
        <div class="k-gps-status">
            <span class="k-gps-dot k-gps-dot--pending" id="gps-dot"></span>
            <div class="k-gps-info">
                <p class="k-gps-label" id="gps-status-text">Mengakses lokasi GPS…</p>
                <p class="k-gps-coords" id="gps-coords">—</p>
            </div>
            <button class="k-icon-btn k-icon-btn--view" id="btn-refresh-gps"
                    title="Perbarui lokasi GPS"
                    aria-label="Perbarui lokasi GPS">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        {{-- Progress bar jarak ke area --}}
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
    <div class="k-anim-up k-anim-up-d2" id="absensi-today-panel">

        <div id="absensi-today-skeleton" style="display:flex;gap:var(--space-2);">
            <div class="k-skel k-skel--block" style="height:72px;flex:1;border-radius:var(--radius-lg);"></div>
            <div class="k-skel k-skel--block" style="height:72px;flex:1;border-radius:var(--radius-lg);"></div>
        </div>

        <div id="absensi-today-content" style="display:none;flex-direction:column;gap:var(--space-2);">

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
                <span class="k-telat-badge" id="telat-badge" style="display:none;">
                    <svg width="10" height="10" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    <span id="telat-menit">0 mnt</span>
                </span>
            </div>

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

        </div>

    </div>

    {{-- ══ TOMBOL CHECK-IN / CHECK-OUT ════════════════════════════════════ --}}
    <div class="k-absensi-center k-anim-up k-anim-up-d3">

        <div class="k-absensi-btn-wrap" id="absensi-btn-container">
            <div class="k-absensi-ring" id="ring-1" style="display:none;"></div>
            <div class="k-absensi-ring" id="ring-2" style="display:none;"></div>
            <div class="k-absensi-ring" id="ring-3" style="display:none;"></div>

            <button class="k-absensi-btn k-absensi-btn--checkin k-absensi-btn--disabled"
                    id="btn-checkin"
                    aria-label="Check-In"
                    disabled>
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
                </svg>
                <span class="k-absensi-btn-label">CHECK-IN</span>
                <span class="k-absensi-btn-sub" id="btn-sub-text">Menunggu GPS…</span>
            </button>
        </div>

        <div style="text-align:center;max-width:280px;">
            <p style="font-size:13px;color:var(--text-muted);line-height:1.6;"
               id="absensi-info-text">
                Pastikan GPS aktif dan Anda berada dalam radius area PT Ecogreen Oleochemicals.
            </p>
        </div>

    </div>

    {{-- ══ NOTIFIKASI PENDING LEMBUR ══════════════════════════════════════ --}}
    <div id="pending-lembur-banner" style="display:none;" class="k-anim-up">
        <div class="k-alert k-alert--warning">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <div>
                <p style="font-weight:600;margin-bottom:2px;">Kelebihan Waktu Kerja Terdeteksi!</p>
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

    {{-- ══ RIWAYAT ABSENSI TERAKHIR ════════════════════════════════════════ --}}
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


{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: PREVIEW PETA LEAFLET
     Menampilkan lingkaran radius area absensi + marker posisi karyawan real-time.
══════════════════════════════════════════════════════════════════════════ --}}
<div id="map-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="map-modal-title">
    <div id="map-modal-box">

        <div class="k-map-header">
            <div>
                <h3 class="k-map-title" id="map-modal-title">Peta Radius Absensi</h3>
                <p style="font-size:11px;color:var(--text-muted);margin-top:1px;">
                    Area hijau = radius absensi · Titik biru = posisi Anda
                </p>
            </div>
            <button class="k-icon-btn" id="btn-close-map-modal" aria-label="Tutup peta">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Container peta Leaflet --}}
        <div id="leaflet-map-container" aria-label="Peta lokasi absensi"></div>

        {{-- Info bar jarak --}}
        <div class="k-map-info-bar" id="map-gps-info">
            Memuat data lokasi…
        </div>

    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: KONFIRMASI CHECK-IN / CHECK-OUT
     Menampilkan ringkasan lokasi sebelum mengirim request ke server.
══════════════════════════════════════════════════════════════════════════ --}}
<div id="confirm-modal-overlay" role="dialog" aria-modal="true"
     aria-labelledby="confirm-modal-title">
    <div id="confirm-modal-box">

        <div class="k-confirm-header">
            <h3 class="k-confirm-title" id="confirm-modal-title">Konfirmasi Absensi</h3>
            <p class="k-confirm-subtitle" id="confirm-modal-subtitle">—</p>
        </div>

        <div class="k-confirm-body">
            <div id="confirm-modal-body">
                {{-- Diisi JS --}}
            </div>
        </div>

        <div class="k-confirm-footer">
            <button type="button" class="k-btn k-btn--ghost k-btn--block"
                    id="btn-cancel-confirm">
                Batal
            </button>
            <button type="button" class="k-btn k-btn--primary k-btn--block"
                    id="btn-proceed-absensi">
                Lanjutkan
            </button>
        </div>

        <button class="k-modal-close" id="btn-close-confirm-modal"
                style="position:absolute;top:14px;right:14px;"
                aria-label="Tutup">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

    </div>
</div>

@endsection

@push('scripts')
    {{-- Leaflet JS harus di-load sebelum absensi.js --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            crossorigin="anonymous"></script>
    @vite(['resources/js/karyawan/absensi.js'])
@endpush
