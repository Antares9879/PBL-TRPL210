/**
 * resources/js/karyawan/dashboard.js
 *
 * Dashboard halaman utama karyawan.
 * Fetch sekali saat halaman dibuka (tidak auto-refresh).
 *
 * Data yang dimuat:
 *   1. GET /api/karyawan/jadwal         → shift hari ini
 *   2. GET /api/karyawan/riwayat/ringkasan → ringkasan bulan ini
 *   3. GET /api/karyawan/riwayat (page=1, per page kecil) → absensi hari ini
 *   4. GET /api/karyawan/lembur?status=menunggu → pengajuan lembur aktif
 *   5. GET /api/karyawan/izin?status=menunggu   → pengajuan izin aktif
 */

'use strict';

import {
    apiFetch,
    toast,
    formatTime,
    formatDate,
    formatMinutes,
    getBadgeHtml,
    getBulanNama,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    renderLiveClock();
    loadDashboardData();
});

// ══════════════════════════════════════════════════════════════════════════════
// 2. LIVE CLOCK
// ══════════════════════════════════════════════════════════════════════════════

function renderLiveClock() {
    const el = document.getElementById('live-date');
    if (!el) return;

    function update() {
        const now = new Date();
        const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][now.getDay()];
        const tgl = now.getDate();
        const bln = getBulanNama(now.getMonth() + 1);
        const thn = now.getFullYear();
        const jam = String(now.getHours()).padStart(2, '0');
        const mnt = String(now.getMinutes()).padStart(2, '0');
        el.textContent = `${hari}, ${tgl} ${bln} ${thn} • ${jam}:${mnt}`;
    }

    update();
    setInterval(update, 30_000); // update tiap 30 detik
}

// ══════════════════════════════════════════════════════════════════════════════
// 3. LOAD SEMUA DATA DASHBOARD
// ══════════════════════════════════════════════════════════════════════════════

async function loadDashboardData() {
    const now = new Date();
    const bulan = now.getMonth() + 1;
    const tahun = now.getFullYear();

    // Jalankan semua fetch secara paralel
    const [jadwalRes, ringkasanRes, lemburRes, izinRes] = await Promise.allSettled([
        apiFetch(`/api/karyawan/jadwal?bulan=${bulan}&tahun=${tahun}`),
        apiFetch(`/api/karyawan/riwayat/ringkasan?bulan=${bulan}&tahun=${tahun}`),
        apiFetch('/api/karyawan/lembur?status=menunggu'),
        apiFetch('/api/karyawan/izin?status=menunggu'),
    ]);

    // Render masing-masing section
    renderShiftHariIni(jadwalRes);
    renderAbsensiHariIni(jadwalRes);
    renderRingkasan(ringkasanRes, bulan, tahun);
    renderPengajuanAktif(lemburRes, izinRes);
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. SHIFT HARI INI
// ══════════════════════════════════════════════════════════════════════════════

function renderShiftHariIni(jadwalRes) {
    const nameEl = document.getElementById('today-shift-name');
    const timeEl = document.getElementById('today-shift-time');
    if (!nameEl || !timeEl) return;

    if (jadwalRes.status !== 'fulfilled' || !jadwalRes.value?.status) {
        nameEl.textContent = 'Jadwal tidak tersedia';
        timeEl.querySelector('span').textContent = '—';
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    const jadwalList = jadwalRes.value.data?.jadwal ?? [];
    const jadwalHariIni = jadwalList.find((j) => j.tanggal_kerja === today);

    if (!jadwalHariIni) {
        nameEl.textContent = 'Tidak ada jadwal hari ini';
        timeEl.querySelector('span').textContent = 'Hari libur atau belum dijadwalkan';
        return;
    }

    if (jadwalHariIni.is_hari_libur) {
        nameEl.textContent = 'Hari Libur';
        timeEl.querySelector('span').textContent = 'Tidak ada shift hari ini';
        return;
    }

    const shift = jadwalHariIni.shift;
    nameEl.textContent = shift?.nama_shift ?? '—';
    const jam = shift ? `${shift.jam_masuk} – ${shift.jam_pulang}` : '—';
    timeEl.querySelector('span').textContent = jam;
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. STATUS ABSENSI HARI INI (dari data jadwal yang sudah include absensi)
// ══════════════════════════════════════════════════════════════════════════════

function renderAbsensiHariIni(jadwalRes) {
    const checkinEl  = document.getElementById('today-checkin-time');
    const checkoutEl = document.getElementById('today-checkout-time');
    const menitEl    = document.getElementById('today-menit-kerja');
    const statusBody = document.getElementById('absensi-status-body');

    if (!checkinEl) return;

    if (jadwalRes.status !== 'fulfilled' || !jadwalRes.value?.status) {
        _setAbsensiChipEmpty(checkinEl, checkoutEl, menitEl);
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    const jadwalList = jadwalRes.value.data?.jadwal ?? [];
    const jadwalHariIni = jadwalList.find((j) => j.tanggal_kerja === today);
    const absensi = jadwalHariIni?.absensi ?? null;

    if (!absensi) {
        _setAbsensiChipEmpty(checkinEl, checkoutEl, menitEl);
        if (statusBody) {
            statusBody.innerHTML = `
                <div class="k-alert k-alert--info" style="font-size:13px;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    <span>Belum melakukan absensi hari ini. 
                          <a href="/karyawan/absensi" style="font-weight:600;">Absen sekarang →</a>
                    </span>
                </div>`;
        }
        return;
    }

    // Update chip check-in
    if (absensi.waktu_check_in) {
        checkinEl.textContent = formatTime(absensi.waktu_check_in);
        checkinEl.className = 'k-today-absensi-chip-val k-today-absensi-chip-val--ok';
    } else {
        _setChipEmpty(checkinEl, '—');
    }

    // Update chip check-out
    if (absensi.waktu_check_out) {
        checkoutEl.textContent = formatTime(absensi.waktu_check_out);
        checkoutEl.className = 'k-today-absensi-chip-val k-today-absensi-chip-val--ok';
    } else {
        _setChipEmpty(checkoutEl, '—');
    }

    // Update chip menit kerja
    if (absensi.menit_kerja_normal > 0) {
        menitEl.textContent = formatMinutes(absensi.menit_kerja_normal);
        menitEl.className = 'k-today-absensi-chip-val k-today-absensi-chip-val--ok';
    } else {
        _setChipEmpty(menitEl, '—');
    }

    // Status card di bawah
    if (statusBody) {
        const telat = absensi.menit_telat > 0
            ? `<span style="color:var(--status-telat);font-weight:600;margin-left:6px;">
                +${absensi.menit_telat} mnt telat</span>`
            : '';
        statusBody.innerHTML = `
            <div style="display:flex;flex-direction:column;gap:var(--space-2);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Status Kehadiran</span>
                    ${getBadgeHtml(absensi.status_kehadiran)}
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:13px;color:var(--text-muted);">Status Validasi</span>
                    ${getBadgeHtml(absensi.status_validasi, 'validasi')}
                </div>
                ${absensi.menit_telat > 0 ? `
                <div style="font-size:12px;color:var(--status-telat);">
                    ⚠ Terlambat ${absensi.menit_telat} menit dari jadwal
                </div>` : ''}
            </div>`;
    }
}

function _setAbsensiChipEmpty(c, co, m) {
    [c, co, m].forEach((el) => { if (el) _setChipEmpty(el, '—'); });
}

function _setChipEmpty(el, text) {
    if (!el) return;
    el.textContent = text;
    el.className = 'k-today-absensi-chip-val k-today-absensi-chip-val--empty';
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. RINGKASAN BULAN INI
// ══════════════════════════════════════════════════════════════════════════════

function renderRingkasan(ringkasanRes, bulan, tahun) {
    if (ringkasanRes.status !== 'fulfilled' || !ringkasanRes.value?.status) return;

    const d = ringkasanRes.value.data;
    if (!d) return;

    // Stat cards
    _setStat('total-hadir',        d.total_hari_hadir ?? 0);
    _setStat('total-lembur',       d.total_menit_lembur_resmi ?? 0);
    _setStat('total-izin',         d.total_hari_izin ?? 0);
    _setStat('total-telat',        d.total_menit_telat ?? 0);

    // Progress bar
    const totalNormal = d.total_menit_kerja_normal ?? 0;
    // Target menit normal = jumlah hari hadir × 480 menit
    const targetMenit = (d.total_hari_hadir ?? 0) * 480 || 1;
    const pct = Math.min(100, Math.round((totalNormal / targetMenit) * 100));

    _setStat('total-menit-normal', formatMinutes(totalNormal), false);
    _setStat('target-menit-normal', targetMenit, false);
    _setStat('pct-hadir', `${pct}%`, false);
    _setStat('pending-validasi', d.total_pending_validasi ?? 0, false);

    const progressBar = document.getElementById('progress-menit');
    if (progressBar) {
        // Animasi setelah render
        requestAnimationFrame(() => { progressBar.style.width = `${pct}%`; });
    }

    // Label periode
    const periodeEl = document.getElementById('progress-periode-label');
    if (periodeEl) periodeEl.textContent = `${getBulanNama(bulan)} ${tahun}`;
}

function _setStat(key, value, raw = true) {
    const el = document.querySelector(`[data-stat="${key}"]`);
    if (!el) return;
    el.textContent = raw ? value : value;
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. PENGAJUAN AKTIF (lembur + izin menunggu)
// ══════════════════════════════════════════════════════════════════════════════

function renderPengajuanAktif(lemburRes, izinRes) {
    const container = document.getElementById('pengajuan-pending-list');
    if (!container) return;

    const items = [];

    // Tambah lembur yang menunggu
    if (lemburRes.status === 'fulfilled' && lemburRes.value?.status) {
        const list = lemburRes.value.data?.data ?? [];
        list.slice(0, 3).forEach((l) => {
            items.push({
                type: 'lembur',
                title: `Lembur ${l.tanggal_lembur ?? '—'}`,
                meta: `${formatMinutes(l.menit_lembur_diajukan)} • Batas: ${l.batas_pengajuan ?? '—'}`,
                status: l.status,
                href: '/karyawan/lembur',
            });
        });
    }

    // Tambah izin yang menunggu
    if (izinRes.status === 'fulfilled' && izinRes.value?.status) {
        const list = izinRes.value.data?.data ?? [];
        list.slice(0, 3).forEach((i) => {
            items.push({
                type: 'izin',
                title: `Izin ${i.jenis_izin?.nama_jenis ?? '—'} — ${i.tanggal_izin ?? '—'}`,
                meta: i.status_dokumen === 'belum_upload' ? '⚠ Dokumen belum diunggah' : 'Menunggu validasi admin',
                status: i.status,
                href: '/karyawan/izin',
            });
        });
    }

    if (items.length === 0) {
        container.innerHTML = `
            <div class="k-empty" style="padding:var(--space-6) var(--space-4);">
                <p class="k-empty-title">Tidak ada pengajuan aktif</p>
                <p class="k-empty-desc">Semua pengajuan lembur dan izin sudah diproses.</p>
            </div>`;
        return;
    }

    const iconMap = {
        lembur:  { cls: 'k-pengajuan-icon--menunggu', svg: clock_svg() },
        izin:    { cls: 'k-pengajuan-icon--menunggu', svg: doc_svg() },
    };

    container.innerHTML = items.map((item) => {
        const icon = iconMap[item.type] ?? iconMap.izin;
        return `
            <a href="${item.href}" class="k-pengajuan-item"
               style="text-decoration:none;color:inherit;">
                <div class="k-pengajuan-icon ${icon.cls}">${icon.svg}</div>
                <div class="k-pengajuan-body">
                    <p class="k-pengajuan-title">${_escapeHtml(item.title)}</p>
                    <div class="k-pengajuan-meta">
                        <span>${_escapeHtml(item.meta)}</span>
                    </div>
                </div>
                <div class="k-pengajuan-actions">
                    ${getBadgeHtml(item.status, 'validasi')}
                </div>
            </a>`;
    }).join('');
}

// ── SVG mini helpers ──────────────────────────────────────────────────────────

function clock_svg() {
    return `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>`;
}

function doc_svg() {
    return `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>`;
}