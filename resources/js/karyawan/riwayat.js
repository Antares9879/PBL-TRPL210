/**
 * resources/js/karyawan/riwayat.js
 *
 * Halaman Riwayat Absensi (F06).
 * Fitur:
 *   - Filter periode bulan + tahun
 *   - Fetch rekap absensi harian: GET /api/karyawan/riwayat?bulan=X&tahun=Y
 *   - Fetch ringkasan agregasi:    GET /api/karyawan/riwayat/ringkasan?bulan=X&tahun=Y
 *   - Render tabel detail + paginasi
 *   - Progress bar menit kerja normal
 *   - Summary grid: hari hadir, izin, alpa, menit
 */

'use strict';

import {
    apiFetch,
    toast,
    formatTime,
    formatDate,
    formatMinutes,
    getBulanNama,
    getBadgeHtml,
    renderPagination,
    renderSkeleton,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. STATE
// ══════════════════════════════════════════════════════════════════════════════

const state = {
    bulan:    new Date().getMonth() + 1,
    tahun:    new Date().getFullYear(),
    page:     1,
    isLoading: false,
};

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    syncFilterUI();
    bindEvents();
    loadData();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. SYNC FILTER UI
// ══════════════════════════════════════════════════════════════════════════════

function syncFilterUI() {
    const bulanEl = document.getElementById('filter-bulan');
    const tahunEl = document.getElementById('filter-tahun');

    if (bulanEl) bulanEl.value = String(state.bulan);
    if (tahunEl) tahunEl.value = String(state.tahun);
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. BIND EVENTS
// ══════════════════════════════════════════════════════════════════════════════

function bindEvents() {
    document.getElementById('btn-load-riwayat')?.addEventListener('click', () => {
        const bulanEl = document.getElementById('filter-bulan');
        const tahunEl = document.getElementById('filter-tahun');

        state.bulan = parseInt(bulanEl?.value ?? state.bulan, 10);
        state.tahun = parseInt(tahunEl?.value ?? state.tahun, 10);
        state.page  = 1;

        loadData();
    });

    // Enter key pada filter juga trigger load
    ['filter-bulan', 'filter-tahun'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            document.getElementById('btn-load-riwayat')?.click();
        });
    });
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. LOAD DATA (paralel: tabel + ringkasan)
// ══════════════════════════════════════════════════════════════════════════════

async function loadData(page = 1) {
    if (state.isLoading) return;
    state.isLoading = true;
    state.page      = page;

    showTableSkeleton();
    showRingkasanSkeleton();

    const [riwayatRes, ringkasanRes] = await Promise.allSettled([
        apiFetch(`/api/karyawan/riwayat?bulan=${state.bulan}&tahun=${state.tahun}&page=${page}`),
        apiFetch(`/api/karyawan/riwayat/ringkasan?bulan=${state.bulan}&tahun=${state.tahun}`),
    ]);

    renderRiwayat(riwayatRes);
    renderRingkasan(ringkasanRes);
    updatePeriodeLabel();

    state.isLoading = false;
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. RENDER TABEL RIWAYAT
// ══════════════════════════════════════════════════════════════════════════════

function renderRiwayat(result) {
    const tbody = document.getElementById('tbody-riwayat');
    const pagEl = document.getElementById('paginasi-riwayat');

    if (!tbody) return;

    if (result.status !== 'fulfilled' || !result.value?.status) {
        tbody.innerHTML = _emptyRow(9, 'Gagal memuat data absensi.');
        if (pagEl) pagEl.innerHTML = '';
        return;
    }

    const list = result.value.data?.data ?? [];
    const meta = result.value.data;

    if (list.length === 0) {
        tbody.innerHTML = _emptyRow(9, 'Belum ada data absensi untuk periode ini.');
        if (pagEl) pagEl.innerHTML = '';
        return;
    }

    tbody.innerHTML = list.map((row) => _renderAbsensiRow(row)).join('');

    if (pagEl) {
        renderPagination(pagEl, meta, (page) => loadData(page));
    }
}

function _renderAbsensiRow(row) {
    const menit_lembur = row.menit_lembur_resmi > 0
        ? `<span class="k-table-menit" style="color:var(--status-lembur);">
               ${formatMinutes(row.menit_lembur_resmi)}
           </span>`
        : '<span style="color:var(--text-muted);">—</span>';

    const menit_telat = (row.menit_telat ?? 0) > 0
        ? `<span class="k-table-menit" style="color:var(--status-telat);">
               ${formatMinutes(row.menit_telat)}
           </span>`
        : '<span style="color:var(--text-muted);">—</span>';

    return `
        <tr>
            <td>
                <div style="display:flex;flex-direction:column;">
                    <span style="font-size:12px;font-weight:500;color:var(--text-primary);">
                        ${_escapeHtml(row.hari ?? '—')}
                    </span>
                    <span style="font-size:11px;color:var(--text-muted);">
                        ${row.tanggal_absensi ?? '—'}
                    </span>
                </div>
            </td>
            <td>
                <span style="font-size:12px;color:var(--text-muted);">
                    ${_escapeHtml(row.shift?.nama_shift ?? '—')}
                </span>
            </td>
            <td>
                <span class="k-table-time">${formatTime(row.waktu_check_in)}</span>
            </td>
            <td>
                <span class="k-table-time">${formatTime(row.waktu_check_out)}</span>
            </td>
            <td>
                <span class="k-table-menit">
                    ${row.menit_kerja_normal > 0 ? formatMinutes(row.menit_kerja_normal) : '—'}
                </span>
            </td>
            <td>${menit_telat}</td>
            <td>${menit_lembur}</td>
            <td>${getBadgeHtml(row.status_kehadiran)}</td>
            <td>${getBadgeHtml(row.status_validasi, 'validasi')}</td>
        </tr>`;
}

function _emptyRow(cols, msg) {
    return `<tr>
                <td colspan="${cols}"
                    style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px;">
                    ${_escapeHtml(msg)}
                </td>
            </tr>`;
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. RENDER RINGKASAN
// ══════════════════════════════════════════════════════════════════════════════

function renderRingkasan(result) {
    const gridEl    = document.getElementById('ringkasan-grid');
    const menitEl   = document.getElementById('ringkasan-menit-normal');
    const progressEl= document.getElementById('ringkasan-progress');
    const lemburEl  = document.getElementById('ringkasan-lembur');
    const telatEl   = document.getElementById('ringkasan-telat');

    if (result.status !== 'fulfilled' || !result.value?.status) {
        if (gridEl) gridEl.innerHTML = _ringkasanEmpty();
        return;
    }

    const d = result.value.data;
    if (!d) return;

    // Summary grid
    if (gridEl) {
        gridEl.innerHTML = `
            <div class="k-summary-item">
                <p class="k-summary-val">${d.total_hari_hadir ?? 0}</p>
                <p class="k-summary-label">Hari Hadir</p>
            </div>
            <div class="k-summary-item">
                <p class="k-summary-val" style="color:var(--status-izin);">${d.total_hari_izin ?? 0}</p>
                <p class="k-summary-label">Hari Izin</p>
            </div>
            <div class="k-summary-item">
                <p class="k-summary-val" style="color:var(--status-alpa);">${d.total_hari_alpa ?? 0}</p>
                <p class="k-summary-label">Hari Alpa</p>
            </div>
            <div class="k-summary-item">
                <p class="k-summary-val">${d.total_pending_validasi ?? 0}</p>
                <p class="k-summary-label">Pending Validasi</p>
            </div>`;
    }

    // Menit normal
    const totalNormal = d.total_menit_kerja_normal ?? 0;
    if (menitEl) menitEl.textContent = formatMinutes(totalNormal);
    if (lemburEl) lemburEl.textContent = formatMinutes(d.total_menit_lembur_resmi ?? 0);
    if (telatEl)  telatEl.textContent  = formatMinutes(d.total_menit_telat ?? 0);

    // Progress bar: dibanding target maks hari hadir × 480
    const target = (d.total_hari_hadir ?? 0) * 480 || 1;
    const pct    = Math.min(100, Math.round((totalNormal / target) * 100));
    if (progressEl) {
        requestAnimationFrame(() => { progressEl.style.width = `${pct}%`; });
    }
}

function _ringkasanEmpty() {
    return Array.from({ length: 4 }).map(() => `
        <div class="k-summary-item">
            <p class="k-summary-val">—</p>
            <p class="k-summary-label">—</p>
        </div>`).join('');
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. HELPERS UI
// ══════════════════════════════════════════════════════════════════════════════

function updatePeriodeLabel() {
    const periodeLabel = document.getElementById('tabel-subtitle');
    const ringkasanLabel = document.getElementById('ringkasan-periode-label');

    const label = `${getBulanNama(state.bulan)} ${state.tahun}`;
    if (ringkasanLabel) ringkasanLabel.textContent = label;
    if (periodeLabel)   periodeLabel.textContent   = `Periode: ${label}`;
}

function showTableSkeleton() {
    const tbody = document.getElementById('tbody-riwayat');
    if (tbody) tbody.innerHTML = renderSkeleton(6, 9);
}

function showRingkasanSkeleton() {
    const grid = document.getElementById('ringkasan-grid');
    if (grid) {
        grid.innerHTML = Array.from({ length: 4 }).map(() => `
            <div class="k-summary-item">
                <div class="k-skel k-skel--text" style="width:36px;height:24px;margin:0 auto;border-radius:4px;"></div>
                <div class="k-skel k-skel--text" style="width:60px;height:8px;margin:6px auto 0;"></div>
            </div>`).join('');
    }

    const progressEl = document.getElementById('ringkasan-progress');
    if (progressEl) progressEl.style.width = '0%';
}