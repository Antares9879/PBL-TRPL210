/**
 * resources/js/admin-outsource/planning.js
 * F08 — Daftar Planning Kerja Bulanan
 * F09 — Detail Jadwal Karyawan per Planning
 *
 * Panel kiri: daftar planning (riwayat + status)
 * Panel kanan: detail jadwal karyawan dari planning yang dipilih
 *
 * Endpoint:
 *   GET  /api/admin/planning          → daftar planning
 *   GET  /api/admin/planning/{id}     → detail + jadwal karyawan
 *   POST /api/admin/planning          → buat planning baru
 *   POST /api/admin/planning/{id}/upload-ulang → upload ulang versi baru
 */

import {
    apiFetch, esc, fmtTanggal, toast,
    openModal, closeModal,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

let currentPage     = 1;
let selectedPlanId  = null;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    updateTheads();
    injectToolbars();
    injectModalBuatPlanning();
    bindEvents();
    loadPlanning();
});

// ════════════════════════════════════════════════════════════════════════
//  PANEL KIRI: DAFTAR PLANNING
// ════════════════════════════════════════════════════════════════════════
async function loadPlanning(page = 1) {
    currentPage = page;
    showPlanningskeleton();

    try {
        const res  = await apiFetch(`/api/admin/planning?page=${page}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        renderPlanning(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-planning', loadPlanning);

    } catch (err) {
        console.error('[Planning] error:', err);
        toast('Gagal memuat data planning.', 'error');
    }
}

function renderPlanning(rows) {
    const container = document.getElementById('planning-list-panel');
    if (!container) return;

    if (!rows.length) {
        container.innerHTML = `
            <div style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
                Belum ada planning kerja dibuat.
                <br><br>
                <button id="btn-buat-planning-empty" class="btn-primary-sm" style="margin-top:8px;">
                    + Buat Planning Pertama
                </button>
            </div>`;
        document.getElementById('btn-buat-planning-empty')?.addEventListener('click', () => openModal('modal-buat-planning'));
        return;
    }

    container.innerHTML = rows.map((p, i) => {
        const statusCls = {
            aktif:      'planning-badge--aktif',
            draft:      'planning-badge--draft',
            diperbarui: 'planning-badge--diperbarui',
        }[p.status] ?? 'planning-badge--belum';

        const statusLabel = {
            aktif:      'Aktif',
            draft:      'Draft',
            diperbarui: 'Diperbarui',
        }[p.status] ?? '—';

        const iconCls = {
            aktif:      'planning-icon--aktif',
            draft:      'planning-icon--draft',
            diperbarui: 'planning-icon--diperbarui',
        }[p.status] ?? 'planning-icon--belum';

        return `
        <div class="planning-item planning-item--selectable ${selectedPlanId === p.id_planning ? 'planning-item--selected' : ''}"
            data-id="${p.id_planning}" style="cursor:pointer;">
            <div class="planning-icon ${iconCls}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                </svg>
            </div>
            <div class="planning-info">
                <span class="planning-label">${esc(p.periode_label ?? `Planning #${p.id_planning}`)}</span>
                <span class="planning-meta">
                    Versi ${p.versi ?? 1} · ${p.jumlah_karyawan_terjadwal ?? 0} karyawan terjadwal
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                <span class="planning-badge ${statusCls}">${statusLabel}</span>
                <button class="btn-upload-ulang" data-id="${p.id_planning}" data-periode="${esc(p.periode_label ?? '')}"
                    title="Upload ulang jadwal"
                    style="background:none;border:1px solid #e2e8f0;border-radius:6px;
                        padding:4px 8px;font-size:11px;color:#64748b;cursor:pointer;">
                    ↑ Update
                </button>
            </div>
        </div>`;
    }).join('');

    // Tambahkan style selected
    injectPlanningSelectedStyle();

    // Auto-select planning pertama
    if (!selectedPlanId && rows.length > 0) {
        selectedPlanId = rows[0].id_planning;
        loadDetailPlanning(selectedPlanId);
        highlightSelected(selectedPlanId);
    }
}

// ════════════════════════════════════════════════════════════════════════
//  PANEL KANAN: DETAIL JADWAL PER PLANNING
// ════════════════════════════════════════════════════════════════════════
async function loadDetailPlanning(id) {
    selectedPlanId = id;
    highlightSelected(id);
    showJadwalSkeleton();

    try {
        const res  = await apiFetch(`/api/admin/planning/${id}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        const planning = json.data;
        renderDetailHeader(planning);
        renderJadwal(planning.jadwal ?? []);

    } catch (err) {
        console.error('[Planning Detail] error:', err);
        toast('Gagal memuat detail planning.', 'error');
    }
}

function renderDetailHeader(p) {
    const header = document.getElementById('detail-planning-header');
    if (!header) return;

    header.innerHTML = `
        <div>
            <div class="dash-panel-title">${esc(p.periode_label ?? 'Detail Jadwal')}</div>
            <p class="dash-panel-subtitle">
                Versi ${p.versi ?? 1} · Dibuat ${fmtTanggal(p.created_at)} · ${p.jumlah_karyawan_terjadwal ?? 0} karyawan
            </p>
        </div>
        <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:999px;
            background:var(--amber-50);color:var(--amber-800);border:1px solid var(--amber-100);">
            F09
        </span>
    `;
}

function renderJadwal(jadwal) {
    const tbody = document.getElementById('tbody-jadwal-planning');
    if (!tbody) return;

    if (!jadwal.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada jadwal pada planning ini.</td></tr>`;
        return;
    }

    tbody.innerHTML = jadwal.map(j => `
        <tr>
            <td>
                <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(j.nama_karyawan ?? '—')}</div>
                <div style="font-size:11px;color:#94a3b8;">${esc(j.nomor_karyawan ?? '')}</div>
            </td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(j.tanggal_kerja)}</td>
            <td style="font-size:12px;color:#475569;">${esc(j.nama_shift ?? '—')}</td>
            <td style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#0f172a;">
                ${j.jam_masuk ? String(j.jam_masuk).slice(0,5) : '—'}</td>
            <td style="font-family:'Syne',sans-serif;font-size:12px;color:#475569;">
                ${j.jam_pulang ? String(j.jam_pulang).slice(0,5) : '—'}</td>
        </tr>
    `).join('');
}

// ════════════════════════════════════════════════════════════════════════
//  BUAT PLANNING BARU & UPLOAD ULANG
// ════════════════════════════════════════════════════════════════════════
async function buatPlanning() {
    const btn = document.getElementById('btn-simpan-planning');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const body = {
        bulan:       parseInt(getVal('p-bulan')),
        tahun:       parseInt(getVal('p-tahun')),
        keterangan:  getVal('p-keterangan') || null,
        jadwal:      parseJadwalInput(),
    };

    if (!body.jadwal.length) {
        toast('Minimal satu baris jadwal harus diisi.', 'warning');
        btn.disabled = false; btn.textContent = 'Simpan';
        return;
    }

    try {
        const res  = await apiFetch('/api/admin/planning', {
            method: 'POST',
            body: JSON.stringify(body),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-buat-planning');
            loadPlanning(1);
        } else {
            toast(json.message ?? 'Gagal menyimpan planning.', 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan Planning';
    }
}

async function uploadUlangPlanning(id, periode) {
    // Untuk saat ini: tampilkan konfirmasi sederhana
    if (!confirm(`Upload ulang jadwal untuk ${periode}?\n\nVersi sebelumnya akan diarsipkan sebagai "diperbarui".`)) return;

    const body = {
        keterangan: `Upload ulang pada ${new Date().toLocaleDateString('id-ID')}`,
        jadwal:     [],
    };

    try {
        const res  = await apiFetch(`/api/admin/planning/${id}/upload-ulang`, {
            method: 'POST',
            body: JSON.stringify(body),
        });
        const json = await res.json();

        json.status
            ? (toast(json.message, 'success'), loadPlanning(currentPage))
            : toast(json.message, 'error');
    } catch {
        toast('Gagal upload ulang.', 'error');
    }
}

// Parse jadwal dari textarea (format CSV sederhana per baris)
function parseJadwalInput() {
    const raw = getVal('p-jadwal-input').trim();
    if (!raw) return [];

    return raw.split('\n')
        .filter(line => line.trim())
        .map(line => {
            const parts = line.split(',').map(s => s.trim());
            return {
                id_karyawan:  parts[0] ?? '',
                tanggal_kerja: parts[1] ?? '',
                id_shift:     parts[2] ?? '',
            };
        })
        .filter(j => j.id_karyawan && j.tanggal_kerja);
}

// ── Event binding ─────────────────────────────────────────────────────────────
function bindEvents() {
    // Klik planning item → load detail
    document.getElementById('planning-list-panel')?.addEventListener('click', e => {
        const item = e.target.closest('.planning-item--selectable');
        const uploadBtn = e.target.closest('.btn-upload-ulang');

        if (uploadBtn) {
            e.stopPropagation();
            uploadUlangPlanning(parseInt(uploadBtn.dataset.id), uploadBtn.dataset.periode);
            return;
        }

        if (item) {
            loadDetailPlanning(parseInt(item.dataset.id));
        }
    });

    // Tombol buat planning
    document.getElementById('btn-buat-planning')?.addEventListener('click', () => openModal('modal-buat-planning'));

    // Form submit
    document.getElementById('form-buat-planning')?.addEventListener('submit', async e => {
        e.preventDefault(); await buatPlanning();
    });

    // Close modals
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── Inject UI ─────────────────────────────────────────────────────────────────
function updateTheads() {
    const t = document.querySelectorAll('.data-table thead tr')[0];
    if (t) t.innerHTML = `
        <th>Karyawan</th><th>Tanggal Kerja</th>
        <th>Shift</th><th>Jam Masuk</th><th>Jam Pulang</th>`;
}

function injectToolbars() {
    const panels = document.querySelectorAll('.dash-panel');
    if (!panels.length) return;

    // Panel kiri: ganti body dengan custom layout
    const leftBody = panels[0]?.querySelector('.dash-panel-body');
    if (leftBody && !document.getElementById('planning-list-panel')) {
        leftBody.innerHTML = `
            <div id="planning-list-panel" class="planning-list" style="display:flex;flex-direction:column;gap:8px;"></div>
            <div id="paginasi-planning"></div>
        `;
    }

    // Panel kanan: tambahkan header slot + toolbar
    const rightHeader = panels[1]?.querySelector('.dash-panel-header');
    if (rightHeader && !document.getElementById('detail-planning-header')) {
        rightHeader.innerHTML = '<div id="detail-planning-header"><div class="dash-panel-title">Pilih planning untuk melihat detail jadwal</div></div>';
    }

    // Tombol buat planning di page-header
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader && !document.getElementById('btn-buat-planning')) {
        const btn = document.createElement('button');
        btn.id = 'btn-buat-planning';
        btn.className = 'btn-primary';
        btn.style.cssText = 'padding:9px 18px;border:none;border-radius:9px;background:linear-gradient(135deg,#f59e0b,#d97706);font-family:\'DM Sans\',sans-serif;font-size:13px;font-weight:600;color:#fff;cursor:pointer;';
        btn.textContent = '+ Buat Planning';
        pageHeader.appendChild(btn);
    }
}

function injectModalBuatPlanning() {
    if (document.getElementById('modal-buat-planning')) return;

    // Buat opsi bulan
    const namaBulan = ['Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
    const bulanOpts = namaBulan.map((b, i) => {
        const bulanNow = new Date().getMonth() + 1;
        return `<option value="${i+1}" ${i+1 === bulanNow ? 'selected' : ''}>${b}</option>`;
    }).join('');

    const tahunOpts = [0, 1].map(n => {
        const t = new Date().getFullYear() + n;
        return `<option value="${t}" ${n === 0 ? 'selected' : ''}>${t}</option>`;
    }).join('');

    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-buat-planning" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:560px;">
                <div class="modal-header">
                    <h3 class="modal-title">Buat Planning Kerja</h3>
                    <button data-close-modal="modal-buat-planning" class="modal-close">×</button>
                </div>
                <form id="form-buat-planning" class="modal-body">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label">Bulan</label>
                            <select id="p-bulan" class="form-input">${bulanOpts}</select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tahun</label>
                            <select id="p-tahun" class="form-input">${tahunOpts}</select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keterangan (opsional)</label>
                        <input id="p-keterangan" type="text" class="form-input"
                            placeholder="Contoh: Jadwal reguler Maret 2025">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data Jadwal</label>
                        <p style="font-size:11px;color:#94a3b8;margin:0 0 8px;line-height:1.5;">
                            Format per baris: <code style="background:#f8fafc;padding:1px 5px;border-radius:3px;font-size:10px;">
                            id_karyawan, YYYY-MM-DD, id_shift</code><br>
                            Contoh: <code style="background:#f8fafc;padding:1px 5px;border-radius:3px;font-size:10px;">
                            1, 2025-03-01, 2</code>
                        </p>
                        <textarea id="p-jadwal-input" class="catatan-box" style="min-height:160px;font-family:monospace;font-size:12px;"
                            placeholder="1, 2025-03-01, 1&#10;1, 2025-03-02, 1&#10;2, 2025-03-01, 2"></textarea>
                        <p style="font-size:11px;color:#94a3b8;margin:6px 0 0;">
                            💡 Format JSON juga diterima backend secara langsung via API.
                            Input textarea ini adalah helper sederhana untuk menginput cepat.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-buat-planning" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-planning" class="btn-primary-sm">Simpan Planning</button>
                    </div>
                </form>
            </div>
        </div>
    `);

    document.getElementById('form-buat-planning')?.addEventListener('submit', async e => {
        e.preventDefault(); await buatPlanning();
    });
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── Skeleton helpers ──────────────────────────────────────────────────────────
function showPlanningskeleton() {
    const c = document.getElementById('planning-list-panel');
    if (!c) return;
    c.innerHTML = Array(4).fill(`
        <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;
            border-radius:10px;background:#f8fafc;border:1px solid #e8eaed;">
            <div class="skel" style="width:34px;height:34px;border-radius:9px;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                <div class="skel" style="height:10px;width:100px;border-radius:4px;"></div>
                <div class="skel" style="height:8px;width:140px;border-radius:4px;"></div>
            </div>
            <div class="skel" style="width:60px;height:22px;border-radius:999px;"></div>
        </div>
    `).join('');
}

function showJadwalSkeleton() {
    const tbody = document.getElementById('tbody-jadwal-planning');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>${Array(5).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>
    `).join('');
}

function highlightSelected(id) {
    document.querySelectorAll('.planning-item--selectable').forEach(el => {
        el.classList.toggle('planning-item--selected', parseInt(el.dataset.id) === id);
    });
}

function injectPlanningSelectedStyle() {
    if (document.getElementById('planning-selected-style')) return;
    const s = document.createElement('style');
    s.id = 'planning-selected-style';
    s.textContent = `
        .planning-item--selectable:hover { background: #f1f5f9; cursor: pointer; }
        .planning-item--selected {
            background: var(--amber-50) !important;
            border-color: var(--amber-200) !important;
        }
    `;
    document.head.appendChild(s);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function getVal(id) { return document.getElementById(id)?.value ?? ''; }