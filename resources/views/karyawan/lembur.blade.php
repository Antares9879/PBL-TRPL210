@extends('layouts.karyawan')

@section('title', 'Pengajuan Lembur')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Pengajuan Lembur')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Pengajuan Lembur</h1>
            <p class="k-page-subtitle">
                Ajukan lembur setelah bekerja. Batas pengajuan: H+1 setelah tanggal lembur.
            </p>
        </div>
        <span class="k-card-tag">F03</span>
    </div>

    {{-- ══ INFO ATURAN LEMBUR ═══════════════════════════════════════════════ --}}
    <div class="k-alert k-alert--info k-anim-up k-anim-up-d1">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        <div>
            <p style="font-weight:600;margin-bottom:2px;">Aturan Pengajuan Lembur</p>
            <p style="font-size:12px;line-height:1.6;">
                Lembur dilakukan terlebih dahulu, lalu ajukan form ini <strong>maksimal H+1</strong>
                setelah tanggal lembur. Pengajuan yang melewati batas waktu akan otomatis
                berstatus <em>kadaluarsa</em>. Persetujuan dilakukan oleh User Departemen.
            </p>
            <p style="font-size:12px;line-height:1.6;margin-top:6px;padding-top:6px;border-top:1px solid rgba(59,130,246,0.2);">
                <strong>Minimum kelebihan waktu:</strong> 60 menit (1 jam) untuk dapat mengajukan lembur.
                Kelebihan di bawah 60 menit tetap tercatat untuk transparansi, namun tidak dapat diajukan.
            </p>
        </div>
    </div>

    {{-- ══ TAB: FORM BARU / RIWAYAT ════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d2">

        {{-- Tab header --}}
        <div class="k-tabs" role="tablist">
            <button class="k-tab k-tab--active" id="tab-form-lembur"
                    role="tab" aria-selected="true"
                    aria-controls="panel-form-lembur">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24" style="display:inline-block;margin-right:4px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                Ajukan Baru
            </button>
            <button class="k-tab" id="tab-riwayat-lembur"
                    role="tab" aria-selected="false"
                    aria-controls="panel-riwayat-lembur">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24" style="display:inline-block;margin-right:4px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                Riwayat
                <span id="badge-lembur-pending"
                      style="display:none;margin-left:4px;padding:1px 6px;background:#fef3c7;
                             color:#92400e;border-radius:999px;font-size:10px;font-weight:700;">
                    0
                </span>
            </button>
        </div>

        {{-- ── PANEL: FORM BARU ────────────────────────────────────────── --}}
        <div id="panel-form-lembur" role="tabpanel"
             aria-labelledby="tab-form-lembur"
             class="k-card-body">

            {{-- Alert global form --}}
            <div id="lembur-alert" class="k-alert" style="display:none;margin-bottom:var(--space-4);"></div>

            <form id="form-lembur" class="k-form" novalidate>

                {{-- Tanggal lembur --}}
                <div class="k-form-group">
                    <label for="lembur-tanggal" class="k-label">
                        Tanggal Lembur <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="date"
                           id="lembur-tanggal"
                           name="tanggal_lembur"
                           class="k-input"
                           max="{{ date('Y-m-d') }}"
                           required
                           aria-describedby="err-lembur-tanggal">
                    <span class="k-input-hint">
                        Hanya tanggal yang sudah lewat. Tidak bisa mengajukan lembur untuk tanggal mendatang.
                    </span>
                    <span class="k-field-error" id="err-lembur-tanggal"></span>
                </div>

                {{-- Info absensi pada tanggal terpilih — diisi JS --}}
                <div id="lembur-absensi-info" style="display:none;">
                    <div class="k-alert k-alert--success" style="font-size:12px;">
                        <svg fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        <div>
                            <p>Ditemukan data absensi: Check-in <strong id="lembur-absensi-checkin">—</strong>,
                               Check-out <strong id="lembur-absensi-checkout">—</strong></p>
                            <p style="margin-top:2px;">
                                Kelebihan waktu kerja: <strong id="lembur-menit-kelebihan"
                                style="color:var(--eco-700);">—</strong> menit
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Jam estimasi lembur --}}
                <div class="k-form-row">
                    <div class="k-form-group">
                        <label for="lembur-jam-mulai" class="k-label">
                            Jam Mulai Lembur <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="time"
                               id="lembur-jam-mulai"
                               name="jam_mulai_estimasi"
                               class="k-input"
                               required
                               aria-describedby="err-lembur-jam-mulai">
                        <span class="k-field-error" id="err-lembur-jam-mulai"></span>
                    </div>
                    <div class="k-form-group">
                        <label for="lembur-jam-selesai" class="k-label">
                            Jam Selesai Lembur <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="time"
                               id="lembur-jam-selesai"
                               name="jam_selesai_estimasi"
                               class="k-input"
                               required
                               aria-describedby="err-lembur-jam-selesai">
                        <span class="k-field-error" id="err-lembur-jam-selesai"></span>
                    </div>
                </div>

                {{-- Preview menit lembur yang diajukan --}}
                <div id="lembur-preview-menit"
                     style="display:none;background:var(--eco-50);border:1px solid var(--eco-200);
                            border-radius:var(--radius-md);padding:var(--space-3) var(--space-4);
                            display:none;align-items:center;justify-content:space-between;">
                    <span style="font-size:13px;color:var(--eco-700);">Estimasi menit lembur:</span>
                    <span style="font-family:var(--font-display);font-size:20px;font-weight:700;
                                 color:var(--eco-700);letter-spacing:-0.02em;"
                          id="lembur-preview-angka">— mnt</span>
                </div>

                {{-- Alasan lembur --}}
                <div class="k-form-group">
                    <label for="lembur-alasan" class="k-label">
                        Alasan Lembur <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea id="lembur-alasan"
                              name="alasan_lembur"
                              class="k-textarea"
                              placeholder="Jelaskan alasan lembur secara singkat (min. 10 karakter)…"
                              rows="3"
                              maxlength="500"
                              required
                              aria-describedby="err-lembur-alasan"></textarea>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="k-field-error" id="err-lembur-alasan"></span>
                        <span style="font-size:11px;color:var(--text-muted);margin-left:auto;">
                            <span id="alasan-count">0</span>/500
                        </span>
                    </div>
                </div>

                {{-- Tombol submit --}}
                <div style="display:flex;gap:var(--space-3);padding-top:var(--space-2);">
                    <button type="button"
                            class="k-btn k-btn--ghost"
                            id="btn-reset-lembur">
                        Reset
                    </button>
                    <button type="submit"
                            class="k-btn k-btn--primary k-btn--block"
                            id="btn-submit-lembur">
                        <span id="submit-lembur-text">Ajukan Lembur</span>
                        <span class="k-btn-spinner" id="spinner-lembur"></span>
                    </button>
                </div>

            </form>

        </div>{{-- /panel-form-lembur --}}

        {{-- ── PANEL: RIWAYAT ──────────────────────────────────────────── --}}
        <div id="panel-riwayat-lembur" role="tabpanel"
             aria-labelledby="tab-riwayat-lembur"
             style="display:none;">

            {{-- Filter status --}}
            <div style="padding:var(--space-3) var(--space-4);border-bottom:1px solid var(--surface-border);">
                <select class="k-filter-select" id="filter-status-lembur"
                        style="width:100%;max-width:200px;"
                        aria-label="Filter status lembur">
                    <option value="">Semua Status</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="kadaluarsa">Kadaluarsa</option>
                </select>
            </div>

            <div id="riwayat-lembur-list">
                {{-- Skeleton --}}
                @for ($i = 0; $i < 3; $i++)
                    <div class="k-pengajuan-item">
                        <div class="k-skel k-skel--block"
                             style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                            <div class="k-skel k-skel--text" style="width:65%;"></div>
                            <div class="k-skel k-skel--text" style="width:40%;"></div>
                        </div>
                        <div class="k-skel" style="width:60px;height:20px;border-radius:999px;"></div>
                    </div>
                @endfor
            </div>

            {{-- Paginasi --}}
            <div id="paginasi-lembur" style="padding:0 var(--space-4) var(--space-3);"></div>

        </div>{{-- /panel-riwayat-lembur --}}

    </div>{{-- /k-card --}}

</div>{{-- /k-wrap --}}
@endsection

@push('scripts')
    @vite(['resources/js/karyawan/lembur.js'])
@endpush