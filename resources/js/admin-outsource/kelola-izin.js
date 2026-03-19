/**
 * resources/js/admin-outsource/kelola-izin.js
 * F10 (izin) — Validasi Pengajuan Izin
 * F05 ↔     — Verifikasi & Download Dokumen Pendukung
 *
 * Panel kiri: daftar pengajuan izin (filter status, search)
 * Panel kanan: izin yang dokumennya perlu diverifikasi
 *
 * Endpoint:
 *   GET  /api/admin/validasi-izin           → daftar izin
 *   POST /api/admin/validasi-izin/{id}      → approve / reject
 *   GET  /api/karyawan/izin/{id}/dokumen/{docId}  → download dokumen
 *   (admin download via /api/admin/izin/{id}/dokumen/{docId} jika endpoint tersedia)
 */

import {
    apiFetch, esc, fmtTanggal, toast,
    openModal, closeModal, badgeStatusIzin,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

// State panel kiri (pengajuan izin)
let pageIzin       = 1;
let filterStatusIzin = 'menunggu';
let searchIzin     = '';
let debounce       = null;
let selectedIzinId = null;
let selectedAksi   = null;

// State panel kanan (dokumen perlu verifikasi)
let pageDokumen    = 1;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    injectToolbars();
    injectModals();
    updateTheads();
    loadPengajuanIzin();
    loadDokumenPendingVerifikasi();
});

// ════════════════════════════════════════════════════════════════════════
//  PANEL KIRI: DAFTAR PENGAJUAN IZIN
// ════════════════════════════════════════════════════════════════════════
async function loadPengajuanIzin(page = 1) {
    pageIzin = page;
    showSkeleton('tbody-izin', 5, 6);

    const params = new URLSearchParams({ page });
    if (filterStatusIzin) params.set('status', filterStatusIzin);
    if (searchIzin)       params.set('search', searchIzin);

    try {
        const res  = await apiFetch(`/api/admin/validasi-izin?${params}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        renderIzin(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-izin', loadPengajuanIzin);

    } catch (err) {
        console.error('[Izin] error:', err);
        toast('Gagal memuat data izin.', 'error');
    }
}

function renderIzin(rows) {
    const tbody = document.getElementById('tbody-izin');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada pengajuan izin ${filterStatusIzin === 'menunggu' ? 'yang perlu divalidasi' : ''} saat ini.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const dokIcon = row.jenis_izin?.wajib_dokumen
            ? (row.status_dokumen === 'sudah_upload'
                ? `<span style="font-size:11px;color:#1a6e1a;">📎 Tersedia</span>`
                : `<span style="font-size:11px;color:#b91c1c;">⚠ Belum upload</span>`)
            : `<span style="font-size:11px;color:#94a3b8;">Tidak wajib</span>`;

        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#87dc87;">
                        ${esc(row.nama_karyawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(row.nama_karyawan)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(row.nomor_karyawan ?? '')}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${esc(row.jenis_izin?.nama_jenis ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_izin)}</td>
            <td>${dokIcon}</td>
            <td>${badgeStatusIzin(row.status)}</td>
            <td>
                ${row.status === 'menunggu'
                    ? `<div style="display:flex;gap:5px;flex-wrap:wrap;">
                            <button class="btn-approve btn-izin-approve"
                                data-id="${row.id_izin}"
                                data-nama="${esc(row.nama_karyawan)}"
                                data-wajib="${row.jenis_izin?.wajib_dokumen ? '1' : '0'}"
                                data-dok="${row.status_dokumen ?? ''}">
                                ✓ Setujui
                            </button>
                            <button class="btn-reject btn-izin-reject"
                                data-id="${row.id_izin}"
                                data-nama="${esc(row.nama_karyawan)}">
                                ✕ Tolak
                            </button>
                       </div>`
                    : `<span style="font-size:12px;color:#94a3b8;">Sudah diproses</span>`
                }
            </td>
        </tr>`;
    }).join('');
}

async function prosesIzin(id, aksi, catatan = '') {
    try {
        const res  = await apiFetch(`/api/admin/validasi-izin/${id}`, {
            method: 'POST',
            body: JSON.stringify({ aksi, catatan }),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            loadPengajuanIzin(pageIzin);
            loadDokumenPendingVerifikasi(pageDokumen);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses izin.', 'error');
    }
}

// ════════════════════════════════════════════════════════════════════════
//  PANEL KANAN: DOKUMEN YANG PERLU DIVERIFIKASI
// ════════════════════════════════════════════════════════════════════════
async function loadDokumenPendingVerifikasi(page = 1) {
    pageDokumen = page;
    showSkeleton('tbody-dokumen-verifikasi', 5, 6);

    const params = new URLSearchParams({ page, status: 'menunggu', has_dokumen: '1' });

    try {
        const res  = await apiFetch(`/api/admin/validasi-izin?${params}`);
        const json = await res.json();
        if (!json.status) return;

        renderDokumen(json.data?.data ?? json.data ?? []);

    } catch (err) {
        console.error('[Dokumen] error:', err);
    }
}

function renderDokumen(rows) {
    const tbody = document.getElementById('tbody-dokumen-verifikasi');
    if (!tbody) return;

    // Filter hanya yang ada dokumen
    const withDok = rows.filter(r => r.status_dokumen === 'sudah_upload' && r.jumlah_dokumen > 0);

    if (!withDok.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada dokumen yang perlu diverifikasi.</td></tr>`;
        return;
    }

    tbody.innerHTML = withDok.map(row => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#87dc87;">
                        ${esc(row.nama_karyawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <span style="font-weight:500;color:#0f172a;font-size:13px;">${esc(row.nama_karyawan)}</span>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${esc(row.jenis_izin?.nama_jenis ?? '—')}</td>
            <td>
                ${(row.dokumen ?? []).map(d => `
                    <span style="display:inline-flex;align-items:center;gap:4px;
                        font-size:11px;background:#f8fafc;border:1px solid #e2e8f0;
                        border-radius:5px;padding:2px 8px;margin:2px;">
                        📄 ${esc(d.nama_file ?? `Dokumen #${d.id_dokumen}`)}
                        <button class="btn-download-dok"
                            data-izin="${row.id_izin}"
                            data-dok="${d.id_dokumen}"
                            data-nama="${esc(d.nama_file ?? 'dokumen')}"
                            style="background:none;border:none;cursor:pointer;color:#f59e0b;
                                font-size:10px;padding:0 2px;font-weight:600;">
                            ↓
                        </button>
                    </span>
                `).join('')}
            </td>
            <td style="font-size:12px;color:#475569;">${row.jumlah_dokumen ?? 0} file</td>
            <td>${badgeStatusIzin(row.status)}</td>
            <td>
                <div style="display:flex;gap:5px;">
                    <button class="btn-approve btn-dok-approve"
                        data-id="${row.id_izin}"
                        data-nama="${esc(row.nama_karyawan)}">
                        ✓ Setujui
                    </button>
                    <button class="btn-reject btn-dok-reject"
                        data-id="${row.id_izin}"
                        data-nama="${esc(row.nama_karyawan)}">
                        ✕ Tolak
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Download dokumen — buka di tab baru (browser handle stream)
async function downloadDokumen(idIzin, idDok, nama) {
    try {
        // Buka di tab baru dengan endpoint download — browser handle stream
        window.open(`/api/admin/izin/${idIzin}/dokumen/${idDok}`, '_blank');
    } catch {
        toast('Gagal membuka dokumen.', 'error');
    }
}

// ── Event binding ─────────────────────────────────────────────────────────────
function bindEvents() {
    // Panel kiri — filter
    document.getElementById('search-izin')?.addEventListener('input', e => {
        clearTimeout(debounce);
        debounce = setTimeout(() => { searchIzin = e.target.value.trim(); loadPengajuanIzin(1); }, 400);
    });

    document.getElementById('filter-izin-status')?.addEventListener('change', e => {
        filterStatusIzin = e.target.value; loadPengajuanIzin(1);
    });

    // Delegasi panel kiri
    document.querySelector('.dashboard-secondary')?.addEventListener('click', async e => {
        // Approve izin
        const approveBtn = e.target.closest('.btn-izin-approve');
        if (approveBtn) {
            const wajib  = approveBtn.dataset.wajib === '1';
            const dokAda = approveBtn.dataset.dok === 'sudah_upload';

            if (wajib && !dokAda) {
                // Warning: dokumen wajib belum diupload
                toast('Peringatan: Jenis izin ini membutuhkan dokumen pendukung, namun belum diupload karyawan. Tetap setujui?', 'warning', 5000);
                selectedIzinId = parseInt(approveBtn.dataset.id);
                document.getElementById('modal-approve-warning-nama').textContent = approveBtn.dataset.nama;
                openModal('modal-approve-warning');
                return;
            }

            await prosesIzin(parseInt(approveBtn.dataset.id), 'disetujui');
        }

        // Reject izin
        const rejectBtn = e.target.closest('.btn-izin-reject');
        if (rejectBtn) {
            selectedIzinId = parseInt(rejectBtn.dataset.id);
            selectedAksi   = 'ditolak';
            document.getElementById('reject-izin-nama').textContent = rejectBtn.dataset.nama;
            setVal('reject-izin-catatan', '');
            openModal('modal-reject-izin');
        }

        // Approve dari panel dokumen
        const dokApproveBtn = e.target.closest('.btn-dok-approve');
        if (dokApproveBtn) {
            await prosesIzin(parseInt(dokApproveBtn.dataset.id), 'disetujui');
        }

        // Reject dari panel dokumen
        const dokRejectBtn = e.target.closest('.btn-dok-reject');
        if (dokRejectBtn) {
            selectedIzinId = parseInt(dokRejectBtn.dataset.id);
            selectedAksi   = 'ditolak';
            document.getElementById('reject-izin-nama').textContent = dokRejectBtn.dataset.nama;
            setVal('reject-izin-catatan', '');
            openModal('modal-reject-izin');
        }

        // Download dokumen
        const downloadBtn = e.target.closest('.btn-download-dok');
        if (downloadBtn) {
            await downloadDokumen(
                parseInt(downloadBtn.dataset.izin),
                parseInt(downloadBtn.dataset.dok),
                downloadBtn.dataset.nama,
            );
        }
    });
}

// ── Inject UI ─────────────────────────────────────────────────────────────────
function updateTheads() {
    // Panel kiri
    const t1 = document.querySelectorAll('.dashboard-secondary .data-table thead tr')[0];
    if (t1) t1.innerHTML = `
        <th>Karyawan</th><th>Jenis Izin</th><th>Tanggal</th>
        <th>Dokumen</th><th>Status</th><th>Aksi</th>`;

    // Panel kanan
    const t2 = document.querySelectorAll('.dashboard-secondary .data-table thead tr')[1];
    if (t2) t2.innerHTML = `
        <th>Karyawan</th><th>Jenis Izin</th><th>File Dokumen</th>
        <th>Jumlah</th><th>Status Izin</th><th>Aksi</th>`;
}

function injectToolbars() {
    const panels = document.querySelectorAll('.dash-panel .dash-panel-header');
    if (!panels.length || document.getElementById('search-izin')) return;

    // Toolbar panel kiri
    const tb1 = document.createElement('div');
    tb1.className = 'ao-toolbar';
    tb1.style.cssText = 'padding:0 20px 12px;';
    tb1.innerHTML = `
        <input id="search-izin" class="ao-search" type="text"
            placeholder="Cari nama karyawan..." style="width:180px;">
        <select id="filter-izin-status" class="ao-select">
            <option value="menunggu" selected>Menunggu Persetujuan</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
            <option value="">Semua</option>
        </select>
    `;

    panels[0]?.after(tb1);

    // Paginasi
    const bodies = document.querySelectorAll('.dash-panel .dash-panel-body');
    if (bodies[0] && !document.getElementById('paginasi-izin')) {
        bodies[0].insertAdjacentHTML('beforeend', '<div id="paginasi-izin"></div>');
    }
}

function injectModals() {
    if (document.getElementById('modal-reject-izin')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <!-- Modal Reject Izin -->
        <div id="modal-reject-izin" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title">Tolak Pengajuan Izin</h3>
                    <button data-close-modal="modal-reject-izin" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                        Tolak izin: <strong id="reject-izin-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Catatan Penolakan</label>
                        <textarea id="reject-izin-catatan" class="catatan-box"
                            placeholder="Jelaskan alasan penolakan..."></textarea>
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

        <!-- Modal Warning Approve Tanpa Dokumen -->
        <div id="modal-approve-warning" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:400px;">
                <div class="modal-header">
                    <h3 class="modal-title" style="color:#d97706;">⚠ Konfirmasi Persetujuan</h3>
                    <button data-close-modal="modal-approve-warning" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 20px;">
                        Izin <strong id="modal-approve-warning-nama" style="color:#0f172a;"></strong>
                        membutuhkan dokumen pendukung, namun belum ada dokumen yang diunggah.<br><br>
                        Anda tetap bisa menyetujui, tetapi pastikan sudah mendapatkan bukti fisik secara terpisah.
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

    // Event handlers modal
    document.getElementById('btn-konfirmasi-reject-izin')?.addEventListener('click', async () => {
        const catatan = getVal('reject-izin-catatan');
        closeModal('modal-reject-izin');
        await prosesIzin(selectedIzinId, 'ditolak', catatan);
    });

    document.getElementById('btn-tetap-approve')?.addEventListener('click', async () => {
        closeModal('modal-approve-warning');
        await prosesIzin(selectedIzinId, 'disetujui');
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    bindEvents();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function showSkeleton(tbodyId, cols, rows = 5) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array(rows).fill(`
        <tr>${Array(cols).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function getVal(id) { return document.getElementById(id)?.value ?? ''; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ''; }