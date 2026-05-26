/**
 * resources/js/user-departemen/validasi-lembur.js
 * F12 — Validasi Pengajuan Lembur
 *
 * Endpoint:
 *   GET  /api/departemen/validasi-lembur           → daftar pengajuan lembur (filter: status, tanggal, nama)
 *   POST /api/departemen/validasi-lembur/{id}/proses → approve atau reject
 */

import {
    apiFetch, esc, fmtTanggal, fmtMenit,
    toast, openModal, closeModal,
    badgeLembur,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

let currentPage       = 1;
let filterStatus      = 'menunggu';
let filterTanggalDari = '';
let filterTanggalSampai = '';
let filterKaryawan    = '';
let debounceTimer     = null;
let selectedLemburId  = null;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    injectToolbar();
    injectModal();
    loadLembur();
});

// ════════════════════════════════════════════════════════════════════════
//  LOAD & RENDER
// ════════════════════════════════════════════════════════════════════════
async function loadLembur(page = 1) {
    currentPage = page;
    showSkeleton();

    const params = new URLSearchParams({ page });
    const normalizedStatus = filterStatus === '' ? 'semua' : filterStatus;
    if (normalizedStatus)    params.set('status', normalizedStatus);
    if (filterTanggalDari)   params.set('tanggal_dari', filterTanggalDari);
    if (filterTanggalSampai) params.set('tanggal_sampai', filterTanggalSampai);
    if (filterKaryawan)      params.set('search', filterKaryawan);

    try {
        const res  = await apiFetch(`/api/departemen/validasi-lembur?${params}`);
        const json = await res.json();
        if (!json.status) {
            toast(json.message, 'error');
            showEmpty('Gagal memuat data.');
            return;
        }

        renderLembur(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-lembur', loadLembur);

    } catch (err) {
        console.error('[ValidasiLembur] error:', err);
        toast('Gagal terhubung ke server.', 'error');
        showEmpty('Gagal terhubung ke server.');
    }
}

function renderLembur(rows) {
    const tbody = document.getElementById('tbody-validasi-lembur');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada pengajuan lembur ${filterStatus === 'menunggu' ? 'yang menunggu validasi' : ''} ditemukan.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const namaKaryawan = row.karyawan?.nama_lengkap ?? '—';
        const nomorKaryawan = row.karyawan?.nomor_karyawan ?? '';
        const departemen = row.karyawan?.departemen?.nama_departemen ?? '';
        const perusahaan = row.karyawan?.perusahaan ?? '';

        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                        background:linear-gradient(135deg,#0f766e,#042f2e);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#5eead4;">
                        ${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(nomorKaryawan)}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_lembur)}</td>
            <td style="font-size:12px;color:#475569;">
                ${row.jam_mulai_estimasi ?? '—'} - ${row.jam_selesai_estimasi ?? '—'}
            </td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${row.menit_lembur_diajukan ?? 0} mnt
            </td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                ${row.menit_lembur_resmi ?? 0} mnt
            </td>
            <td style="font-size:12px;color:#475569;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                ${esc(row.alasan_lembur ?? '—')}
            </td>
            <td>${badgeLembur(row.status)}</td>
            <td>
                ${row.status === 'menunggu'
                    ? `<div style="display:flex;gap:5px;">
                            <button class="btn-approve btn-do-approve"
                                data-id="${row.id_lembur}" data-nama="${esc(namaKaryawan)}">
                                ✓ Setujui
                            </button>
                            <button class="btn-reject btn-do-reject"
                                data-id="${row.id_lembur}" data-nama="${esc(namaKaryawan)}">
                                ✕ Tolak
                            </button>
                            <button class="btn-detail btn-do-detail"
                                data-id="${row.id_lembur}">
                                Detail
                            </button>
                       </div>`
                    : `<span style="font-size:12px;color:#94a3b8;">Sudah diproses</span>`
                }
            </td>
        </tr>
    `;
    }).join('');
}

// ════════════════════════════════════════════════════════════════════════
//  PROSES VALIDASI
// ════════════════════════════════════════════════════════════════════════
async function prosesValidasi(id, aksi, catatan = '') {
    try {
        const res  = await apiFetch(`/api/departemen/validasi-lembur/${id}/proses`, {
            method: 'POST',
            body: JSON.stringify({
                aksi,
                ...(aksi === 'reject' ? { catatan_penolakan: catatan } : {}),
            }),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            loadLembur(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses validasi.', 'error');
    }
}

// ════════════════════════════════════════════════════════════════════════
//  TOOLBAR & MODAL
// ════════════════════════════════════════════════════════════════════════
function injectToolbar() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-status-lembur')) return;

    const wrap = document.createElement('div');
    wrap.className = 'dept-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan-lembur" class="dept-search" type="text"
            placeholder="Cari nama karyawan..." style="width:200px;">
        <input id="filter-tanggal-dari" type="date" class="dept-select"
            style="padding:7px 12px;">
        <input id="filter-tanggal-sampai" type="date" class="dept-select"
            style="padding:7px 12px;">
        <select id="filter-status-lembur" class="dept-select">
            <option value="menunggu" selected>Menunggu Validasi</option>
            <option value="disetujui">Sudah Disetujui</option>
            <option value="ditolak">Ditolak</option>
            <option value="kadaluarsa">Kadaluarsa</option>
            <option value="semua">Semua Status</option>
        </select>
        <button id="btn-reset-filter-lembur" class="btn-secondary">
            Reset
        </button>
    `;
    header.after(wrap);

    // Paginasi container
    const panel = document.querySelector('.dash-panel-body');
    if (panel && !document.getElementById('paginasi-lembur')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-lembur"></div>');
    }

    // Events
    wrap.querySelector('#search-karyawan-lembur')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { filterKaryawan = e.target.value.trim(); loadLembur(1); }, 400);
    });
    wrap.querySelector('#filter-tanggal-dari')?.addEventListener('change', e => {
        filterTanggalDari = e.target.value; loadLembur(1);
    });
    wrap.querySelector('#filter-tanggal-sampai')?.addEventListener('change', e => {
        filterTanggalSampai = e.target.value; loadLembur(1);
    });
    wrap.querySelector('#filter-status-lembur')?.addEventListener('change', e => {
        filterStatus = e.target.value === '' ? 'semua' : e.target.value;
        loadLembur(1);
    });
    wrap.querySelector('#btn-reset-filter-lembur')?.addEventListener('click', () => {
        filterTanggalDari   = '';
        filterTanggalSampai = '';
        filterKaryawan      = '';
        filterStatus        = 'menunggu';
        wrap.querySelector('#filter-tanggal-dari').value = '';
        wrap.querySelector('#filter-tanggal-sampai').value = '';
        wrap.querySelector('#search-karyawan-lembur').value = '';
        wrap.querySelector('#filter-status-lembur').value = 'menunggu';
        loadLembur(1);
    });

    // Delegasi tabel
    document.querySelector('.dash-panel--full')?.addEventListener('click', async e => {
        const approveBtn = e.target.closest('.btn-do-approve');
        const rejectBtn  = e.target.closest('.btn-do-reject');
        const detailBtn  = e.target.closest('.btn-do-detail');

        if (approveBtn) {
            selectedLemburId = parseInt(approveBtn.dataset.id);
            await prosesValidasi(selectedLemburId, 'approve');
        }

        if (rejectBtn) {
            selectedLemburId = parseInt(rejectBtn.dataset.id);
            document.getElementById('reject-lembur-nama').textContent = rejectBtn.dataset.nama;
            document.getElementById('reject-lembur-catatan').value = '';
            openModal('modal-reject-lembur');
        }

        if (detailBtn) {
            selectedLemburId = parseInt(detailBtn.dataset.id);
            await loadDetailLembur(selectedLemburId);
        }
    });
}

function injectModal() {
    if (document.getElementById('modal-reject-lembur')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-reject-lembur" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title">Tolak Pengajuan Lembur</h3>
                    <button data-close-modal="modal-reject-lembur" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                        Tolak pengajuan lembur: <strong id="reject-lembur-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Catatan Penolakan</label>
                        <textarea id="reject-lembur-catatan" class="catatan-box"
                            placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-reject-lembur" class="btn-cancel">Batal</button>
                        <button type="button" id="btn-konfirmasi-reject-lembur"
                            style="padding:9px 20px;border:none;border-radius:8px;
                                background:#dc2626;font-family:'DM Sans',sans-serif;
                                font-size:13px;font-weight:600;color:#fff;cursor:pointer;">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-detail-lembur" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:540px;">
                <div class="modal-header">
                    <h3 class="modal-title">Detail Pengajuan Lembur</h3>
                    <button data-close-modal="modal-detail-lembur" class="modal-close">×</button>
                </div>
                <div class="modal-body" id="detail-lembur-content">
                    <p style="text-align:center;color:#94a3b8;">Memuat data...</p>
                </div>
            </div>
        </div>
    `);

    document.getElementById('btn-konfirmasi-reject-lembur')?.addEventListener('click', async () => {
        const catatan = document.getElementById('reject-lembur-catatan').value.trim();
        if (!catatan) {
            toast('Catatan penolakan wajib diisi.', 'warning');
            return;
        }
        closeModal('modal-reject-lembur');
        await prosesValidasi(selectedLemburId, 'reject', catatan);
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

async function loadDetailLembur(id) {
    const content = document.getElementById('detail-lembur-content');
    if (!content) return;

    content.innerHTML = '<p style="text-align:center;color:#94a3b8;">Memuat data...</p>';
    openModal('modal-detail-lembur');

    try {
        const res  = await apiFetch(`/api/departemen/validasi-lembur/${id}`);
        const json = await res.json();
        if (!json.status) {
            content.innerHTML = `<p style="text-align:center;color:#ef4444;">${json.message}</p>`;
            return;
        }

        const d = json.data;
        content.innerHTML = `
            <div style="display:grid;gap:14px;">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Karyawan</label>
                    <p style="font-size:14px;font-weight:500;color:#0f172a;margin:0;">${esc(d.karyawan?.nama_lengkap ?? '—')}</p>
                    <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;">${esc(d.karyawan?.nomor_karyawan ?? '')} · ${esc(d.karyawan?.departemen?.nama_departemen ?? '')}</p>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Tanggal Lembur</label>
                    <p style="font-size:14px;color:#0f172a;margin:0;">${fmtTanggal(d.tanggal_lembur)}</p>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Jam Estimasi</label>
                    <p style="font-size:14px;color:#0f172a;margin:0;">${d.jam_mulai_estimasi ?? '—'} - ${d.jam_selesai_estimasi ?? '—'}</p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Menit Diajukan</label>
                        <p style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">${d.menit_lembur_diajukan ?? 0} mnt</p>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Menit Resmi</label>
                        <p style="font-size:18px;font-weight:700;color:#0f766e;margin:0;">${d.menit_lembur_resmi ?? 0} mnt</p>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Alasan Lembur</label>
                    <p style="font-size:13px;color:#0f172a;margin:0;line-height:1.5;">${esc(d.alasan_lembur ?? '—')}</p>
                </div>
                ${d.absensi_referensi ? `
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:8px;">Data Absensi Referensi</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px;">
                        <div><span style="color:#94a3b8;">Check-In:</span> <strong>${d.absensi_referensi.waktu_check_in ?? '—'}</strong></div>
                        <div><span style="color:#94a3b8;">Check-Out:</span> <strong>${d.absensi_referensi.waktu_check_out ?? '—'}</strong></div>
                        <div><span style="color:#94a3b8;">Menit Normal:</span> <strong>${d.absensi_referensi.menit_kerja_normal ?? 0} mnt</strong></div>
                        <div><span style="color:#94a3b8;">Menit Kelebihan:</span> <strong>${d.absensi_referensi.menit_kelebihan ?? 0} mnt</strong></div>
                    </div>
                </div>
                ` : ''}
                <div>
                    <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Status</label>
                    ${badgeLembur(d.status)}
                </div>
                ${d.catatan_penolakan ? `
                <div style="background:#fef2f2;border:1px solid#fecaca;border-radius:10px;padding:12px;">
                    <label style="font-size:11px;font-weight:600;color:#b91c1c;text-transform:uppercase;display:block;margin-bottom:4px;">Catatan Penolakan</label>
                    <p style="font-size:13px;color:#7f1d1d;margin:0;line-height:1.5;">${esc(d.catatan_penolakan)}</p>
                </div>
                ` : ''}
            </div>
        `;
    } catch (err) {
        console.error('[DetailLembur] error:', err);
        content.innerHTML = '<p style="text-align:center;color:#ef4444;">Gagal memuat detail.</p>';
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function showSkeleton() {
    const tbody = document.getElementById('tbody-validasi-lembur');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>${Array(8).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function showEmpty(msg) {
    const tbody = document.getElementById('tbody-validasi-lembur');
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">${msg}</td></tr>`;
}
