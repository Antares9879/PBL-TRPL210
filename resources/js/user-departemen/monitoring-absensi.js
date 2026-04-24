/**
 * resources/js/user-departemen/monitoring-absensi.js
 * Monitoring Absensi Karyawan Departemen (Read-Only)
 *
 * Endpoint:
 *   GET /api/departemen/dashboard/absensi           → daftar absensi (filter: tanggal, status, karyawan)
 *   GET /api/departemen/dashboard/absensi/{id}      → detail satu absensi
 *   GET /api/departemen/dashboard/daftar-karyawan   → dropdown filter karyawan
 */

import {
    apiFetch, esc, fmtWaktu, fmtTanggal, fmtMenit,
    toast, openModal, closeModal,
    badgeKehadiran, badgeValidasi,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

let currentPage       = 1;
let filterTanggalDari = '';
let filterTanggalSampai = '';
let filterStatus      = '';
let filterKaryawan    = '';
let debounceTimer     = null;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    injectToolbar();
    injectModal();
    loadDaftarKaryawan();
    loadAbsensi();
});

// ════════════════════════════════════════════════════════════════════════
//  LOAD & RENDER
// ════════════════════════════════════════════════════════════════════════
async function loadAbsensi(page = 1) {
    currentPage = page;
    showSkeleton();

    const params = new URLSearchParams({ page });
    if (filterTanggalDari)   params.set('tanggal_dari', filterTanggalDari);
    if (filterTanggalSampai) params.set('tanggal_sampai', filterTanggalSampai);
    if (filterStatus)        params.set('status_kehadiran', filterStatus);
    if (filterKaryawan)      params.set('id_karyawan', filterKaryawan);

    try {
        const res  = await apiFetch(`/api/departemen/dashboard/absensi?${params}`);
        const json = await res.json();
        if (!json.status) {
            toast(json.message, 'error');
            showEmpty('Gagal memuat data.');
            return;
        }

        renderAbsensi(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-absensi', loadAbsensi);

    } catch (err) {
        console.error('[MonitoringAbsensi] error:', err);
        toast('Gagal terhubung ke server.', 'error');
        showEmpty('Gagal terhubung ke server.');
    }
}

function renderAbsensi(rows) {
    const tbody = document.getElementById('tbody-monitoring-absensi');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada data absensi ditemukan.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const namaKaryawan = row.karyawan?.nama_lengkap ?? row.nama_karyawan ?? '—';
        const nomorKaryawan = row.karyawan?.nomor_karyawan ?? row.nomor_karyawan ?? '';
        const namaShift = row.shift?.nama_shift ?? row.nama_shift ?? '—';

        return `
        <tr style="cursor:pointer;" class="row-absensi" data-id="${row.id_absensi}">
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
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_absensi)}</td>
            <td style="font-size:12px;color:#475569;">${esc(namaShift)}</td>
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
    `;
    }).join('');

    // Event listener untuk klik row
    tbody.querySelectorAll('.row-absensi').forEach(row => {
        row.addEventListener('click', () => {
            const id = parseInt(row.dataset.id);
            loadDetailAbsensi(id);
        });
    });
}

// ════════════════════════════════════════════════════════════════════════
//  DAFTAR KARYAWAN (untuk dropdown filter)
// ════════════════════════════════════════════════════════════════════════
async function loadDaftarKaryawan() {
    try {
        const res  = await apiFetch('/api/departemen/dashboard/daftar-karyawan');
        const json = await res.json();
        if (!json.status) return;

        const select = document.getElementById('filter-karyawan');
        if (!select) return;

        const options = json.data.map(k => 
            `<option value="${k.id_karyawan}">${esc(k.nama_lengkap)} (${esc(k.nomor_karyawan)})</option>`
        ).join('');

        select.innerHTML = `<option value="">Semua Karyawan</option>${options}`;

    } catch (err) {
        console.error('[DaftarKaryawan] error:', err);
    }
}

// ════════════════════════════════════════════════════════════════════════
//  DETAIL ABSENSI
// ════════════════════════════════════════════════════════════════════════
async function loadDetailAbsensi(id) {
    const content = document.getElementById('detail-absensi-content');
    if (!content) return;

    content.innerHTML = '<p style="text-align:center;color:#94a3b8;">Memuat data...</p>';
    openModal('modal-detail-absensi');

    try {
        const res  = await apiFetch(`/api/departemen/dashboard/absensi/${id}`);
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
                    <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;">
                        ${esc(d.karyawan?.nomor_karyawan ?? '')} · 
                        ${esc(d.karyawan?.posisi ?? '')} · 
                        ${esc(d.karyawan?.departemen ?? '')}
                    </p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Tanggal</label>
                        <p style="font-size:14px;color:#0f172a;margin:0;">${fmtTanggal(d.tanggal_absensi)}</p>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Shift</label>
                        <p style="font-size:14px;color:#0f172a;margin:0;">${esc(d.shift?.nama_shift ?? '—')}</p>
                        <p style="font-size:11px;color:#94a3b8;margin:2px 0 0;">
                            ${d.shift?.jam_masuk ?? '—'} - ${d.shift?.jam_pulang ?? '—'}
                        </p>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Check-In</label>
                        <p style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">${fmtWaktu(d.waktu_check_in)}</p>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Check-Out</label>
                        <p style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">${fmtWaktu(d.waktu_check_out)}</p>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Menit Normal</label>
                        <p style="font-size:16px;font-weight:700;color:#0f766e;margin:0;">${d.menit_kerja_normal ?? 0} mnt</p>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Menit Telat</label>
                        <p style="font-size:16px;font-weight:700;color:#d97706;margin:0;">${d.menit_telat ?? 0} mnt</p>
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Menit Kelebihan</label>
                        <p style="font-size:16px;font-weight:700;color:#2563eb;margin:0;">${d.menit_kelebihan ?? 0} mnt</p>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Status Kehadiran</label>
                        ${badgeKehadiran(d.status_kehadiran)}
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px;">Status Validasi</label>
                        ${badgeValidasi(d.status_validasi)}
                    </div>
                </div>
                ${d.pengajuan_lembur && d.pengajuan_lembur.length > 0 ? `
                <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:12px;">
                    <label style="font-size:11px;font-weight:600;color:#0f766e;text-transform:uppercase;display:block;margin-bottom:8px;">Pengajuan Lembur Terkait</label>
                    ${d.pengajuan_lembur.map(l => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #ccfbf1;">
                            <div>
                                <p style="font-size:12px;color:#0f172a;margin:0;font-weight:500;">${l.alasan_lembur ?? '—'}</p>
                                <p style="font-size:11px;color:#64748b;margin:2px 0 0;">
                                    Diajukan: ${l.menit_lembur_diajukan ?? 0} mnt · 
                                    Resmi: ${l.menit_lembur_resmi ?? 0} mnt
                                </p>
                            </div>
                            <span class="lembur-badge lembur-badge--${l.status}">${l.status}</span>
                        </div>
                    `).join('')}
                </div>
                ` : ''}
            </div>
        `;
    } catch (err) {
        console.error('[DetailAbsensi] error:', err);
        content.innerHTML = '<p style="text-align:center;color:#ef4444;">Gagal memuat detail.</p>';
    }
}

// ════════════════════════════════════════════════════════════════════════
//  TOOLBAR & MODAL
// ════════════════════════════════════════════════════════════════════════
function injectToolbar() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-karyawan')) return;

    const wrap = document.createElement('div');
    wrap.className = 'dept-toolbar';
    wrap.innerHTML = `
        <select id="filter-karyawan" class="dept-select" style="min-width:220px;">
            <option value="">Semua Karyawan</option>
        </select>
        <input id="filter-tanggal-dari" type="date" class="dept-select"
            style="padding:7px 12px;">
        <input id="filter-tanggal-sampai" type="date" class="dept-select"
            style="padding:7px 12px;">
        <select id="filter-status-kehadiran" class="dept-select">
            <option value="">Semua Status</option>
            <option value="hadir">Hadir</option>
            <option value="telat">Telat</option>
            <option value="izin">Izin</option>
            <option value="alpa">Alpa</option>
            <option value="pending">Pending</option>
        </select>
        <button id="btn-reset-filter-absensi" class="btn-secondary">
            Reset
        </button>
    `;
    header.after(wrap);

    // Paginasi container
    const panel = document.querySelector('.dash-panel-body');
    if (panel && !document.getElementById('paginasi-absensi')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-absensi"></div>');
    }

    // Set default: 7 hari terakhir
    const today = new Date();
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 6);
    
    filterTanggalDari = weekAgo.toISOString().slice(0, 10);
    filterTanggalSampai = today.toISOString().slice(0, 10);
    
    wrap.querySelector('#filter-tanggal-dari').value = filterTanggalDari;
    wrap.querySelector('#filter-tanggal-sampai').value = filterTanggalSampai;

    // Events
    wrap.querySelector('#filter-karyawan')?.addEventListener('change', e => {
        filterKaryawan = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#filter-tanggal-dari')?.addEventListener('change', e => {
        filterTanggalDari = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#filter-tanggal-sampai')?.addEventListener('change', e => {
        filterTanggalSampai = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#filter-status-kehadiran')?.addEventListener('change', e => {
        filterStatus = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#btn-reset-filter-absensi')?.addEventListener('click', () => {
        const today = new Date();
        const weekAgo = new Date(today);
        weekAgo.setDate(weekAgo.getDate() - 6);
        
        filterTanggalDari   = weekAgo.toISOString().slice(0, 10);
        filterTanggalSampai = today.toISOString().slice(0, 10);
        filterStatus        = '';
        filterKaryawan      = '';
        
        wrap.querySelector('#filter-tanggal-dari').value = filterTanggalDari;
        wrap.querySelector('#filter-tanggal-sampai').value = filterTanggalSampai;
        wrap.querySelector('#filter-status-kehadiran').value = '';
        wrap.querySelector('#filter-karyawan').value = '';
        loadAbsensi(1);
    });
}

function injectModal() {
    if (document.getElementById('modal-detail-absensi')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-detail-absensi" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:540px;">
                <div class="modal-header">
                    <h3 class="modal-title">Detail Absensi</h3>
                    <button data-close-modal="modal-detail-absensi" class="modal-close">×</button>
                </div>
                <div class="modal-body" id="detail-absensi-content">
                    <p style="text-align:center;color:#94a3b8;">Memuat data...</p>
                </div>
            </div>
        </div>
    `);

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function showSkeleton() {
    const tbody = document.getElementById('tbody-monitoring-absensi');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>${Array(9).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function showEmpty(msg) {
    const tbody = document.getElementById('tbody-monitoring-absensi');
    if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">${msg}</td></tr>`;
}
