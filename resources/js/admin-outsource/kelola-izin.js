/**
 * resources/js/admin-outsource/kelola-izin.js
 * F04 — Validasi Pengajuan Izin
 * F05 — Verifikasi & Preview Dokumen Pendukung
 *
 * Redesign: satu tabel terpadu. Kolom "Dokumen" menampilkan tombol
 * "Lihat Dokumen" yang membuka modal preview (embed PDF/gambar)
 * lengkap dengan aksi validasi langsung dari modal tersebut.
 *
 * Endpoint:
 *   GET  /api/admin/validasi-izin              → daftar izin
 *   GET  /api/admin/validasi-izin/{id}         → detail izin + dokumen
 *   POST /api/admin/validasi-izin/{id}         → approve / reject
 *   GET  /api/admin/izin/{id}/dokumen/{docId}  → stream file dokumen
 */

import {
    apiFetch, esc, fmtTanggal, toast,
    openModal, closeModal, badgeStatusIzin,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

// ── State ─────────────────────────────────────────────────────────────────────
let currentPage      = 1;
let filterStatus     = 'menunggu';
let searchQuery      = '';
let debounce         = null;
let selectedIzinId   = null;
let selectedIzinData = null; // baris data izin yang sedang dibuka

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    injectToolbar();
    injectModals();
    updateThead();
    loadIzin();
});

// ════════════════════════════════════════════════════════════════════════
//  LOAD DATA
// ════════════════════════════════════════════════════════════════════════
async function loadIzin(page = 1) {
    currentPage = page;
    showSkeleton();

    const params = new URLSearchParams({ page });
    // Selalu kirim status. Nilai kosong (?status=) berarti "Semua Status".
    params.set('status', filterStatus ?? '');
    if (searchQuery)  params.set('search', searchQuery);

    try {
        const res  = await apiFetch(`/api/admin/validasi-izin?${params}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        renderTabel(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-izin', loadIzin);

    } catch (err) {
        console.error('[KelolaIzin] error:', err);
        toast('Gagal memuat data izin.', 'error');
    }
}

// ════════════════════════════════════════════════════════════════════════
//  RENDER TABEL
// ════════════════════════════════════════════════════════════════════════
function renderTabel(rows) {
    const tbody = document.getElementById('tbody-izin');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
            Tidak ada pengajuan izin ${filterStatus === 'menunggu' ? 'yang perlu divalidasi' : ''} saat ini.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const namaKaryawan  = row.karyawan?.nama_lengkap ?? row.nama_karyawan ?? '—';
        const nomorKaryawan = row.karyawan?.nomor_karyawan ?? row.nomor_karyawan ?? '';
        const namaJenis     = row.jenis_izin?.nama_jenis ?? '—';
        const wajibDokumen  = row.jenis_izin?.wajib_dokumen ?? false;
        const jumlahDok     = row.jumlah_dokumen ?? 0;
        const statusDok     = row.status_dokumen ?? '';

        // ── Kolom Dokumen ────────────────────────────────────────────────
        let dokumenCell;
        if (jumlahDok > 0) {
            dokumenCell = `
                <button class="btn-lihat-dokumen"
                    data-id="${row.id_izin}"
                    data-jumlah="${jumlahDok}"
                    data-nama="${esc(namaKaryawan)}">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Lihat (${jumlahDok})
                </button>`;
        } else if (wajibDokumen) {
            dokumenCell = `<span class="dok-badge dok-badge--missing">⚠ Belum upload</span>`;
        } else {
            dokumenCell = `<span style="font-size:11px;color:#94a3b8;">Tidak wajib</span>`;
        }

        // ── Kolom Aksi ───────────────────────────────────────────────────
        let aksiCell;
        if (row.status === 'menunggu') {
            aksiCell = `
                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <button class="btn-approve btn-izin-approve"
                        data-id="${row.id_izin}"
                        data-nama="${esc(namaKaryawan)}"
                        data-wajib="${wajibDokumen ? '1' : '0'}"
                        data-dok-status="${statusDok}">
                        ✓ Setujui
                    </button>
                    <button class="btn-reject btn-izin-reject"
                        data-id="${row.id_izin}"
                        data-nama="${esc(namaKaryawan)}">
                        ✕ Tolak
                    </button>
                </div>`;
        } else {
            aksiCell = `<span style="font-size:12px;color:#94a3b8;">Sudah diproses</span>`;
        }

        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:12px;font-weight:700;color:#87dc87;">
                        ${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(nomorKaryawan)}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${esc(namaJenis)}</td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_izin)}</td>
            <td style="font-size:12px;color:#64748b;">${esc(row.keterangan ?? '—')}</td>
            <td>${dokumenCell}</td>
            <td>${badgeStatusIzin(row.status)}</td>
            <td>${aksiCell}</td>
        </tr>`;
    }).join('');
}

// ════════════════════════════════════════════════════════════════════════
//  PROSES VALIDASI
// ════════════════════════════════════════════════════════════════════════
async function prosesIzin(id, aksi, catatan = '') {
    try {
        const body = { aksi };
        if (catatan) body.catatan_penolakan = catatan;

        const res  = await apiFetch(`/api/admin/validasi-izin/${id}`, {
            method: 'POST',
            body: JSON.stringify(body),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            // Tutup semua modal yang mungkin terbuka
            closeModal('modal-dokumen-preview');
            closeModal('modal-reject-izin');
            closeModal('modal-approve-warning');
            loadIzin(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses izin.', 'error');
    }
}

// ════════════════════════════════════════════════════════════════════════
//  MODAL PREVIEW DOKUMEN
//  Load daftar dokumen dari API, render daftar + preview file pertama
// ════════════════════════════════════════════════════════════════════════
async function bukaModalDokumen(idIzin, namaKaryawan) {
    selectedIzinId = idIzin;

    // Set state judul modal
    document.getElementById('modal-dok-nama').textContent = namaKaryawan;
    document.getElementById('modal-dok-list').innerHTML   = _skeletonDokList();
    document.getElementById('dok-preview-area').innerHTML = _previewPlaceholder();

    // Sembunyikan aksi validasi sementara — akan diisi setelah load data
    document.getElementById('modal-dok-aksi').style.display = 'none';

    openModal('modal-dokumen-preview');

    try {
        // Ambil detail izin lengkap (termasuk dokumen)
        const res  = await apiFetch(`/api/admin/validasi-izin/${idIzin}`);
        const json = await res.json();
        if (!json.status || !json.data) {
            toast(json.message || 'Data izin tidak ditemukan.', 'error');
            document.getElementById('modal-dok-list').innerHTML =
                '<p style="color:#94a3b8;font-size:13px;padding:8px;">Data izin tidak ditemukan.</p>';
            return;
        }

        const izinRow = json.data;
        selectedIzinData = izinRow;
        _renderDokumenList(izinRow);

    } catch (err) {
        console.error('[Dokumen] error:', err);
        document.getElementById('modal-dok-list').innerHTML =
            '<p style="color:#ef4444;font-size:13px;padding:8px;">Gagal memuat daftar dokumen.</p>';
    }
}

function _renderDokumenList(izinRow) {
    const dokList = document.getElementById('modal-dok-list');
    const aksiEl  = document.getElementById('modal-dok-aksi');

    const dokumen  = izinRow.dokumen ?? [];
    const status   = izinRow.status;

    // ── Render daftar dokumen ─────────────────────────────────────────
    if (!dokumen.length) {
        dokList.innerHTML = `
            <div style="padding:16px;text-align:center;color:#94a3b8;">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 13h6m-3-3v6m-9 1V7a2 2 0 0 1 2-2h6l2 2h4a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <p style="font-size:12px;margin:0;">Belum ada dokumen diunggah</p>
            </div>`;
    } else {
        dokList.innerHTML = dokumen.map((d, i) => `
            <button class="dok-list-item ${i === 0 ? 'dok-list-item--active' : ''}"
                data-izin="${izinRow.id_izin}"
                data-dok="${d.id_dokumen}"
                data-tipe="${esc(d.tipe_file ?? '')}"
                data-nama="${esc(d.nama_file ?? `dokumen_${d.id_dokumen}`)}">
                <span class="dok-tipe-icon">${_tipeIcon(d.tipe_file)}</span>
                <div class="dok-list-info">
                    <span class="dok-list-nama">${esc(d.nama_file ?? `Dokumen ${d.id_dokumen}`)}</span>
                    <span class="dok-list-meta">${esc(d.tipe_file?.toUpperCase() ?? '')} · ${d.ukuran_kb ?? 0} KB</span>
                </div>
                <button class="dok-buka-btn"
                    onclick="window.open('/api/admin/izin/${izinRow.id_izin}/dokumen/${d.id_dokumen}', '_blank')"
                    title="Buka di tab baru">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </button>
            </button>
        `).join('');

        // Auto-preview dokumen pertama
        if (dokumen[0]) {
            _loadPreview(izinRow.id_izin, dokumen[0].id_dokumen, dokumen[0].tipe_file, dokumen[0].nama_file);
        }
    }

    // ── Tampilkan aksi validasi jika masih menunggu ───────────────────
    if (status === 'menunggu') {
        aksiEl.style.display = 'flex';
        document.getElementById('btn-dok-approve').dataset.id   = izinRow.id_izin;
        document.getElementById('btn-dok-approve').dataset.nama = izinRow.karyawan?.nama_lengkap ?? '';
        document.getElementById('btn-dok-approve').dataset.wajib = izinRow.jenis_izin?.wajib_dokumen ? '1' : '0';
        document.getElementById('btn-dok-approve').dataset.dokStatus = izinRow.status_dokumen ?? '';
        document.getElementById('btn-dok-reject').dataset.id    = izinRow.id_izin;
        document.getElementById('btn-dok-reject').dataset.nama  = izinRow.karyawan?.nama_lengkap ?? '';
    } else {
        aksiEl.style.display = 'none';
    }
}

/**
 * Load preview file di dalam modal.
 * PDF → iframe, gambar → img, lainnya → pesan download
 */
function _loadPreview(idIzin, idDok, tipe, nama) {
    const area = document.getElementById('dok-preview-area');
    const url  = `/api/admin/izin/${idIzin}/dokumen/${idDok}`;
    const ext  = (tipe ?? '').toLowerCase();

    // Update active state di daftar
    document.querySelectorAll('.dok-list-item').forEach(el => {
        el.classList.toggle('dok-list-item--active',
            parseInt(el.dataset.dok) === idDok);
    });

    if (ext === 'pdf') {
        area.innerHTML = `
            <div class="dok-preview-loading" id="dok-preview-loading">
                <div class="dok-preview-spinner"></div>
                <span>Memuat PDF…</span>
            </div>
            <iframe
                src="${url}#toolbar=1&navpanes=0"
                class="dok-preview-iframe"
                id="dok-iframe"
                onload="document.getElementById('dok-preview-loading').style.display='none'"
                onerror="document.getElementById('dok-preview-area').innerHTML = window._previewError('${url}', '${esc(nama)}')">
            </iframe>`;
    } else if (['jpg','jpeg','png'].includes(ext)) {
        area.innerHTML = `
            <div class="dok-preview-loading" id="dok-preview-loading">
                <div class="dok-preview-spinner"></div>
                <span>Memuat gambar…</span>
            </div>
            <img
                src="${url}"
                class="dok-preview-img"
                alt="${esc(nama)}"
                onload="document.getElementById('dok-preview-loading').style.display='none'"
                onerror="document.getElementById('dok-preview-area').innerHTML = window._previewError('${url}', '${esc(nama)}')">`;
    } else {
        area.innerHTML = `
            <div class="dok-preview-unsupported">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
                <p>Format <strong>${esc(ext.toUpperCase())}</strong> tidak bisa dipreview.</p>
                <a href="${url}" target="_blank" class="btn-primary-sm" style="margin-top:12px;">
                    Download File
                </a>
            </div>`;
    }
}

// Expose helper untuk onerror inline
window._previewError = function(url, nama) {
    return `
        <div class="dok-preview-unsupported">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p>Gagal memuat file. Coba buka di tab baru.</p>
            <a href="${url}" target="_blank" class="btn-primary-sm" style="margin-top:12px;">
                Buka di Tab Baru
            </a>
        </div>`;
};

// ════════════════════════════════════════════════════════════════════════
//  INJECT TOOLBAR
// ════════════════════════════════════════════════════════════════════════
function injectToolbar() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('search-izin')) return;

    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-izin" class="ao-search" type="text"
            placeholder="Cari nama karyawan..." style="width:200px;">
        <select id="filter-izin-status" class="ao-select">
            <option value="menunggu" selected>Menunggu Persetujuan</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
            <option value="">Semua Status</option>
        </select>
    `;
    header.after(wrap);

    const panel = document.querySelector('.dash-panel-body');
    if (panel && !document.getElementById('paginasi-izin')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-izin"></div>');
    }

    wrap.querySelector('#search-izin')?.addEventListener('input', e => {
        clearTimeout(debounce);
        debounce = setTimeout(() => { searchQuery = e.target.value.trim(); loadIzin(1); }, 400);
    });

    wrap.querySelector('#filter-izin-status')?.addEventListener('change', e => {
        filterStatus = e.target.value; loadIzin(1);
    });
}

// ════════════════════════════════════════════════════════════════════════
//  INJECT MODALS
// ════════════════════════════════════════════════════════════════════════
function injectModals() {
    if (document.getElementById('modal-dokumen-preview')) return;

    // Inject styles khusus halaman ini
    if (!document.getElementById('kelola-izin-styles')) {
        const style = document.createElement('style');
        style.id = 'kelola-izin-styles';
        style.textContent = `
            /* ── Tombol Lihat Dokumen ─────────────────────────────────── */
            .btn-lihat-dokumen {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 10px;
                border: 1px solid #bbecbb;
                border-radius: 7px;
                background: #f0faf0;
                color: #1a6e1a;
                font-family: 'DM Sans', sans-serif;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: background .15s, border-color .15s;
            }
            .btn-lihat-dokumen:hover {
                background: #dcf5dc;
                border-color: #1a6e1a;
            }
            .dok-badge--missing {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 8px;
                border-radius: 6px;
                background: #fef2f2;
                color: #b91c1c;
                font-size: 11px;
                font-weight: 500;
                border: 1px solid #fecaca;
            }

            /* ── Modal Dokumen Preview ───────────────────────────────── */
            #modal-dokumen-preview .modal-box {
                max-width: 900px;
                width: 95vw;
                height: 88vh;
                display: flex;
                flex-direction: column;
            }
            .dok-modal-layout {
                display: flex;
                flex: 1;
                min-height: 0;
                gap: 0;
            }
            /* Sidebar daftar dokumen */
            .dok-sidebar {
                width: 240px;
                flex-shrink: 0;
                border-right: 1px solid #f1f5f9;
                display: flex;
                flex-direction: column;
                background: #f8fafc;
            }
            .dok-sidebar-title {
                padding: 12px 14px 8px;
                font-family: 'DM Sans', sans-serif;
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .07em;
                border-bottom: 1px solid #f1f5f9;
            }
            #modal-dok-list {
                flex: 1;
                overflow-y: auto;
                padding: 8px;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .dok-list-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 10px;
                border-radius: 8px;
                border: 1px solid transparent;
                background: transparent;
                cursor: pointer;
                text-align: left;
                width: 100%;
                transition: background .12s;
            }
            .dok-list-item:hover {
                background: #fff;
                border-color: #e2e8f0;
            }
            .dok-list-item--active {
                background: #fff !important;
                border-color: #bbecbb !important;
            }
            .dok-tipe-icon {
                font-size: 18px;
                flex-shrink: 0;
                line-height: 1;
            }
            .dok-list-info {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .dok-list-nama {
                font-family: 'DM Sans', sans-serif;
                font-size: 12px;
                font-weight: 500;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .dok-list-meta {
                font-size: 10px;
                color: #94a3b8;
                font-family: 'DM Sans', sans-serif;
            }
            .dok-buka-btn {
                flex-shrink: 0;
                background: none;
                border: none;
                cursor: pointer;
                color: #94a3b8;
                padding: 2px;
                border-radius: 4px;
                display: flex;
                align-items: center;
            }
            .dok-buka-btn:hover {
                background: #e2e8f0;
                color: #0f172a;
            }

            /* Area preview */
            .dok-preview-wrap {
                flex: 1;
                display: flex;
                flex-direction: column;
                min-width: 0;
                position: relative;
                background: #1e1e2e;
            }
            #dok-preview-area {
                flex: 1;
                position: relative;
                overflow: hidden;
            }
            .dok-preview-iframe {
                width: 100%;
                height: 100%;
                border: none;
                display: block;
            }
            .dok-preview-img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
                padding: 16px;
                box-sizing: border-box;
            }
            .dok-preview-loading {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 12px;
                color: #94a3b8;
                font-family: 'DM Sans', sans-serif;
                font-size: 13px;
                background: #f8fafc;
                z-index: 1;
            }
            .dok-preview-spinner {
                width: 28px;
                height: 28px;
                border: 3px solid #e2e8f0;
                border-top-color: #1a6e1a;
                border-radius: 50%;
                animation: spin .7s linear infinite;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            .dok-preview-unsupported {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #64748b;
                text-align: center;
                padding: 32px;
                font-family: 'DM Sans', sans-serif;
                font-size: 13px;
            }
            .dok-preview-unsupported svg { opacity: .4; margin-bottom: 12px; }

            /* Footer aksi validasi */
            #modal-dok-aksi {
                padding: 12px 20px;
                border-top: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                background: #fff;
                flex-shrink: 0;
            }

            /* Info karyawan di modal header */
            .dok-modal-subtitle {
                font-family: 'DM Sans', sans-serif;
                font-size: 12px;
                color: #64748b;
                margin-top: 2px;
            }
        `;
        document.head.appendChild(style);
    }

    document.body.insertAdjacentHTML('beforeend', `
        <!-- ══ Modal Preview Dokumen ══════════════════════════════════════ -->
        <div id="modal-dokumen-preview" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="padding:0;overflow:hidden;display:flex;flex-direction:column;">

                <!-- Header -->
                <div class="modal-header" style="flex-shrink:0;">
                    <div>
                        <h3 class="modal-title">Dokumen Izin</h3>
                        <p class="dok-modal-subtitle">Karyawan: <strong id="modal-dok-nama">—</strong></p>
                    </div>
                    <button data-close-modal="modal-dokumen-preview" class="modal-close">×</button>
                </div>

                <!-- Layout: sidebar + preview -->
                <div class="dok-modal-layout">

                    <!-- Sidebar: daftar file -->
                    <div class="dok-sidebar">
                        <div class="dok-sidebar-title">File Dokumen</div>
                        <div id="modal-dok-list">
                            ${_skeletonDokList()}
                        </div>
                    </div>

                    <!-- Preview area -->
                    <div class="dok-preview-wrap">
                        <div id="dok-preview-area">
                            ${_previewPlaceholder()}
                        </div>
                    </div>

                </div>

                <!-- Footer aksi validasi (hanya muncul jika status menunggu) -->
                <div id="modal-dok-aksi" style="display:none;">
                    <span style="font-size:12px;color:#64748b;margin-right:auto;">
                        Validasi pengajuan izin ini:
                    </span>
                    <button class="btn-reject" id="btn-dok-reject"
                        data-id="" data-nama="">
                        ✕ Tolak
                    </button>
                    <button class="btn-approve" id="btn-dok-approve"
                        data-id="" data-nama="" data-wajib="0" data-dok-status="">
                        ✓ Setujui
                    </button>
                </div>

            </div>
        </div>

        <!-- ══ Modal Reject Izin ═══════════════════════════════════════════ -->
        <div id="modal-reject-izin" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:440px;">
                <div class="modal-header">
                    <h3 class="modal-title">Tolak Pengajuan Izin</h3>
                    <button data-close-modal="modal-reject-izin" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                        Tolak izin: <strong id="reject-izin-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Catatan Penolakan <span style="color:#ef4444;">*</span></label>
                        <textarea id="reject-izin-catatan" class="catatan-box"
                            placeholder="Jelaskan alasan penolakan kepada karyawan..."
                            rows="4"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button data-close-modal="modal-reject-izin" class="btn-cancel">Batal</button>
                        <button id="btn-konfirmasi-reject-izin"
                            style="padding:9px 20px;border:none;border-radius:8px;
                                background:#dc2626;font-family:'DM Sans',sans-serif;
                                font-size:13px;font-weight:600;color:#fff;cursor:pointer;">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ Modal Konfirmasi Approve Izin ════════════════════════════ -->
        <div id="modal-approve-izin" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title" style="color:#16a34a;">✓ Konfirmasi Persetujuan Izin</h3>
                    <button data-close-modal="modal-approve-izin" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 20px;">
                        Apakah Anda yakin ingin menyetujui izin untuk <strong id="modal-approve-izin-nama" style="color:#0f172a;"></strong>?
                    </p>
                    <div class="modal-footer" style="padding-top:0;border-top:none;">
                        <button data-close-modal="modal-approve-izin" class="btn-cancel">Batal</button>
                        <button id="btn-konfirmasi-approve-izin"
                            style="padding:9px 20px;border:none;border-radius:8px;
                                background:#16a34a;
                                font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;
                                color:#fff;cursor:pointer;">
                            Setujui Izin
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ Modal Warning Approve Tanpa Dokumen ════════════════════════ -->
        <div id="modal-approve-warning" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title" style="color:#d97706;">⚠ Konfirmasi Persetujuan</h3>
                    <button data-close-modal="modal-approve-warning" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 20px;">
                        Izin <strong id="modal-approve-warning-nama" style="color:#0f172a;"></strong>
                        membutuhkan dokumen pendukung, namun belum ada dokumen yang diunggah karyawan.<br><br>
                        Anda tetap bisa menyetujui jika sudah mendapatkan bukti secara terpisah.
                    </p>
                    <div class="modal-footer" style="padding-top:0;border-top:none;">
                        <button data-close-modal="modal-approve-warning" class="btn-cancel">Batal</button>
                        <button id="btn-tetap-approve"
                            style="padding:9px 20px;border:none;border-radius:8px;
                                background:linear-gradient(135deg,#f59e0b,#d97706);
                                font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;
                                color:#fff;cursor:pointer;">
                            Tetap Setujui
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);

    bindEvents();
}

// ════════════════════════════════════════════════════════════════════════
//  BIND EVENTS
// ════════════════════════════════════════════════════════════════════════
function bindEvents() {
    // Tutup modal
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    // Delegasi tabel — semua klik di dalam .dash-panel-body
    document.querySelector('.dash-panel-body')?.addEventListener('click', async e => {

        // Tombol Lihat Dokumen
        const lihatBtn = e.target.closest('.btn-lihat-dokumen');
        if (lihatBtn) {
            await bukaModalDokumen(
                parseInt(lihatBtn.dataset.id),
                lihatBtn.dataset.nama
            );
            return;
        }

        // Setujui izin (dari tabel)
        const approveBtn = e.target.closest('.btn-izin-approve');
        if (approveBtn) {
            _handleApprove(approveBtn);
            return;
        }

        // Tolak izin (dari tabel)
        const rejectBtn = e.target.closest('.btn-izin-reject');
        if (rejectBtn) {
            selectedIzinId = parseInt(rejectBtn.dataset.id);
            document.getElementById('reject-izin-nama').textContent = rejectBtn.dataset.nama;
            setVal('reject-izin-catatan', '');
            openModal('modal-reject-izin');
        }
    });

    // Delegasi dokumen di dalam modal (klik file di sidebar)
    document.getElementById('modal-dokumen-preview')?.addEventListener('click', e => {

        // Klik item dokumen di sidebar → preview
        const dokItem = e.target.closest('.dok-list-item');
        if (dokItem && !e.target.closest('.dok-buka-btn')) {
            _loadPreview(
                parseInt(dokItem.dataset.izin),
                parseInt(dokItem.dataset.dok),
                dokItem.dataset.tipe,
                dokItem.dataset.nama
            );
        }

        // Setujui dari dalam modal dokumen
        const approveDokBtn = e.target.closest('#btn-dok-approve');
        if (approveDokBtn) {
            _handleApprove(approveDokBtn);
        }

        // Tolak dari dalam modal dokumen
        const rejectDokBtn = e.target.closest('#btn-dok-reject');
        if (rejectDokBtn) {
            selectedIzinId = parseInt(rejectDokBtn.dataset.id);
            document.getElementById('reject-izin-nama').textContent = rejectDokBtn.dataset.nama;
            setVal('reject-izin-catatan', '');
            openModal('modal-reject-izin');
        }
    });

    // Konfirmasi tolak
    document.getElementById('btn-konfirmasi-reject-izin')?.addEventListener('click', async () => {
        const catatan = getVal('reject-izin-catatan').trim();
        if (!catatan) { toast('Catatan penolakan wajib diisi.', 'warning'); return; }
        closeModal('modal-reject-izin');
        await prosesIzin(selectedIzinId, 'ditolak', catatan);
    });

    // Konfirmasi setujui
    document.getElementById('btn-konfirmasi-approve-izin')?.addEventListener('click', async () => {
        closeModal('modal-approve-izin');
        await prosesIzin(selectedIzinId, 'disetujui');
    });

}

// ── Handle Approve (cek dokumen wajib terlebih dahulu) ────────────────────────
function _handleApprove(btn) {
    const wajib    = btn.dataset.wajib === '1';
    const dokStatus = btn.dataset.dokStatus ?? '';
    const id       = parseInt(btn.dataset.id);
    const nama     = btn.dataset.nama ?? '—';

    // Izin wajib dokumen hanya boleh disetujui setelah status dokumen sudah_upload.
    if (wajib && dokStatus !== 'sudah_upload') {
        document.getElementById('modal-approve-warning-nama').textContent = nama;
        selectedIzinId = id;
        openModal('modal-approve-warning');
        return;
    }

    // Buka modal konfirmasi
    selectedIzinId = id;
    document.getElementById('modal-approve-izin-nama').textContent = nama;
    openModal('modal-approve-izin');
}

// ════════════════════════════════════════════════════════════════════════
//  HELPERS UI
// ════════════════════════════════════════════════════════════════════════
function updateThead() {
    const thead = document.querySelector('.data-table thead tr');
    if (thead) thead.innerHTML = `
        <th>Karyawan</th>
        <th>Jenis Izin</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>Dokumen</th>
        <th>Status</th>
        <th>Aksi</th>
    `;
}

function showSkeleton() {
    const tbody = document.getElementById('tbody-izin');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>${Array(7).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function _skeletonDokList() {
    return Array(3).fill(`
        <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;">
            <div class="skel" style="width:20px;height:20px;border-radius:4px;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:4px;">
                <div class="skel" style="height:9px;width:100%;border-radius:3px;"></div>
                <div class="skel" style="height:7px;width:60%;border-radius:3px;"></div>
            </div>
        </div>
    `).join('');
}

function _previewPlaceholder() {
    return `
        <div class="dok-preview-unsupported">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 0 1 2-2h6l2 2h4a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 0-2-2z"/>
            </svg>
            <p style="margin:0;font-size:13px;color:#94a3b8;">Pilih dokumen untuk preview</p>
        </div>`;
}

function _tipeIcon(tipe) {
    const ext = (tipe ?? '').toLowerCase();
    if (ext === 'pdf') return '📄';
    if (['jpg','jpeg','png'].includes(ext)) return '🖼️';
    return '📎';
}

function getVal(id) { return document.getElementById(id)?.value ?? ''; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ''; }
