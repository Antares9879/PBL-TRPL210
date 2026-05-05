/**
 * resources/js/admin-outsource/validasi-absensi.js
 * F10 — Validasi Absensi & F11 — Riwayat Absensi
 *
 * Halaman validasi-absensi.blade.php dan riwayat-absensi.blade.php
 * menggunakan file JS ini (deteksi halaman via path).
 *
 * Endpoint:
 *   GET  /api/admin/validasi-absensi           → daftar absensi (filter: status_validasi, tanggal)
 *   POST /api/admin/validasi-absensi/{id}      → approve atau reject
 *
 * Riwayat:
 *   GET  /api/admin/validasi-absensi           → dengan filter lebih luas (bulan/tahun)
 */

import {
    apiFetch, esc, fmtWaktu, fmtTanggal, fmtMenit,
    toast, openModal, closeModal,
    badgeKehadiran, badgeValidasi,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

const isRiwayat = window.location.pathname.includes('riwayat');

let currentPage       = 1;
let filterTanggal     = '';
let filterKaryawan    = '';
let filterValidasi    = isRiwayat ? '' : 'menunggu';
let filterBulan       = new Date().getMonth() + 1;
let filterTahun       = new Date().getFullYear();
let debounceTimer     = null;
let selectedAbsensiId = null;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();

    if (isRiwayat) {
        initRiwayat();
    } else {
        initValidasi();
    }
});

// ════════════════════════════════════════════════════════════════════════
//  VALIDASI ABSENSI (F10)
// ════════════════════════════════════════════════════════════════════════
function initValidasi() {
    updateThead('validasi');
    injectToolbarValidasi();
    injectModalValidasi();
    loadAbsensi();
}

async function loadAbsensi(page = 1) {
    currentPage = page;
    showSkeleton(9, 'tbody-validasi-absensi');

    const params = new URLSearchParams({ page });
    if (filterValidasi)  params.set('status_validasi', filterValidasi);
    if (filterTanggal)   params.set('tanggal',          filterTanggal);
    if (filterKaryawan)  params.set('search',            filterKaryawan);

    try {
        const res  = await apiFetch(`/api/admin/validasi-absensi?${params}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); showEmpty(9, 'tbody-validasi-absensi', 'Gagal memuat data.'); return; }

        renderValidasi(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-absensi', loadAbsensi);

    } catch (err) {
        console.error('[ValidasiAbsensi] error:', err);
        toast('Gagal terhubung ke server.', 'error');
    }
}

function renderValidasi(rows) {
    const tbody = document.getElementById('tbody-validasi-absensi');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada absensi ${filterValidasi === 'menunggu' ? 'yang menunggu validasi' : ''} ditemukan.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const namaKaryawan = row.nama_karyawan ?? row.karyawan?.nama_lengkap ?? '—';
        const nomorKaryawan = row.nomor_karyawan ?? row.karyawan?.nomor_karyawan ?? '';
        const namaShift = row.nama_shift ?? row.shift?.nama_shift ?? '—';
        const lokasiValid = getLokasiValid(row);

        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#87dc87;">
                        ${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(nomorKaryawan)}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_absensi)}</td>
            <td style="font-size:12px;color:#475569;">${esc(namaShift)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${fmtWaktu(row.waktu_check_in)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                ${fmtWaktu(row.waktu_check_out)}</td>
            <td>${renderLokasiBadge(lokasiValid)}</td>
            <td>
                ${(row.menit_telat ?? 0) > 0
                    ? `<span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#d97706;">
                           +${row.menit_telat} mnt</span>`
                    : `<span style="color:#94a3b8;font-size:12px;">—</span>`
                }
            </td>
            <td>${badgeValidasi(row.status_validasi)}</td>
            <td>
                ${row.status_validasi === 'menunggu'
                    ? `<div style="display:flex;gap:5px;">
                            <button class="btn-approve btn-do-approve"
                                data-id="${row.id_absensi}" data-nama="${esc(namaKaryawan)}">
                                ✓ Setujui
                            </button>
                            <button class="btn-reject btn-do-reject"
                                data-id="${row.id_absensi}" data-nama="${esc(namaKaryawan)}">
                                ✕ Tolak
                            </button>
                       </div>`
                    : `<span style="font-size:12px;color:#94a3b8;">Sudah diproses</span>`
                }
            </td>
        </tr>
    `;
    }).join('');
}

async function prosesValidasi(id, aksi, catatan = '') {
    try {
        const res  = await apiFetch(`/api/admin/validasi-absensi/${id}`, {
            method: 'POST',
            body: JSON.stringify({
                aksi,
                ...(aksi === 'reject' ? { catatan_penolakan: catatan } : {}),
            }),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses validasi.', 'error');
    }
}

function injectToolbarValidasi() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-validasi-status')) return;

    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan-absensi" class="ao-search" type="text"
            placeholder="Cari nama karyawan..." style="width:200px;">
        <input id="filter-tanggal" type="date" class="ao-select"
            style="padding:7px 12px;" placeholder="Pilih tanggal...">
        <select id="filter-validasi-status" class="ao-select">
            <option value="">Semua Status</option>
            <option value="menunggu" selected>Menunggu Validasi</option>
            <option value="disetujui">Sudah Disetujui</option>
            <option value="ditolak">Ditolak</option>
        </select>
        <button id="btn-reset-filter-absensi" style="padding:7px 12px;border:1px solid #e2e8f0;
            border-radius:8px;background:#fff;font-size:12px;color:#64748b;cursor:pointer;">
            Reset
        </button>
    `;
    header.after(wrap);

    // Paginasi container
    const panel = document.querySelector('.dash-panel-body');
    if (panel && !document.getElementById('paginasi-absensi')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-absensi"></div>');
    }

    // Events
    wrap.querySelector('#search-karyawan-absensi')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { filterKaryawan = e.target.value.trim(); loadAbsensi(1); }, 400);
    });
    wrap.querySelector('#filter-tanggal')?.addEventListener('change', e => {
        filterTanggal = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#filter-validasi-status')?.addEventListener('change', e => {
        filterValidasi = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#btn-reset-filter-absensi')?.addEventListener('click', () => {
        filterTanggal   = '';
        filterKaryawan  = '';
        filterValidasi  = 'menunggu';
        wrap.querySelector('#filter-tanggal').value       = '';
        wrap.querySelector('#search-karyawan-absensi').value = '';
        wrap.querySelector('#filter-validasi-status').value = 'menunggu';
        loadAbsensi(1);
    });

    // TIDAK set default tanggal - biarkan kosong untuk menampilkan semua data
    // filterTanggal = ''; // Sudah di-init di atas

    // Delegasi tabel
    document.querySelector('.dash-panel--full')?.addEventListener('click', async e => {
        const approveBtn = e.target.closest('.btn-do-approve');
        const rejectBtn  = e.target.closest('.btn-do-reject');

        if (approveBtn) {
            selectedAbsensiId = parseInt(approveBtn.dataset.id);
            await prosesValidasi(selectedAbsensiId, 'approve');
        }

        if (rejectBtn) {
            selectedAbsensiId = parseInt(rejectBtn.dataset.id);
            document.getElementById('reject-absensi-nama').textContent = rejectBtn.dataset.nama;
            setVal('reject-absensi-catatan', '');
            openModal('modal-reject-absensi');
        }
    });
}

function injectModalValidasi() {
    if (document.getElementById('modal-reject-absensi')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-reject-absensi" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title">Tolak Absensi</h3>
                    <button data-close-modal="modal-reject-absensi" class="modal-close">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                        Tolak absensi: <strong id="reject-absensi-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Catatan Penolakan</label>
                        <textarea id="reject-absensi-catatan" class="catatan-box"
                            placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-reject-absensi" class="btn-cancel">Batal</button>
                        <button type="button" id="btn-konfirmasi-reject-absensi"
                            style="padding:9px 20px;border:none;border-radius:8px;
                                background:#dc2626;font-family:'DM Sans',sans-serif;
                                font-size:13px;font-weight:600;color:#fff;cursor:pointer;">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);

    document.getElementById('btn-konfirmasi-reject-absensi')?.addEventListener('click', async () => {
        const catatan = getVal('reject-absensi-catatan').trim();
        if (!catatan) {
            toast('Catatan penolakan wajib diisi.', 'warning');
            return;
        }
        closeModal('modal-reject-absensi');
        await prosesValidasi(selectedAbsensiId, 'reject', catatan);
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ════════════════════════════════════════════════════════════════════════
//  RIWAYAT ABSENSI (F11)
// ════════════════════════════════════════════════════════════════════════
function initRiwayat() {
    updateThead('riwayat');
    injectToolbarRiwayat();
    loadRiwayat();
}

async function loadRiwayat(page = 1) {
    currentPage = page;
    showSkeleton(9, 'tbody-riwayat-absensi');

    const params = new URLSearchParams({ page, bulan: filterBulan, tahun: filterTahun });
    if (filterKaryawan) params.set('search', filterKaryawan);

    try {
        const res  = await apiFetch(`/api/admin/validasi-absensi?${params}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); showEmpty(9, 'tbody-riwayat-absensi', 'Tidak ada data.'); return; }

        renderRiwayat(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-riwayat', loadRiwayat);

    } catch (err) {
        console.error('[Riwayat] error:', err);
    }
}

function renderRiwayat(rows) {
    const tbody = document.getElementById('tbody-riwayat-absensi');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada data absensi pada periode ini.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => `
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
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_absensi)}</td>
            <td style="font-size:12px;color:#475569;">${esc(row.nama_shift ?? '—')}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${fmtWaktu(row.waktu_check_in)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                ${fmtWaktu(row.waktu_check_out)}</td>
            <td style="font-size:12px;color:#475569;">${fmtMenit(row.menit_kerja_normal)}</td>
            <td>
                ${(row.menit_telat ?? 0) > 0
                    ? `<span style="font-size:12px;font-weight:600;color:#d97706;">+${row.menit_telat} mnt</span>`
                    : `<span style="color:#94a3b8;font-size:12px;">—</span>`
                }
            </td>
            <td>${badgeKehadiran(row.status_kehadiran)}</td>
            <td>${badgeValidasi(row.status_validasi)}</td>
        </tr>
    `).join('');
}

function injectToolbarRiwayat() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-bulan-riwayat')) return;

    // Buat opsi bulan
    const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const bulanOpts = namaBulan.map((b, i) =>
        `<option value="${i+1}" ${i+1 === filterBulan ? 'selected' : ''}>${b}</option>`
    ).join('');

    // Tahun: 3 tahun ke belakang
    const tahunOpts = [0,1,2].map(n => {
        const t = new Date().getFullYear() - n;
        return `<option value="${t}" ${t === filterTahun ? 'selected' : ''}>${t}</option>`;
    }).join('');

    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan-riwayat" class="ao-search" type="text"
            placeholder="Cari nama karyawan..." style="width:200px;">
        <select id="filter-bulan-riwayat" class="ao-select">${bulanOpts}</select>
        <select id="filter-tahun-riwayat" class="ao-select">${tahunOpts}</select>
    `;
    header.after(wrap);

    const panel = document.querySelector('.dash-panel-body');
    if (panel && !document.getElementById('paginasi-riwayat')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-riwayat"></div>');
    }

    wrap.querySelector('#search-karyawan-riwayat')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { filterKaryawan = e.target.value.trim(); loadRiwayat(1); }, 400);
    });
    wrap.querySelector('#filter-bulan-riwayat')?.addEventListener('change', e => { filterBulan = parseInt(e.target.value); loadRiwayat(1); });
    wrap.querySelector('#filter-tahun-riwayat')?.addEventListener('change', e => { filterTahun = parseInt(e.target.value); loadRiwayat(1); });
}

// ── Shared helpers ────────────────────────────────────────────────────────────
function updateThead(mode) {
    const sel = mode === 'riwayat' ? '#tabel-riwayat-absensi thead tr' : '.data-table thead tr';
    const thead = document.querySelector(sel) ?? document.querySelector('.data-table thead tr');
    if (!thead) return;

    if (mode === 'riwayat') {
        thead.innerHTML = `
            <th>Karyawan</th><th>Tanggal</th><th>Shift</th>
            <th>Check-In</th><th>Check-Out</th>
            <th>Menit Normal</th><th>Menit Telat</th>
            <th>Status Kehadiran</th><th>Status Validasi</th>`;
    } else {
        thead.innerHTML = `
            <th>Karyawan</th><th>Tanggal</th><th>Shift</th>
            <th>Check-In</th><th>Check-Out</th>
            <th>Lokasi Valid</th><th>Menit Telat</th>
            <th>Status</th><th>Aksi</th>`;
    }
}

function showSkeleton(cols, tbodyId) {
    const tbody = document.getElementById(tbodyId) ?? document.querySelector('.data-table tbody');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>${Array(cols).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function showEmpty(cols, tbodyId, msg) {
    const tbody = document.getElementById(tbodyId) ?? document.querySelector('.data-table tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">${msg}</td></tr>`;
}

function getVal(id) { return document.getElementById(id)?.value ?? ''; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ''; }

function getLokasiValid(row) {
    const inValid = row.is_lokasi_valid_in;
    const outValid = row.is_lokasi_valid_out;

    if (outValid === null || outValid === undefined) {
        if (inValid === null || inValid === undefined) return null;
        return Boolean(inValid);
    }
    return Boolean(inValid) && Boolean(outValid);
}

function renderLokasiBadge(valid) {
    if (valid === null || valid === undefined) {
        return `<span class="badge badge--neutral">-</span>`;
    }
    return valid
        ? `<span class="badge badge--success">Valid</span>`
        : `<span class="badge badge--danger">Tidak Valid</span>`;
}
