@extends('layouts.karyawan')

@section('title', 'Pengajuan Izin')
@section('breadcrumb-parent', 'Karyawan')
@section('breadcrumb-current', 'Pengajuan Izin')

@section('content')
<div class="k-wrap">

    {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
    <div class="k-page-header k-anim-up">
        <div>
            <h1 class="k-page-title">Pengajuan Izin</h1>
            <p class="k-page-subtitle">
                Ajukan izin tidak masuk dan lampirkan dokumen pendukung jika diperlukan.
            </p>
        </div>
        <span class="k-card-tag">F04 · F05</span>
    </div>

    {{-- ══ TAB: FORM BARU / RIWAYAT ════════════════════════════════════════ --}}
    <div class="k-card k-anim-up k-anim-up-d1">

        {{-- Tab header --}}
        <div class="k-tabs" role="tablist">
            <button class="k-tab k-tab--active" id="tab-form-izin"
                    role="tab" aria-selected="true"
                    aria-controls="panel-form-izin">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24"
                     style="display:inline-block;margin-right:4px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                Ajukan Izin
            </button>
            <button class="k-tab" id="tab-riwayat-izin"
                    role="tab" aria-selected="false"
                    aria-controls="panel-riwayat-izin">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24"
                     style="display:inline-block;margin-right:4px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
                Riwayat Izin
                <span id="badge-izin-pending"
                      style="display:none;margin-left:4px;padding:1px 6px;background:#fef3c7;
                             color:#92400e;border-radius:999px;font-size:10px;font-weight:700;">
                    0
                </span>
            </button>
        </div>

        {{-- ── PANEL: FORM IZIN ────────────────────────────────────────── --}}
        <div id="panel-form-izin" role="tabpanel"
             aria-labelledby="tab-form-izin"
             class="k-card-body">

            {{-- Alert global --}}
            <div id="izin-alert" class="k-alert" style="display:none;margin-bottom:var(--space-4);"></div>

            <form id="form-izin" class="k-form" novalidate>

                {{-- Jenis izin --}}
                <div class="k-form-group">
                    <label for="izin-jenis" class="k-label">
                        Jenis Izin <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="izin-jenis"
                            name="id_jenis_izin"
                            class="k-select"
                            required
                            aria-describedby="err-izin-jenis">
                        <option value="">— Pilih Jenis Izin —</option>
                        {{-- Opsi diisi JS via GET /api/karyawan/jenis-izin --}}
                    </select>
                    <span class="k-field-error" id="err-izin-jenis"></span>
                </div>

                {{-- Notifikasi wajib dokumen --}}
                <div id="izin-wajib-dokumen-info" style="display:none;">
                    <div class="k-alert k-alert--warning" style="font-size:12px;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        <div>
                            <p style="font-weight:600;margin-bottom:2px;">Dokumen Wajib Dilampirkan</p>
                            <p>Jenis izin ini memerlukan dokumen pendukung (mis. surat dokter).
                               Upload dokumen setelah pengajuan berhasil dikirim.</p>
                        </div>
                    </div>
                </div>

                {{-- ── RANGE TANGGAL ──────────────────────────────────── --}}
                <div class="k-form-row">
                    {{-- Tanggal Mulai --}}
                    <div class="k-form-group">
                        <label for="izin-tanggal-mulai" class="k-label">
                            Tanggal Mulai <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="date"
                               id="izin-tanggal-mulai"
                               name="tanggal_izin"
                               class="k-input"
                               required
                               aria-describedby="err-izin-tanggal-mulai">
                        <span class="k-field-error" id="err-izin-tanggal-mulai"></span>
                    </div>

                    {{-- Tanggal Selesai --}}
                    <div class="k-form-group">
                        <label for="izin-tanggal-selesai" class="k-label">
                            Tanggal Selesai
                            <span style="color:var(--text-muted);font-weight:400;
                                         text-transform:none;letter-spacing:0;font-size:11px;">
                                (Opsional — kosongkan untuk 1 hari)
                            </span>
                        </label>
                        <input type="date"
                               id="izin-tanggal-selesai"
                               name="tanggal_selesai_izin"
                               class="k-input"
                               aria-describedby="err-izin-tanggal-selesai">
                        <span class="k-field-error" id="err-izin-tanggal-selesai"></span>
                    </div>
                </div>

                {{-- Preview jumlah hari — diisi JS --}}
                <div id="izin-jumlah-hari-preview"
                     style="display:none;background:var(--eco-50);border:1px solid var(--eco-200);
                            border-radius:var(--radius-md);padding:var(--space-2) var(--space-4);
                            align-items:center;justify-content:space-between;margin-bottom:var(--space-1);">
                    <span style="font-size:13px;color:var(--eco-700);">Durasi izin:</span>
                    <span style="font-family:var(--font-display);font-size:18px;font-weight:700;
                                 color:var(--eco-700);letter-spacing:-0.02em;"
                          id="izin-jumlah-hari-angka">1 hari</span>
                </div>

                {{-- Keterangan --}}
                <div class="k-form-group">
                    <label for="izin-keterangan" class="k-label">
                        Keterangan
                        <span style="color:var(--text-muted);font-weight:400;text-transform:none;
                                     letter-spacing:0;font-size:11px;">(Opsional)</span>
                    </label>
                    <textarea id="izin-keterangan"
                              name="keterangan"
                              class="k-textarea"
                              placeholder="Keterangan tambahan (opsional)…"
                              rows="3"
                              maxlength="500"
                              aria-describedby="err-izin-keterangan"></textarea>
                    <div style="display:flex;justify-content:flex-end;">
                        <span style="font-size:11px;color:var(--text-muted);">
                            <span id="keterangan-count">0</span>/500
                        </span>
                    </div>
                    <span class="k-field-error" id="err-izin-keterangan"></span>
                </div>

                {{-- Tombol --}}
                <div style="display:flex;gap:var(--space-3);padding-top:var(--space-2);">
                    <button type="button"
                            class="k-btn k-btn--ghost"
                            id="btn-reset-izin">
                        Reset
                    </button>
                    <button type="submit"
                            class="k-btn k-btn--primary k-btn--block"
                            id="btn-submit-izin">
                        <span id="submit-izin-text">Ajukan Izin</span>
                        <span class="k-btn-spinner" id="spinner-izin"></span>
                    </button>
                </div>

            </form>

        </div>{{-- /panel-form-izin --}}

        {{-- ── PANEL: RIWAYAT ──────────────────────────────────────────── --}}
        <div id="panel-riwayat-izin" role="tabpanel"
             aria-labelledby="tab-riwayat-izin"
             style="display:none;">

            {{-- Filter --}}
            <div style="padding:var(--space-3) var(--space-4);border-bottom:1px solid var(--surface-border);">
                <select class="k-filter-select" id="filter-status-izin"
                        style="width:100%;max-width:200px;"
                        aria-label="Filter status izin">
                    <option value="">Semua Status</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>

            {{-- List riwayat izin --}}
            <div id="riwayat-izin-list">
                @for ($i = 0; $i < 3; $i++)
                    <div class="k-pengajuan-item">
                        <div class="k-skel k-skel--block"
                             style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                            <div class="k-skel k-skel--text" style="width:60%;"></div>
                            <div class="k-skel k-skel--text" style="width:40%;"></div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                            <div class="k-skel" style="width:60px;height:20px;border-radius:999px;"></div>
                            <div class="k-skel" style="width:44px;height:26px;border-radius:var(--radius-md);"></div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Paginasi --}}
            <div id="paginasi-izin" style="padding:0 var(--space-4) var(--space-3);"></div>

        </div>{{-- /panel-riwayat-izin --}}

    </div>{{-- /k-card --}}

</div>{{-- /k-wrap --}}

{{-- ══ MODAL: Upload Dokumen (F05) ════════════════════════════════════════ --}}
{{--
    FIX: Input file TIDAK lagi menggunakan position:absolute;inset:0 yang menutupi
    seluruh area modal. Input file sekarang disembunyikan secara normal (display:none)
    dan hanya diakses via fileInput.click() yang dipanggil JS saat user klik drop zone.

    Dengan pendekatan ini, tombol "Batal", "✕", dan "Upload Dokumen" dapat diklik
    secara normal tanpa terhalangi oleh overlay input file.
--}}
<div id="modal-upload-dokumen" class="k-modal-overlay" aria-modal="true" role="dialog"
     aria-labelledby="modal-upload-title">

    <div class="k-modal">
        <div class="k-modal-handle" aria-hidden="true"></div>

        <div class="k-modal-header">
            <h3 class="k-modal-title" id="modal-upload-title">Upload Dokumen Izin</h3>
            <button class="k-modal-close" id="btn-close-modal-dokumen"
                    type="button"
                    aria-label="Tutup modal">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="k-modal-body">

            {{-- Info pengajuan yang akan diupload dokumennya --}}
            <div style="background:var(--surface-bg);border-radius:var(--radius-md);
                        padding:var(--space-3);margin-bottom:var(--space-4);">
                <p style="font-size:12px;color:var(--text-muted);">Pengajuan izin untuk:</p>
                <p style="font-size:14px;font-weight:600;color:var(--text-primary);"
                   id="modal-izin-info">—</p>
            </div>

            {{-- Alert upload --}}
            <div id="upload-alert" class="k-alert" style="display:none;margin-bottom:var(--space-3);"></div>

            {{--
                FIX: Drop zone sekarang adalah elemen visual biasa (cursor:pointer, tabindex).
                Input file disembunyikan dengan display:none dan hanya dipicu via JS.
                Ini memastikan tombol di luar drop zone (Batal, ✕) bisa diklik normal.
            --}}
            <div class="k-file-drop" id="file-drop-zone"
                 role="button"
                 tabindex="0"
                 style="position:relative;cursor:pointer;"
                 aria-label="Area upload dokumen. Klik atau seret file ke sini.">

                <div class="k-file-drop-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <p class="k-file-drop-label">
                    <span>Klik untuk pilih file</span> atau seret ke sini
                </p>
                <p class="k-file-drop-hint">PDF, JPG, PNG — Maks. 2 MB per file</p>

                {{--
                    Input file TIDAK menggunakan position:absolute;inset:0 lagi.
                    display:none membuatnya tidak terlihat dan tidak menghalangi elemen lain.
                    Dipicu via JS dengan fileInput.click() saat user klik drop zone.
                --}}
                <input type="file"
                       id="input-dokumen-file"
                       name="dokumen"
                       accept=".pdf,.jpg,.jpeg,.png"
                       style="display:none;"
                       aria-hidden="true">
            </div>

            {{-- Preview file yang dipilih --}}
            <div class="k-file-list" id="upload-file-preview"></div>

            <div class="k-modal-footer">
                <button type="button" class="k-btn k-btn--ghost"
                        id="btn-cancel-upload">Batal</button>
                <button type="button" class="k-btn k-btn--primary"
                        id="btn-confirm-upload" disabled>
                    <span id="upload-text">Upload Dokumen</span>
                    <span class="k-btn-spinner" id="spinner-upload"></span>
                </button>
            </div>

        </div>{{-- /k-modal-body --}}

    </div>{{-- /k-modal --}}

</div>{{-- /modal-upload-dokumen --}}

@endsection

@push('scripts')
    @vite(['resources/js/karyawan/izin.js'])
@endpush

@push('scripts')
    @vite(['resources/js/karyawan/notifikasi.js'])
@endpush