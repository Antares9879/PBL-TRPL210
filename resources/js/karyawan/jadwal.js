/**
 * resources/js/karyawan/jadwal.js
 *
 * Halaman Jadwal Kerja (F02).
 * Fitur:
 *   - Fetch jadwal dari GET /api/karyawan/jadwal?bulan=X&tahun=Y
 *   - Toggle view: Kalender / List
 *   - Navigasi bulan prev/next
 *   - Render kalender grid dengan dot indikator shift
 *   - Klik tanggal → tampilkan detail hari
 *   - Render list view dengan info shift dan status absensi
 *   - Ringkasan jumlah shift per bulan
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
    renderSkeleton,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. STATE
// ══════════════════════════════════════════════════════════════════════════════

const state = {
    bulan: new Date().getMonth() + 1,  // 1-12
    tahun: new Date().getFullYear(),
    view: 'calendar',                  // 'calendar' | 'list'
    jadwalData: [],                    // array jadwal dari API
    isLoading: false,
};

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    loadJadwal();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. BIND EVENTS
// ══════════════════════════════════════════════════════════════════════════════

function bindEvents() {
    // Navigasi bulan
    document.getElementById('btn-prev-month')?.addEventListener('click', () => {
        navigateMonth(-1);
    });
    document.getElementById('btn-next-month')?.addEventListener('click', () => {
        navigateMonth(1);
    });

    // Toggle view kalender / list
    document.getElementById('btn-view-cal')?.addEventListener('click', () => setView('calendar'));
    document.getElementById('btn-view-list')?.addEventListener('click', () => setView('list'));

    // Tutup detail card
    document.getElementById('btn-close-detail')?.addEventListener('click', () => {
        const card = document.getElementById('jadwal-detail-card');
        if (card) card.style.display = 'none';
    });
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. NAVIGASI BULAN
// ══════════════════════════════════════════════════════════════════════════════

function navigateMonth(delta) {
    state.bulan += delta;
    if (state.bulan > 12) { state.bulan = 1;  state.tahun++; }
    if (state.bulan < 1)  { state.bulan = 12; state.tahun--; }
    loadJadwal();
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. TOGGLE VIEW
// ══════════════════════════════════════════════════════════════════════════════

function setView(view) {
    state.view = view;

    const calView  = document.getElementById('view-calendar');
    const listView = document.getElementById('view-list');
    const btnCal   = document.getElementById('btn-view-cal');
    const btnList  = document.getElementById('btn-view-list');

    calView?.style.setProperty('display',  view === 'calendar' ? '' : 'none');
    listView?.style.setProperty('display', view === 'list' ? '' : 'none');

    btnCal?.classList.toggle('k-view-toggle-btn--active',  view === 'calendar');
    btnList?.classList.toggle('k-view-toggle-btn--active', view === 'list');

    btnCal?.setAttribute('aria-pressed',  String(view === 'calendar'));
    btnList?.setAttribute('aria-pressed', String(view === 'list'));

    // Render ulang view yang dipilih dengan data yang sudah ada
    if (state.jadwalData.length > 0) {
        if (view === 'calendar') renderCalendar(state.jadwalData);
        else renderList(state.jadwalData);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. LOAD JADWAL
// ══════════════════════════════════════════════════════════════════════════════

async function loadJadwal() {
    if (state.isLoading) return;
    state.isLoading = true;

    updatePeriodLabel();
    showLoadingState();

    try {
        const res = await apiFetch(
            `/api/karyawan/jadwal?bulan=${state.bulan}&tahun=${state.tahun}`
        );

        if (!res.status) {
            toast(res.message ?? 'Gagal memuat jadwal.', 'error');
            showEmptyState();
            return;
        }

        state.jadwalData = res.data?.jadwal ?? [];

        if (state.view === 'calendar') renderCalendar(state.jadwalData);
        else renderList(state.jadwalData);

        renderSummary(state.jadwalData);

    } catch (err) {
        toast(err.message, 'error');
        showEmptyState();
    } finally {
        state.isLoading = false;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. RENDER KALENDER GRID
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Build jadwal lookup map: tanggal → jadwal item
 * @param {Array} jadwalList
 * @returns {Map<string, object>}
 */
function buildJadwalMap(jadwalList) {
    const map = new Map();
    jadwalList.forEach((j) => { map.set(j.tanggal_kerja, j); });
    return map;
}

function renderCalendar(jadwalList) {
    const grid = document.getElementById('calendar-grid');
    if (!grid) return;

    const jadwalMap = buildJadwalMap(jadwalList);
    const today     = new Date().toISOString().split('T')[0];

    // Hitung hari pertama bulan dan total hari
    const firstDay = new Date(state.tahun, state.bulan - 1, 1).getDay(); // 0=Minggu
    const totalDays = new Date(state.tahun, state.bulan, 0).getDate();
    // Hari bulan sebelumnya (filler)
    const prevMonthDays = new Date(state.tahun, state.bulan - 1, 0).getDate();

    let html = '';

    // Filler hari sebelumnya
    for (let i = firstDay - 1; i >= 0; i--) {
        html += `<div class="k-cal-day k-cal-day--other-month">
                     <span class="k-cal-day-num">${prevMonthDays - i}</span>
                 </div>`;
    }

    // Hari bulan ini
    for (let d = 1; d <= totalDays; d++) {
        const dateStr = `${state.tahun}-${String(state.bulan).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const jadwal  = jadwalMap.get(dateStr);
        const isToday = dateStr === today;
        const dayOfWeek = new Date(dateStr).getDay(); // 0=Minggu
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

        let cls = 'k-cal-day';
        if (isToday)   cls += ' k-cal-day--today';
        if (isWeekend) cls += ' k-cal-day--weekend';

        let dot = '';
        if (jadwal) {
            if (jadwal.is_hari_libur) {
                cls += ' k-cal-day--libur';
                dot = `<span class="k-cal-shift-dot k-cal-shift-dot--libur" aria-hidden="true"></span>`;
            } else {
                const shiftKey = _getShiftKey(jadwal.shift?.nama_shift ?? '');
                dot = `<span class="k-cal-shift-dot k-cal-shift-dot--${shiftKey}" aria-hidden="true"></span>`;
            }
        }

        html += `
            <div class="${cls}"
                 role="button"
                 tabindex="0"
                 data-date="${dateStr}"
                 aria-label="Tanggal ${d}, ${jadwal ? (jadwal.is_hari_libur ? 'Libur' : jadwal.shift?.nama_shift ?? 'Jadwal') : 'Tidak ada jadwal'}"
                 onclick="window._showJadwalDetail('${dateStr}')">
                <span class="k-cal-day-num">${d}</span>
                ${dot}
            </div>`;
    }

    grid.innerHTML = html;

    // Expose global function untuk onclick inline
    window._showJadwalDetail = (dateStr) => showJadwalDetail(dateStr, jadwalMap);
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. RENDER LIST VIEW
// ══════════════════════════════════════════════════════════════════════════════

function renderList(jadwalList) {
    const container = document.getElementById('jadwal-list-container');
    if (!container) return;

    const today = new Date().toISOString().split('T')[0];

    if (jadwalList.length === 0) {
        container.innerHTML = `
            <div class="k-empty">
                <p class="k-empty-title">Jadwal belum tersedia</p>
                <p class="k-empty-desc">Admin Outsource belum membuat planning untuk bulan ini.</p>
            </div>`;
        return;
    }

    const HARI_SHORT = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

    container.innerHTML = jadwalList.map((j) => {
        const isToday  = j.tanggal_kerja === today;
        const isLibur  = j.is_hari_libur;
        const dayObj   = new Date(j.tanggal_kerja + 'T00:00:00');
        const hari     = HARI_SHORT[dayObj.getDay()];
        const tgl      = dayObj.getDate();
        const shiftKey = _getShiftKey(j.shift?.nama_shift ?? '');

        const absensiInfo = j.absensi ? `
            <div class="k-jadwal-absensi">
                ${j.absensi.waktu_check_in ? `
                    <span class="k-jadwal-dot k-jadwal-dot--checkin" aria-hidden="true"></span>
                    <span class="k-jadwal-absensi-time">${formatTime(j.absensi.waktu_check_in)}</span>
                ` : ''}
                ${j.absensi.waktu_check_out ? `
                    <span class="k-jadwal-dot k-jadwal-dot--checkout" aria-hidden="true"></span>
                    <span class="k-jadwal-absensi-time">${formatTime(j.absensi.waktu_check_out)}</span>
                ` : ''}
            </div>` : '';

        return `
            <div class="k-jadwal-item ${isToday ? 'k-jadwal-item--today' : ''} ${isLibur ? 'k-jadwal-item--libur' : ''}">
                <div class="k-jadwal-date">
                    <span class="k-jadwal-date-day">${hari}</span>
                    <span class="k-jadwal-date-num">${tgl}</span>
                </div>
                <div class="k-jadwal-divider" aria-hidden="true"></div>
                <div class="k-jadwal-info">
                    <p class="k-jadwal-shift-name">
                        ${isLibur ? 'Hari Libur' : _escapeHtml(j.shift?.nama_shift ?? 'Tidak ada shift')}
                    </p>
                    ${!isLibur && j.shift ? `
                    <div class="k-jadwal-shift-time">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        ${j.shift.jam_masuk} – ${j.shift.jam_pulang}
                    </div>` : ''}
                    ${absensiInfo}
                </div>
                <div class="k-shift-badge">
                    <span class="k-shift-pill k-shift-pill--${isLibur ? 'libur' : shiftKey}">
                        ${isLibur ? 'Libur' : _escapeHtml(j.shift?.nama_shift?.split(' ')[1] ?? j.shift?.nama_shift ?? '—')}
                    </span>
                    ${j.absensi ? getBadgeHtml(j.absensi.status_kehadiran) : ''}
                </div>
            </div>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════════════
// 9. DETAIL HARI TERPILIH (klik kalender)
// ══════════════════════════════════════════════════════════════════════════════

function showJadwalDetail(dateStr, jadwalMap) {
    const jadwal = jadwalMap.get(dateStr);
    const card   = document.getElementById('jadwal-detail-card');
    const title  = document.getElementById('detail-card-title');
    const date   = document.getElementById('detail-card-date');
    const body   = document.getElementById('jadwal-detail-body');

    if (!card || !body) return;

    const dateObj = new Date(dateStr + 'T00:00:00');
    const HARI    = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    if (title) title.textContent = `Detail — ${HARI[dateObj.getDay()]}`;
    if (date)  date.textContent  = formatDate(dateStr);

    if (!jadwal) {
        body.innerHTML = `<p class="k-jadwal-detail-empty">Tidak ada jadwal untuk tanggal ini.</p>`;
    } else if (jadwal.is_hari_libur) {
        body.innerHTML = `
            <div class="k-alert k-alert--info">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9.003 9.003 0 0 0 12 21a9.003 9.003 0 0 0 8.354-5.646z"/>
                </svg>
                Hari ini adalah hari libur.
            </div>`;
    } else {
        const absensi = jadwal.absensi;
        body.innerHTML = `
            <div style="display:flex;flex-direction:column;gap:var(--space-3);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Shift</span>
                    <strong style="font-size:14px;">${_escapeHtml(jadwal.shift?.nama_shift ?? '—')}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Jadwal Masuk</span>
                    <span class="k-table-time">${jadwal.shift?.jam_masuk ?? '—'}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Jadwal Pulang</span>
                    <span class="k-table-time">${jadwal.shift?.jam_pulang ?? '—'}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Durasi Normal</span>
                    <span>${formatMinutes(jadwal.shift?.durasi_normal_menit)}</span>
                </div>
                ${absensi ? `
                <div class="k-divider" aria-hidden="true"></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Check-In Aktual</span>
                    <span class="k-table-time">${formatTime(absensi.waktu_check_in)}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Check-Out Aktual</span>
                    <span class="k-table-time">${formatTime(absensi.waktu_check_out)}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Status Kehadiran</span>
                    ${getBadgeHtml(absensi.status_kehadiran)}
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Status Validasi</span>
                    ${getBadgeHtml(absensi.status_validasi, 'validasi')}
                </div>
                ${(absensi.menit_telat ?? 0) > 0 ? `
                <div style="font-size:12px;color:var(--status-telat);">
                    ⚠ Terlambat ${absensi.menit_telat} menit
                </div>` : ''}
                ` : `
                <div class="k-divider" aria-hidden="true"></div>
                <p style="font-size:13px;color:var(--text-muted);text-align:center;">
                    Belum ada data absensi untuk hari ini.
                </p>`}
            </div>`;
    }

    card.style.display = '';
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ══════════════════════════════════════════════════════════════════════════════
// 10. RINGKASAN
// ══════════════════════════════════════════════════════════════════════════════

function renderSummary(jadwalList) {
    const totalHariKerja = jadwalList.filter((j) => !j.is_hari_libur).length;
    const totalLibur     = jadwalList.filter((j) => j.is_hari_libur).length;
    const shiftPagi      = jadwalList.filter((j) => _getShiftKey(j.shift?.nama_shift) === 'pagi').length;
    const shiftMalam     = jadwalList.filter((j) => _getShiftKey(j.shift?.nama_shift) === 'malam').length;

    _setSummary('total-hari-kerja', totalHariKerja);
    _setSummary('total-libur', totalLibur);
    _setSummary('shift-pagi', shiftPagi);
    _setSummary('shift-malam', shiftMalam);
}

function _setSummary(key, val) {
    const el = document.querySelector(`[data-summary="${key}"]`);
    if (el) el.textContent = val;
}

// ══════════════════════════════════════════════════════════════════════════════
// 11. HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function updatePeriodLabel() {
    const el = document.getElementById('period-label');
    if (el) el.textContent = `${getBulanNama(state.bulan)} ${state.tahun}`;
}

/**
 * Map nama shift ke key CSS
 * @param {string} nama
 * @returns {'pagi'|'siang'|'malam'|'normal'|'libur'}
 */
function _getShiftKey(nama) {
    if (!nama) return 'normal';
    const n = nama.toLowerCase();
    if (n.includes('pagi'))   return 'pagi';
    if (n.includes('siang'))  return 'siang';
    if (n.includes('malam'))  return 'malam';
    if (n.includes('libur'))  return 'libur';
    return 'normal';
}

function showLoadingState() {
    const calGrid  = document.getElementById('calendar-grid');
    const listCont = document.getElementById('jadwal-list-container');

    if (calGrid) {
        calGrid.innerHTML = Array.from({ length: 35 }).map(() =>
            `<div class="k-cal-day">
                 <div class="k-skel k-skel--text" style="width:18px;height:12px;border-radius:3px;"></div>
             </div>`
        ).join('');
    }

    if (listCont) {
        listCont.innerHTML = renderSkeleton(5, 4);
    }
}

function showEmptyState() {
    const calGrid  = document.getElementById('calendar-grid');
    const listCont = document.getElementById('jadwal-list-container');

    const emptyHtml = `
        <div class="k-empty" style="grid-column:1/-1;">
            <p class="k-empty-title">Jadwal tidak tersedia</p>
            <p class="k-empty-desc">Belum ada planning kerja untuk periode ini.</p>
        </div>`;

    if (calGrid)  calGrid.innerHTML  = emptyHtml;
    if (listCont) listCont.innerHTML = emptyHtml;
}