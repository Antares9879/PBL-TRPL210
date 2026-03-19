/**
 * resources/js/admin-outsource/planning.js
 * F08 — Input Planning Kerja via Upload Excel + Grid Interaktif
 * F09 — Upload Ulang dengan Preview Diff
 *
 * Alur utama:
 *   1. Pilih periode (bulan + tahun)
 *   2a. Download template Excel
 *   2b. Upload Excel → SheetJS parse → validasi backend → preview → simpan
 *   2c. Grid interaktif → update sel individual
 *   3. Upload ulang → preview diff → konfirmasi → simpan versi baru
 */

import { apiFetch, esc, fmtTanggal, toast, openModal, closeModal, injectModalStyles } from './_utils.js';
import * as XLSX from 'https://cdn.sheetjs.com/xlsx-0.20.0/package/xlsx.mjs';

// ── State ─────────────────────────────────────────────────────────────────────
let state = {
    bulan:           new Date().getMonth() + 1,
    tahun:           new Date().getFullYear(),
    selectedPlanId:  null,
    validatedData:   null,   // hasil uploadExcel() — jadwal yang sudah tervalidasi
    diffData:        null,   // hasil previewDiff()
    planningLamaId:  null,   // id planning yang akan di-replace
    shiftList:       [],     // dari backend
    karyawanList:    [],     // untuk grid
    gridPlanningId:  null,   // planning yang sedang diedit di grid
    gridData:        {},     // { "id_karyawan|tgl": { id_shift, is_hari_libur } }
    currentPage:     1,
};

// ── Nama bulan ────────────────────────────────────────────────────────────────
const NAMA_BULAN = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const KODE_SHIFT = {'P':'Pagi','S':'Siang','M':'Malam','N':'Normal'};

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    // injectStyles();
    buildUI();
    bindEvents();
    loadShiftList();
    loadPlanning();
});

// ════════════════════════════════════════════════════════════════════════════
//  BUILD UI
// ════════════════════════════════════════════════════════════════════════════

function buildUI() {
    const content = document.querySelector('.dashboard-wrap');
    if (!content) return;

    content.innerHTML = `
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Planning Kerja</h1>
                <p class="page-subtitle">Input dan kelola jadwal kerja bulanan karyawan outsource (F08–F09)</p>
            </div>
        </div>

        <!-- PANEL KONTROL PERIODE -->
        <div class="planning-period-bar">
            <div class="period-bar-left">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                </svg>
                <span class="period-bar-label">Periode Aktif:</span>
                <select id="sel-bulan" class="period-select">
                    ${NAMA_BULAN.slice(1).map((b,i) => `<option value="${i+1}" ${i+1===state.bulan?'selected':''}>${b}</option>`).join('')}
                </select>
                <select id="sel-tahun" class="period-select">
                    ${[0,1,2].map(n => { const t = new Date().getFullYear()-n; return `<option value="${t}" ${t===state.tahun?'selected':''}>${t}</option>`; }).join('')}
                </select>
            </div>
            <div class="period-bar-actions">
                <button id="btn-download-template" class="btn-period btn-period--outline">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Template
                </button>
                <label class="btn-period btn-period--primary" for="input-upload-excel">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload Excel
                    <input type="file" id="input-upload-excel" accept=".xlsx,.xls" style="display:none;">
                </label>
            </div>
        </div>

        <!-- LAYOUT DUA KOLOM: DAFTAR PLANNING + DETAIL / GRID -->
        <div class="planning-layout">

            <!-- Kolom kiri: daftar planning -->
            <div class="planning-sidebar">
                <div class="dash-panel">
                    <div class="dash-panel-header">
                        <div>
                            <h2 class="dash-panel-title">Riwayat Planning</h2>
                            <p class="dash-panel-subtitle" id="planning-list-subtitle">Semua versi</p>
                        </div>
                        <span class="dash-panel-tag">F09</span>
                    </div>
                    <div class="dash-panel-body" style="padding:12px;">
                        <div id="planning-list-container" style="display:flex;flex-direction:column;gap:6px;">
                            ${skeletonPlanningList()}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom kanan: detail / grid -->
            <div class="planning-main">
                <div class="dash-panel" id="detail-panel">
                    <div class="dash-panel-header" id="detail-header">
                        <div>
                            <h2 class="dash-panel-title">Pilih planning untuk melihat detail</h2>
                            <p class="dash-panel-subtitle">atau upload Excel untuk membuat planning baru</p>
                        </div>
                    </div>
                    <div class="dash-panel-body" id="detail-body">
                        <div id="grid-placeholder" style="text-align:center;padding:60px 20px;color:#94a3b8;">
                            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:0.3;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m0 10a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 7a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m0 10V7m0 10a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2"/>
                            </svg>
                            <p style="font-size:14px;font-weight:500;color:#64748b;margin:0 0 8px;">Grid jadwal akan tampil di sini</p>
                            <p style="font-size:12px;color:#94a3b8;margin:0;">Klik planning di sebelah kiri, atau upload Excel untuk mulai</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL: Preview Upload Excel -->
        <div id="modal-preview-excel" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:720px;">
                <div class="modal-header">
                    <h3 class="modal-title" id="modal-preview-title">Preview Data Excel</h3>
                    <button data-close-modal="modal-preview-excel" class="modal-close">×</button>
                </div>
                <div class="modal-body" id="modal-preview-body">
                    <!-- Diisi JS -->
                </div>
            </div>
        </div>

        <!-- MODAL: Preview Diff Upload Ulang -->
        <div id="modal-diff" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:760px;">
                <div class="modal-header">
                    <h3 class="modal-title">Preview Perubahan — Upload Ulang</h3>
                    <button data-close-modal="modal-diff" class="modal-close">×</button>
                </div>
                <div class="modal-body" id="modal-diff-body">
                    <!-- Diisi JS -->
                </div>
            </div>
        </div>
    `;

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ════════════════════════════════════════════════════════════════════════════
//  LOAD DATA
// ════════════════════════════════════════════════════════════════════════════

async function loadShiftList() {
    try {
        const res  = await apiFetch('/api/super-admin/shift?status=aktif');
        const json = await res.json();
        if (json.status) {
            state.shiftList = Array.isArray(json.data) ? json.data : (json.data?.data ?? []);
        }
    } catch { /* silent */ }
}

async function loadPlanning() {
    const container = document.getElementById('planning-list-container');
    if (!container) return;

    try {
        const params = new URLSearchParams({ bulan: state.bulan, tahun: state.tahun });
        const res  = await apiFetch(`/api/admin/planning?${params}`);
        const json = await res.json();

        const rows = json.data?.data ?? json.data ?? [];

        if (!rows.length) {
            container.innerHTML = `
                <div style="text-align:center;padding:32px 16px;color:#94a3b8;font-size:13px;">
                    Belum ada planning untuk ${NAMA_BULAN[state.bulan]} ${state.tahun}.<br><br>
                    <button class="btn-period btn-period--primary" style="font-size:12px;" onclick="document.getElementById('input-upload-excel').click()">
                        + Upload Excel Sekarang
                    </button>
                </div>`;
            return;
        }

        container.innerHTML = rows.map(p => renderPlanningItem(p)).join('');

        // Auto-select pertama
        if (!state.selectedPlanId) {
            selectPlanning(rows[0].id_planning);
        }

    } catch (err) {
        console.error(err);
        container.innerHTML = `<div style="color:#ef4444;font-size:13px;padding:16px;">Gagal memuat data.</div>`;
    }
}

function renderPlanningItem(p) {
    const isSelected = state.selectedPlanId === p.id_planning;
    const statusCls  = { aktif: 'planning-badge--aktif', draft: 'planning-badge--draft', diperbarui: 'planning-badge--arsip' }[p.status] ?? 'planning-badge--arsip';
    const statusLabel= { aktif: 'Aktif', draft: 'Draft', diperbarui: 'Diarsipkan' }[p.status] ?? p.status;
    const iconBg     = p.status === 'aktif' ? '#f0faf0' : '#f8fafc';
    const iconColor  = p.status === 'aktif' ? '#1a6e1a' : '#94a3b8';

    return `
    <div class="planning-list-item ${isSelected ? 'planning-list-item--active' : ''}"
         data-id="${p.id_planning}" onclick="window._selectPlanning(${p.id_planning})">
        <div class="planning-list-icon" style="background:${iconBg};color:${iconColor};">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
        </div>
        <div class="planning-list-info">
            <span class="planning-list-label">${esc(p.periode_label)}</span>
            <span class="planning-list-meta">v${p.versi} · ${p.jumlah_jadwal ?? '?'} jadwal</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
            <span class="planning-badge ${statusCls}">${statusLabel}</span>
            ${p.status === 'aktif' ? `
            <button class="btn-upload-ulang-item" data-id="${p.id_planning}" data-label="${esc(p.periode_label)}"
                title="Upload ulang jadwal"
                onclick="event.stopPropagation(); window._triggerUploadUlang(${p.id_planning}, '${esc(p.periode_label)}')">
                ↑
            </button>` : ''}
        </div>
    </div>`;
}

// ════════════════════════════════════════════════════════════════════════════
//  SELECT PLANNING → LOAD GRID
// ════════════════════════════════════════════════════════════════════════════

async function selectPlanning(id) {
    state.selectedPlanId = id;
    state.gridPlanningId = id;

    // Re-render list dengan item aktif
    document.querySelectorAll('.planning-list-item').forEach(el => {
        el.classList.toggle('planning-list-item--active', parseInt(el.dataset.id) === id);
    });

    const detailBody   = document.getElementById('detail-body');
    const detailHeader = document.getElementById('detail-header');
    if (!detailBody) return;

    detailBody.innerHTML = `<div style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">Memuat grid jadwal...</div>`;

    try {
        const res  = await apiFetch(`/api/admin/planning/${id}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        const planning = json.data;

        // Update header
        detailHeader.innerHTML = `
            <div>
                <h2 class="dash-panel-title">${esc(planning.periode_label)}</h2>
                <p class="dash-panel-subtitle">
                    Versi ${planning.versi} · ${planning.jadwal?.length ?? 0} jadwal terkonfigurasi
                    ${planning.status === 'aktif' ? '<span style="color:#16a34a;font-size:11px;margin-left:6px;">● Aktif</span>' : '<span style="color:#94a3b8;font-size:11px;margin-left:6px;">Diarsipkan</span>'}
                </p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                ${planning.status === 'aktif' ? `
                <button class="btn-period btn-period--outline" style="font-size:12px;" onclick="window._triggerUploadUlang(${planning.id_planning}, '${esc(planning.periode_label)}')">
                    ↑ Upload Ulang
                </button>` : ''}
                <span class="dash-panel-tag">F08</span>
            </div>
        `;

        // Build grid data lookup
        state.gridData = {};
        state.karyawanList = [];
        const karyawanMap = {};

        (planning.jadwal ?? []).forEach(j => {
            const key = `${j.karyawan?.id_karyawan}|${j.tanggal_kerja}`;
            state.gridData[key] = { id_shift: j.shift?.id_shift, is_hari_libur: j.is_hari_libur, nama_shift: j.shift?.nama_shift };
            if (j.karyawan && !karyawanMap[j.karyawan.id_karyawan]) {
                karyawanMap[j.karyawan.id_karyawan] = j.karyawan;
                state.karyawanList.push(j.karyawan);
            }
        });

        // Sort karyawan by nama
        state.karyawanList.sort((a,b) => a.nama_lengkap.localeCompare(b.nama_lengkap));

        renderGrid(planning);

    } catch (err) {
        console.error(err);
        toast('Gagal memuat detail planning.', 'error');
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  RENDER GRID INTERAKTIF
// ════════════════════════════════════════════════════════════════════════════

function renderGrid(planning) {
    const detailBody = document.getElementById('detail-body');
    if (!detailBody) return;

    const bulan    = planning.periode_bulan;
    const tahun    = planning.periode_tahun;
    const isAktif  = planning.status === 'aktif';
    const jumlahHari = new Date(tahun, bulan, 0).getDate();

    // Buat array tanggal
    const tanggalArr = [];
    for (let d = 1; d <= jumlahHari; d++) {
        const tgl  = new Date(tahun, bulan-1, d);
        const isWE = tgl.getDay() === 0 || tgl.getDay() === 6;
        const hariSingkat = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][tgl.getDay()];
        tanggalArr.push({ d, tgl: `${tahun}-${String(bulan).padStart(2,'0')}-${String(d).padStart(2,'0')}`, hariSingkat, isWE });
    }

    // Dropdown shift options
    const shiftOpts = state.shiftList.map(s =>
        `<option value="${s.id_shift}">${s.nama_shift.replace('Shift ','')}</option>`
    ).join('');

    detailBody.innerHTML = `
        <div class="grid-legend">
            ${state.shiftList.map(s => `
            <span class="legend-item">
                <span class="legend-dot legend-dot--${getShiftClass(s.nama_shift)}"></span>
                ${s.nama_shift.replace('Shift ','')}
            </span>`).join('')}
            <span class="legend-item"><span class="legend-dot legend-dot--libur"></span>Libur</span>
            ${isAktif ? '<span class="legend-info">💡 Klik sel untuk ubah shift</span>' : '<span class="legend-info" style="color:#f59e0b;">📌 Planning ini sudah diarsipkan — tidak dapat diedit</span>'}
        </div>

        <div class="grid-scroll-wrapper">
            <table class="jadwal-grid" id="jadwal-grid">
                <thead>
                    <tr>
                        <th class="grid-th-karyawan">Karyawan</th>
                        ${tanggalArr.map(t => `
                        <th class="grid-th-tgl ${t.isWE ? 'grid-th-tgl--we' : ''}" title="${t.tgl}">
                            <span class="grid-tgl-num">${t.d}</span>
                            <span class="grid-tgl-hari">${t.hariSingkat}</span>
                        </th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${state.karyawanList.map(k => `
                    <tr>
                        <td class="grid-td-karyawan">
                            <span class="grid-karyawan-inisial">${esc(k.nama_lengkap.charAt(0).toUpperCase())}</span>
                            <div>
                                <span class="grid-karyawan-nama">${esc(k.nama_lengkap)}</span>
                                <span class="grid-karyawan-meta">${esc(k.nomor_karyawan ?? '')}</span>
                            </div>
                        </td>
                        ${tanggalArr.map(t => {
                            const key   = `${k.id_karyawan}|${t.tgl}`;
                            const jdwl  = state.gridData[key];
                            const cls   = jdwl ? (jdwl.is_hari_libur ? 'grid-cell--libur' : `grid-cell--${getShiftClass(jdwl.nama_shift)}`) : (t.isWE ? 'grid-cell--we' : 'grid-cell--kosong');
                            const label = jdwl ? (jdwl.is_hari_libur ? '—' : getKodeShift(jdwl.nama_shift)) : '';
                            return `<td class="grid-cell ${cls} ${isAktif ? 'grid-cell--editable' : ''}"
                                data-karyawan="${k.id_karyawan}"
                                data-tgl="${t.tgl}"
                                data-nama-karyawan="${esc(k.nama_lengkap)}"
                                title="${k.nama_lengkap} — ${t.tgl}${jdwl && !jdwl.is_hari_libur ? ' — ' + (jdwl.nama_shift ?? '') : ''}">
                                ${label}
                            </td>`;
                        }).join('')}
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>

        <!-- Dropdown popup untuk edit sel -->
        <div id="cell-editor" class="cell-editor" style="display:none;">
            <div class="cell-editor-header" id="cell-editor-info">Ahmad — 1 Mar 2026</div>
            <div class="cell-editor-options">
                ${state.shiftList.map(s => `
                <button class="cell-editor-btn" data-shift-id="${s.id_shift}" data-shift-nama="${esc(s.nama_shift)}">
                    <span class="legend-dot legend-dot--${getShiftClass(s.nama_shift)}"></span>
                    ${s.nama_shift.replace('Shift ','')}
                </button>`).join('')}
                <button class="cell-editor-btn cell-editor-btn--libur" data-libur="1">
                    <span class="legend-dot legend-dot--libur"></span>
                    Hari Libur
                </button>
            </div>
        </div>
    `;

    if (isAktif) {
        bindGridEvents();
    }
}

// ── Cell editor ───────────────────────────────────────────────────────────────
let activeCellEl = null;

function bindGridEvents() {
    const grid   = document.getElementById('jadwal-grid');
    const editor = document.getElementById('cell-editor');
    if (!grid || !editor) return;

    grid.addEventListener('click', (e) => {
        const cell = e.target.closest('.grid-cell--editable');
        if (!cell) return;

        // Posisikan editor di dekat sel
        const rect = cell.getBoundingClientRect();
        const scrollTop = window.scrollY;
        editor.style.top  = (rect.bottom + scrollTop + 4) + 'px';
        editor.style.left = Math.min(rect.left, window.innerWidth - 200) + 'px';
        editor.style.display = 'block';

        document.getElementById('cell-editor-info').textContent =
            `${cell.dataset.namaKaryawan} — ${fmtTanggal(cell.dataset.tgl)}`;

        activeCellEl = cell;

        // Hightlight sel aktif
        document.querySelectorAll('.grid-cell--editing').forEach(c => c.classList.remove('grid-cell--editing'));
        cell.classList.add('grid-cell--editing');
    });

    // Tombol di editor
    editor.addEventListener('click', async (e) => {
        const btn = e.target.closest('.cell-editor-btn');
        if (!btn || !activeCellEl) return;

        const idKaryawan = parseInt(activeCellEl.dataset.karyawan);
        const tgl        = activeCellEl.dataset.tgl;
        const isLibur    = btn.dataset.libur === '1';
        const idShift    = isLibur ? null : parseInt(btn.dataset.shiftId);

        editor.style.display = 'none';
        activeCellEl.classList.remove('grid-cell--editing');

        await updateSel(idKaryawan, tgl, idShift, isLibur, activeCellEl, btn.dataset.shiftNama ?? '');
        activeCellEl = null;
    });

    // Tutup editor klik luar
    document.addEventListener('click', (e) => {
        if (!editor.contains(e.target) && !e.target.closest('.grid-cell--editable')) {
            editor.style.display = 'none';
            document.querySelectorAll('.grid-cell--editing').forEach(c => c.classList.remove('grid-cell--editing'));
        }
    });
}

async function updateSel(idKaryawan, tgl, idShift, isLibur, cellEl, namaSHift) {
    cellEl.classList.add('grid-cell--loading');
    cellEl.textContent = '⟳';

    try {
        const res  = await apiFetch(`/api/admin/planning/${state.gridPlanningId}/update-jadwal`, {
            method: 'PUT',
            body: JSON.stringify({ id_karyawan: idKaryawan, tanggal_kerja: tgl, id_shift: idShift, is_hari_libur: isLibur }),
        });
        const json = await res.json();

        if (json.status) {
            // Update state
            const key = `${idKaryawan}|${tgl}`;
            state.gridData[key] = { id_shift: idShift, is_hari_libur: isLibur, nama_shift: namaSHift };

            // Update cell UI
            cellEl.classList.remove('grid-cell--loading', 'grid-cell--pagi', 'grid-cell--siang', 'grid-cell--malam', 'grid-cell--normal', 'grid-cell--libur', 'grid-cell--we', 'grid-cell--kosong');
            if (isLibur) {
                cellEl.classList.add('grid-cell--libur');
                cellEl.textContent = '—';
            } else {
                cellEl.classList.add(`grid-cell--${getShiftClass(namaSHift)}`);
                cellEl.textContent = getKodeShift(namaSHift);
            }

            toast(json.message, 'success');
        } else {
            toast(json.message, 'error');
            // Restore visual
            cellEl.classList.remove('grid-cell--loading');
            const key = `${idKaryawan}|${tgl}`;
            const jdwl = state.gridData[key];
            if (jdwl) {
                cellEl.textContent = jdwl.is_hari_libur ? '—' : getKodeShift(jdwl.nama_shift);
            } else {
                cellEl.textContent = '';
            }
        }
    } catch {
        toast('Gagal menyimpan perubahan.', 'error');
        cellEl.classList.remove('grid-cell--loading');
        cellEl.textContent = '';
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  DOWNLOAD TEMPLATE
// ════════════════════════════════════════════════════════════════════════════

async function downloadTemplate() {
    const btn = document.getElementById('btn-download-template');
    if (btn) { btn.disabled = true; btn.textContent = 'Membuat template...'; }

    try {
        const params = new URLSearchParams({ bulan: state.bulan, tahun: state.tahun });
        const res = await apiFetch(`/api/admin/planning/download-template?${params}`);

        if (!res.ok) {
            const json = await res.json().catch(() => ({}));
            toast(json.message ?? 'Gagal download template.', 'error');
            return;
        }

        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `Template_Planning_${NAMA_BULAN[state.bulan]}_${state.tahun}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        toast(`Template ${NAMA_BULAN[state.bulan]} ${state.tahun} berhasil didownload.`, 'success');
    } catch (err) {
        console.error(err);
        toast('Gagal download template.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> Download Template`; }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  UPLOAD EXCEL — parse SheetJS + kirim ke backend untuk validasi
// ════════════════════════════════════════════════════════════════════════════

async function handleFileUpload(file, isUploadUlang = false) {
    if (!file) return;
    if (!file.name.match(/\.(xlsx|xls)$/i)) {
        toast('File harus berformat .xlsx atau .xls', 'error');
        return;
    }

    toast('Membaca file Excel...', 'info', 2000);

    try {
        // ── Parse dengan SheetJS ──────────────────────────────────────────────
        const buffer = await file.arrayBuffer();
        const wb     = XLSX.read(buffer, { type: 'array' });

        // Cari sheet planning (sheet pertama atau sheet dengan nama "Planning_...")
        const sheetName = wb.SheetNames.find(n => n.startsWith('Planning_')) ?? wb.SheetNames[0];
        const ws        = wb.Sheets[sheetName];

        if (!ws) {
            toast('Sheet planning tidak ditemukan di file Excel ini.', 'error');
            return;
        }

        // Konversi ke array 2D
        const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

        // Cari baris header (baris yang mengandung angka tanggal mulai dari kolom E)
        // Baris 2 (index 1) adalah header tanggal
        const headerRow = raw[1] ?? [];

        // Ekstrak mapping kolom: indeks kolom → tanggal (Y-m-d)
        const colTanggal = {}; // colIdx → 'YYYY-MM-DD'
        for (let c = 4; c < headerRow.length; c++) {
            const val = headerRow[c];
            if (val === '' || val === undefined) continue;
            const num = parseInt(String(val).split('\n')[0]);
            if (!isNaN(num) && num >= 1 && num <= 31) {
                const tglPad = String(num).padStart(2, '0');
                const bulanPad = String(state.bulan).padStart(2, '0');
                colTanggal[c] = `${state.tahun}-${bulanPad}-${tglPad}`;
            }
        }

        if (Object.keys(colTanggal).length === 0) {
            toast('Tidak ditemukan kolom tanggal di file ini. Pastikan menggunakan template yang didownload dari sistem.', 'error');
            return;
        }

        // Parse data karyawan (mulai baris 3, index 2)
        const rows = [];
        for (let r = 2; r < raw.length; r++) {
            const row = raw[r];
            if (!row || !row[1]) continue; // kolom B = ID karyawan

            const idKaryawan = parseInt(row[1]);
            if (isNaN(idKaryawan)) continue;

            const jadwal = {};
            for (const [colIdx, tglStr] of Object.entries(colTanggal)) {
                const val = String(row[colIdx] ?? '').trim();
                if (val) jadwal[tglStr] = val;
            }

            rows.push({ id_karyawan: idKaryawan, jadwal });
        }

        if (!rows.length) {
            toast('Tidak ada data karyawan yang dapat dibaca dari file ini.', 'error');
            return;
        }

        toast(`Membaca ${rows.length} karyawan... Memvalidasi ke server...`, 'info', 3000);

        // ── Kirim ke backend untuk validasi ───────────────────────────────────
        const res  = await apiFetch('/api/admin/planning/upload-excel', {
            method: 'POST',
            body: JSON.stringify({
                periode_bulan: state.bulan,
                periode_tahun: state.tahun,
                sheet_name:    sheetName,
                rows,
            }),
        });
        const json = await res.json();

        // Simpan data tervalidasi
        state.validatedData = json.data?.valid ?? [];

        // ── Tampilkan modal preview ────────────────────────────────────────────
        if (isUploadUlang && json.data?.planning_existing) {
            showDiffPreview(json);
        } else {
            showExcelPreview(json, isUploadUlang);
        }

    } catch (err) {
        console.error(err);
        toast('Gagal membaca file Excel. Pastikan file tidak rusak.', 'error');
    }
}

// ── Modal preview setelah upload Excel ───────────────────────────────────────
function showExcelPreview(json, isUploadUlang) {
    const { valid, errors, warnings, ringkasan, planning_existing } = json.data;
    const hasError = errors.length > 0;

    const body = document.getElementById('modal-preview-body');
    document.getElementById('modal-preview-title').textContent =
        isUploadUlang ? 'Preview Upload Ulang' : 'Preview Data Excel';

    body.innerHTML = `
        <!-- Ringkasan -->
        <div class="preview-summary">
            <div class="preview-stat preview-stat--green">
                <span class="preview-stat-num">${ringkasan.total_karyawan}</span>
                <span class="preview-stat-label">Karyawan</span>
            </div>
            <div class="preview-stat preview-stat--blue">
                <span class="preview-stat-num">${ringkasan.total_jadwal}</span>
                <span class="preview-stat-label">Jadwal</span>
            </div>
            <div class="preview-stat preview-stat--gray">
                <span class="preview-stat-num">${ringkasan.total_libur}</span>
                <span class="preview-stat-label">Hari Libur</span>
            </div>
            ${ringkasan.total_error > 0 ? `
            <div class="preview-stat preview-stat--red">
                <span class="preview-stat-num">${ringkasan.total_error}</span>
                <span class="preview-stat-label">Error</span>
            </div>` : `
            <div class="preview-stat preview-stat--success">
                <span class="preview-stat-num">✓</span>
                <span class="preview-stat-label">Siap Simpan</span>
            </div>`}
        </div>

        ${warnings.length > 0 ? `
        <div class="preview-alert preview-alert--warning">
            ${warnings.map(w => `<p style="margin:0;font-size:13px;">⚠ ${esc(w.pesan)}</p>`).join('')}
        </div>` : ''}

        ${hasError ? `
        <div class="preview-errors">
            <div class="preview-errors-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                ${errors.length} error perlu diperbaiki di file Excel
            </div>
            <div class="preview-errors-list">
                ${errors.slice(0, 20).map(e => `
                <div class="preview-error-item">
                    <span class="preview-error-baris">Baris ${e.baris}</span>
                    <span class="preview-error-msg">${esc(e.pesan)}</span>
                    ${e.tanggal ? `<span class="preview-error-tgl">${e.tanggal}</span>` : ''}
                </div>`).join('')}
                ${errors.length > 20 ? `<div style="text-align:center;color:#94a3b8;font-size:12px;padding:8px;">... dan ${errors.length-20} error lainnya</div>` : ''}
            </div>
            <p style="font-size:13px;color:#64748b;margin:12px 0 0;">Perbaiki error di atas di file Excel, lalu upload ulang.</p>
        </div>` : `
        <div class="preview-alert preview-alert--success">
            Tidak ada error ditemukan. Data siap untuk disimpan.
        </div>

        ${planning_existing ? `
        <div class="preview-alert preview-alert--warning">
            ⚠ Planning aktif untuk ${NAMA_BULAN[state.bulan]} ${state.tahun} (v${planning_existing.versi}, ${planning_existing.jumlah_jadwal} jadwal) sudah ada.
            Dengan melanjutkan, planning lama akan diarsipkan dan versi baru akan dibuat.
        </div>` : ''}
        `}

        <!-- Preview tabel sampel (10 jadwal pertama) -->
        ${!hasError && valid.length > 0 ? `
        <div style="margin-top:16px;">
            <p style="font-size:12px;color:#94a3b8;margin:0 0 8px;">Pratinjau (${Math.min(valid.length,10)} dari ${valid.length} jadwal):</p>
            <div style="overflow-x:auto;">
                <table class="data-table" style="font-size:12px;">
                    <thead><tr><th>Karyawan</th><th>Tanggal</th><th>Shift</th></tr></thead>
                    <tbody>
                        ${valid.slice(0,10).map(v => `
                        <tr>
                            <td>${esc(v.nama_karyawan)}</td>
                            <td>${fmtTanggal(v.tanggal_kerja)}</td>
                            <td><span class="badge badge--${v.kode_shift === 'P' ? 'success' : v.kode_shift === 'S' ? 'info' : v.kode_shift === 'M' ? 'warning' : 'neutral'}">${esc(v.kode_shift)}</span></td>
                        </tr>`).join('')}
                        ${valid.length > 10 ? `<tr><td colspan="3" style="text-align:center;color:#94a3b8;">... dan ${valid.length-10} jadwal lainnya</td></tr>` : ''}
                    </tbody>
                </table>
            </div>
        </div>` : ''}

        <!-- Footer aksi -->
        <div class="modal-footer">
            <button class="btn-cancel" data-close-modal="modal-preview-excel">Tutup</button>
            ${!hasError ? `
            <button id="btn-konfirmasi-simpan" class="btn-primary-sm" onclick="window._konfirmasiSimpan(${planning_existing?.id_planning ?? 'null'})">
                ${planning_existing ? '⟲ Gantikan Planning Lama' : '✓ Simpan Planning'}
            </button>` : ''}
        </div>
    `;

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    openModal('modal-preview-excel');
}

// ── Konfirmasi simpan ─────────────────────────────────────────────────────────
async function konfirmasiSimpan(idPlanningLama) {
    const btn = document.getElementById('btn-konfirmasi-simpan');
    if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }

    try {
        if (idPlanningLama) {
            // Upload ulang: langsung simpan tanpa diff (sudah di-inform di preview)
            const res  = await apiFetch(`/api/admin/planning/${idPlanningLama}/upload-ulang`, {
                method: 'POST',
                body: JSON.stringify({ jadwal: state.validatedData }),
            });
            const json = await res.json();
            if (json.status) {
                toast(json.message, 'success');
                closeModal('modal-preview-excel');
                state.selectedPlanId = null;
                loadPlanning();
            } else {
                toast(json.message, 'error');
            }
        } else {
            // Planning baru
            const res  = await apiFetch('/api/admin/planning', {
                method: 'POST',
                body: JSON.stringify({
                    periode_bulan: state.bulan,
                    periode_tahun: state.tahun,
                    jadwal: state.validatedData,
                }),
            });
            const json = await res.json();
            if (json.status) {
                toast(json.message, 'success');
                closeModal('modal-preview-excel');
                state.selectedPlanId = null;
                loadPlanning();
            } else {
                toast(json.message, 'error');
            }
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Simpan'; }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  UPLOAD ULANG — trigger + diff preview
// ════════════════════════════════════════════════════════════════════════════

function triggerUploadUlang(idPlanning, periodeLabel) {
    state.planningLamaId = idPlanning;

    // Buat input file sementara khusus upload ulang
    const input = document.createElement('input');
    input.type  = 'file';
    input.accept = '.xlsx,.xls';
    input.onchange = (e) => {
        if (e.target.files[0]) handleFileUploadUlang(e.target.files[0], idPlanning);
    };
    input.click();
}

async function handleFileUploadUlang(file, idPlanningLama) {
    // Parse file dulu sama seperti upload biasa
    try {
        const buffer = await file.arrayBuffer();
        const wb     = XLSX.read(buffer, { type: 'array' });
        const sheetName = wb.SheetNames.find(n => n.startsWith('Planning_')) ?? wb.SheetNames[0];
        const ws        = wb.Sheets[sheetName];
        const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
        const headerRow = raw[1] ?? [];

        const colTanggal = {};
        for (let c = 4; c < headerRow.length; c++) {
            const num = parseInt(String(headerRow[c]).split('\n')[0]);
            if (!isNaN(num) && num >= 1 && num <= 31) {
                colTanggal[c] = `${state.tahun}-${String(state.bulan).padStart(2,'0')}-${String(num).padStart(2,'0')}`;
            }
        }

        const rows = [];
        for (let r = 2; r < raw.length; r++) {
            const row = raw[r];
            if (!row || !row[1]) continue;
            const idKaryawan = parseInt(row[1]);
            if (isNaN(idKaryawan)) continue;
            const jadwal = {};
            for (const [colIdx, tglStr] of Object.entries(colTanggal)) {
                const val = String(row[colIdx] ?? '').trim();
                if (val) jadwal[tglStr] = val;
            }
            rows.push({ id_karyawan: idKaryawan, jadwal });
        }

        // Validasi backend
        toast('Memvalidasi data...', 'info', 2000);
        const resVal = await apiFetch('/api/admin/planning/upload-excel', {
            method: 'POST',
            body: JSON.stringify({ periode_bulan: state.bulan, periode_tahun: state.tahun, sheet_name: sheetName, rows }),
        });
        const jsonVal = await resVal.json();
        if (jsonVal.data?.errors?.length > 0) {
            state.validatedData = jsonVal.data?.valid ?? [];
            showExcelPreview(jsonVal, true);
            return;
        }

        state.validatedData = jsonVal.data?.valid ?? [];

        // Minta diff dari backend
        toast('Membandingkan dengan planning lama...', 'info', 2000);
        const resDiff = await apiFetch('/api/admin/planning/preview-diff', {
            method: 'POST',
            body: JSON.stringify({
                id_planning_lama: idPlanningLama,
                jadwal_baru: state.validatedData.map(j => ({
                    id_karyawan: j.id_karyawan,
                    tanggal_kerja: j.tanggal_kerja,
                    id_shift: j.id_shift,
                })),
            }),
        });
        const jsonDiff = await resDiff.json();
        state.diffData = jsonDiff.data;
        showDiffModal(jsonDiff.data, idPlanningLama, jsonVal.data.ringkasan);

    } catch (err) {
        console.error(err);
        toast('Gagal memproses file.', 'error');
    }
}

function showDiffModal(diffData, idPlanningLama, ringkasan) {
    const body = document.getElementById('modal-diff-body');

    const { diff, total_perubahan, total_diubah, total_ditambah, total_dihapus, total_tidak_berubah } = diffData;

    body.innerHTML = `
        <!-- Ringkasan diff -->
        <div class="preview-summary">
            <div class="preview-stat preview-stat--gray">
                <span class="preview-stat-num">${total_tidak_berubah}</span>
                <span class="preview-stat-label">Tidak Berubah</span>
            </div>
            <div class="preview-stat ${total_diubah > 0 ? 'preview-stat--blue' : 'preview-stat--gray'}">
                <span class="preview-stat-num">${total_diubah}</span>
                <span class="preview-stat-label">Diubah</span>
            </div>
            <div class="preview-stat ${total_ditambah > 0 ? 'preview-stat--green' : 'preview-stat--gray'}">
                <span class="preview-stat-num">${total_ditambah}</span>
                <span class="preview-stat-label">Ditambah</span>
            </div>
            <div class="preview-stat ${total_dihapus > 0 ? 'preview-stat--red' : 'preview-stat--gray'}">
                <span class="preview-stat-num">${total_dihapus}</span>
                <span class="preview-stat-label">Dihapus</span>
            </div>
        </div>

        ${total_perubahan === 0 ? `
        <div class="preview-alert preview-alert--success">
            ✓ Tidak ada perubahan dari planning sebelumnya. Data identik.
        </div>` : ''}

        <!-- Tabel diff -->
        ${renderDiffTable('✏️ Jadwal Diubah', diff.diubah, 'blue', ['Karyawan','Tanggal','Shift Lama','→','Shift Baru'],
            r => `<td>${esc(r.nama_karyawan)}</td><td>${fmtTanggal(r.tanggal)}</td><td><span style="color:#94a3b8;text-decoration:line-through;">${esc(r.shift_lama)}</span></td><td>→</td><td style="color:#1a6e1a;font-weight:600;">${esc(r.shift_baru)}</td>`
        )}
        ${renderDiffTable('➕ Jadwal Ditambah', diff.ditambah, 'green', ['Karyawan','Tanggal','Shift Baru'],
            r => `<td>${esc(r.nama_karyawan)}</td><td>${fmtTanggal(r.tanggal)}</td><td style="color:#1a6e1a;font-weight:600;">${esc(r.shift_baru)}</td>`
        )}
        ${renderDiffTable('➖ Jadwal Dihapus', diff.dihapus, 'red', ['Karyawan','Tanggal','Shift Lama'],
            r => `<td>${esc(r.nama_karyawan)}</td><td>${fmtTanggal(r.tanggal)}</td><td style="color:#ef4444;">${esc(r.shift_lama)}</td>`
        )}

        <div class="modal-footer">
            <button class="btn-cancel" data-close-modal="modal-diff">Batal</button>
            <button id="btn-konfirmasi-diff" class="btn-primary-sm" onclick="window._konfirmasiUploadUlang(${idPlanningLama})">
                ${total_perubahan === 0 ? '✓ Simpan (tidak ada perubahan)' : `✓ Konfirmasi ${total_perubahan} Perubahan`}
            </button>
        </div>
    `;

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    openModal('modal-diff');
}

function renderDiffTable(judul, rows, color, headers, rowFn) {
    if (!rows.length) return '';
    const colorMap = { blue: '#dbeafe', green: '#dcf5dc', red: '#fee2e2' };
    return `
    <div style="margin-bottom:16px;">
        <div style="background:${colorMap[color]};border-radius:8px 8px 0 0;padding:8px 14px;font-size:13px;font-weight:600;color:#0f172a;">
            ${judul} (${rows.length})
        </div>
        <div style="overflow-x:auto;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
            <table class="data-table" style="font-size:12px;">
                <thead><tr>${headers.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
                <tbody>
                    ${rows.slice(0,20).map(r => `<tr>${rowFn(r)}</tr>`).join('')}
                    ${rows.length > 20 ? `<tr><td colspan="${headers.length}" style="text-align:center;color:#94a3b8;">... dan ${rows.length-20} lainnya</td></tr>` : ''}
                </tbody>
            </table>
        </div>
    </div>`;
}

async function konfirmasiUploadUlang(idPlanningLama) {
    const btn = document.getElementById('btn-konfirmasi-diff');
    if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }

    try {
        const res  = await apiFetch(`/api/admin/planning/${idPlanningLama}/upload-ulang`, {
            method: 'POST',
            body: JSON.stringify({ jadwal: state.validatedData }),
        });
        const json = await res.json();
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-diff');
            state.selectedPlanId = null;
            loadPlanning();
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Konfirmasi'; }
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  BIND EVENTS
// ════════════════════════════════════════════════════════════════════════════

function bindEvents() {
    // Expose fungsi ke window untuk dipanggil dari inline HTML
    window._selectPlanning      = selectPlanning;
    window._triggerUploadUlang  = triggerUploadUlang;
    window._konfirmasiSimpan    = konfirmasiSimpan;
    window._konfirmasiUploadUlang = konfirmasiUploadUlang;

    document.addEventListener('change', (e) => {
        if (e.target.id === 'sel-bulan') {
            state.bulan = parseInt(e.target.value);
            state.selectedPlanId = null;
            loadPlanning();
        }
        if (e.target.id === 'sel-tahun') {
            state.tahun = parseInt(e.target.value);
            state.selectedPlanId = null;
            loadPlanning();
        }
        if (e.target.id === 'input-upload-excel') {
            const file = e.target.files[0];
            if (file) handleFileUpload(file, false);
            e.target.value = '';
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('#btn-download-template')) downloadTemplate();
    });
}

// ════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════════════════════

function getShiftClass(namaSHift) {
    if (!namaSHift) return 'kosong';
    const n = namaSHift.toLowerCase();
    if (n.includes('pagi'))   return 'pagi';
    if (n.includes('siang'))  return 'siang';
    if (n.includes('malam'))  return 'malam';
    if (n.includes('normal')) return 'normal';
    return 'kosong';
}

function getKodeShift(namaShift) {
    if (!namaShift) return '';
    const n = namaShift.toLowerCase();
    if (n.includes('pagi'))   return 'P';
    if (n.includes('siang'))  return 'S';
    if (n.includes('malam'))  return 'M';
    if (n.includes('normal')) return 'N';
    return '?';
}

function skeletonPlanningList() {
    return Array(4).fill(`
        <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;background:#f8fafc;">
            <div class="skel" style="width:34px;height:34px;border-radius:8px;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                <div class="skel" style="height:10px;width:100px;border-radius:4px;"></div>
                <div class="skel" style="height:8px;width:70px;border-radius:4px;"></div>
            </div>
            <div class="skel" style="width:55px;height:20px;border-radius:999px;"></div>
        </div>
    `).join('');
}