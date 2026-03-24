/**
 * resources/js/karyawan/absensi.js
 *
 * Halaman Absensi GPS (F01).
 * Fitur utama:
 *   - navigator.geolocation.watchPosition() untuk update koordinat real-time
 *   - Animasi ring pulse GPS aktif
 *   - Tombol check-in / check-out berubah state berdasarkan status absensi hari ini
 *   - Progress bar jarak ke area
 *   - Feedback menit telat setelah check-in
 *   - Notifikasi pending lembur setelah check-out
 *
 * Endpoints:
 *   GET  /api/karyawan/jadwal?bulan=X&tahun=Y → cek jadwal & absensi hari ini
 *   GET  /api/karyawan/riwayat?bulan=X&tahun=Y → tabel riwayat mini
 *   POST /api/karyawan/check-in               → check-in
 *   POST /api/karyawan/check-out              → check-out
 */

'use strict';

import {
    apiFetch,
    toast,
    formatTime,
    formatDate,
    formatMinutes,
    formatDistance,
    haversineDistance,
    getBadgeHtml,
    watchGps,
    clearGpsWatch,
    renderSkeleton,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. STATE
// ══════════════════════════════════════════════════════════════════════════════

/** @type {{ lat: number|null, lng: number|null, accuracy: number|null }} */
const gpsState = { lat: null, lng: null, accuracy: null };

/** @type {number|null} GPS watch ID */
let gpsWatchId = null;

/** State absensi hari ini: null | 'belum_checkin' | 'sudah_checkin' | 'selesai' */
let absensiState = null;

/** Koordinat area aktif (dari meta atau estimasi) — tidak diperlukan untuk validasi backend,
 *  hanya digunakan untuk tampilan jarak di UI.
 *  Jika backend tidak mengekspos koordinat area, bar jarak tidak ditampilkan. */
let areaConfig = null; // { lat, lng, radius_meter }

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    await loadAbsensiStatus();
    startGpsWatch();
    loadRiwayatMini();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. BIND EVENTS
// ══════════════════════════════════════════════════════════════════════════════

function bindEvents() {
    document.getElementById('btn-refresh-gps')?.addEventListener('click', () => {
        clearGpsWatch(gpsWatchId);
        resetGpsUI();
        startGpsWatch();
    });
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. LOAD STATUS ABSENSI HARI INI
// ══════════════════════════════════════════════════════════════════════════════

async function loadAbsensiStatus() {
    const now = new Date();
    const bulan = now.getMonth() + 1;
    const tahun = now.getFullYear();

    const [jadwalRes] = await Promise.allSettled([
        apiFetch(`/api/karyawan/jadwal?bulan=${bulan}&tahun=${tahun}`),
    ]);

    showTodayContent();

    if (jadwalRes.status !== 'fulfilled' || !jadwalRes.value?.status) {
        setAbsensiState('belum_checkin');
        toast('Gagal memuat data jadwal. Pastikan koneksi internet aktif.', 'error');
        return;
    }

    const today = now.toISOString().split('T')[0];
    const jadwalList = jadwalRes.value.data?.jadwal ?? [];
    const jadwalHariIni = jadwalList.find((j) => j.tanggal_kerja === today);

    if (!jadwalHariIni || jadwalHariIni.is_hari_libur) {
        setAbsensiState('libur');
        return;
    }

    const absensi = jadwalHariIni.absensi;

    if (!absensi) {
        setAbsensiState('belum_checkin');
    } else if (absensi.waktu_check_in && !absensi.waktu_check_out) {
        setAbsensiState('sudah_checkin', {
            waktu_check_in: absensi.waktu_check_in,
            menit_telat:    absensi.menit_telat,
            shift:          jadwalHariIni.shift,
        });
    } else if (absensi.waktu_check_in && absensi.waktu_check_out) {
        setAbsensiState('selesai', {
            waktu_check_in:     absensi.waktu_check_in,
            waktu_check_out:    absensi.waktu_check_out,
            menit_kerja_normal: absensi.menit_kerja_normal,
            menit_telat:        absensi.menit_telat,
        });
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. STATE MACHINE ABSENSI UI
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Update seluruh UI berdasarkan state absensi.
 * @param {'belum_checkin'|'sudah_checkin'|'selesai'|'libur'} state
 * @param {object} [data]
 */
function setAbsensiState(state, data = {}) {
    absensiState = state;

    const container = document.getElementById('absensi-btn-container');
    const infoText  = document.getElementById('absensi-info-text');

    switch (state) {
        case 'belum_checkin':
            renderCheckinButton();
            if (infoText) infoText.textContent =
                'Pastikan GPS aktif dan Anda berada dalam radius area PT Ecogreen Oleochemicals.';
            hideCheckinCard();
            hideCheckoutCard();
            break;

        case 'sudah_checkin':
            renderCheckoutButton();
            showCheckinCard(data);
            hideCheckoutCard();
            if (infoText) infoText.textContent =
                'Anda sudah check-in. Tekan tombol di atas saat siap pulang.';
            break;

        case 'selesai':
            renderDoneState();
            showCheckinCard(data);
            showCheckoutCard(data);
            if (infoText) infoText.textContent =
                'Absensi hari ini sudah selesai. Sampai jumpa besok!';
            break;

        case 'libur':
            renderLiburState();
            hideCheckinCard();
            hideCheckoutCard();
            if (infoText) infoText.textContent =
                'Hari ini adalah hari libur. Tidak perlu melakukan absensi.';
            break;
    }
}

// ── Render tombol ─────────────────────────────────────────────────────────────

function renderCheckinButton() {
    const container = document.getElementById('absensi-btn-container');
    if (!container) return;

    const rings = _buildRings('absensi-ring');
    container.innerHTML = `
        ${rings}
        <button class="k-absensi-btn k-absensi-btn--checkin k-absensi-btn--disabled"
                id="btn-checkin"
                aria-label="Check-In Absensi Masuk"
                disabled>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
            </svg>
            <span class="k-absensi-btn-label">CHECK-IN</span>
            <span class="k-absensi-btn-sub" id="btn-sub-text">Menunggu GPS…</span>
        </button>`;

    document.getElementById('btn-checkin')?.addEventListener('click', handleCheckin);
}

function renderCheckoutButton() {
    const container = document.getElementById('absensi-btn-container');
    if (!container) return;

    const rings = _buildRings('absensi-ring k-absensi-ring--checkout');
    container.innerHTML = `
        ${rings}
        <button class="k-absensi-btn k-absensi-btn--checkout k-absensi-btn--disabled"
                id="btn-checkout"
                aria-label="Check-Out Absensi Pulang"
                disabled>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v1"/>
            </svg>
            <span class="k-absensi-btn-label">CHECK-OUT</span>
            <span class="k-absensi-btn-sub" id="btn-sub-text">Menunggu GPS…</span>
        </button>`;

    document.getElementById('btn-checkout')?.addEventListener('click', handleCheckout);
}

function renderDoneState() {
    const container = document.getElementById('absensi-btn-container');
    if (!container) return;
    container.innerHTML = `
        <div style="width:176px;height:176px;border-radius:50%;
                    background:linear-gradient(145deg,#16a34a,#166534);
                    display:flex;flex-direction:column;align-items:center;
                    justify-content:center;gap:var(--space-2);
                    box-shadow:0 8px 32px rgba(22,163,74,0.3);">
            <svg width="48" height="48" fill="none" stroke="#ffffff" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <span style="font-size:14px;font-weight:700;color:#fff;letter-spacing:0.05em;">
                SELESAI
            </span>
        </div>`;
}

function renderLiburState() {
    const container = document.getElementById('absensi-btn-container');
    if (!container) return;
    container.innerHTML = `
        <div style="width:176px;height:176px;border-radius:50%;
                    background:linear-gradient(145deg,#64748b,#475569);
                    display:flex;flex-direction:column;align-items:center;
                    justify-content:center;gap:var(--space-2);
                    box-shadow:0 8px 32px rgba(100,116,139,0.3);">
            <svg width="48" height="48" fill="none" stroke="#ffffff" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9.003 9.003 0 0 0 12 21a9.003 9.003 0 0 0 8.354-5.646z"/>
            </svg>
            <span style="font-size:14px;font-weight:700;color:#fff;letter-spacing:0.05em;">
                LIBUR
            </span>
        </div>`;
}

function _buildRings(cls) {
    return `
        <div class="${cls}" style="display:none;" id="ring-1"></div>
        <div class="${cls}" style="display:none;" id="ring-2"></div>
        <div class="${cls}" style="display:none;" id="ring-3"></div>`;
}

// ── Show/hide status cards ────────────────────────────────────────────────────

function showTodayContent() {
    const skeleton = document.getElementById('absensi-today-skeleton');
    const content  = document.getElementById('absensi-today-content');
    if (skeleton) skeleton.style.display = 'none';
    if (content)  content.style.display  = 'flex';
}

function showCheckinCard(data) {
    const card = document.getElementById('checkin-card');
    if (!card) return;
    card.style.display = 'flex';

    document.getElementById('checkin-time').textContent = formatTime(data.waktu_check_in);

    const shift = data.shift;
    if (shift) {
        document.getElementById('checkin-jadwal').textContent =
            `Jadwal masuk: ${shift.jam_masuk ?? '—'}`;
    }

    const telatBadge = document.getElementById('telat-badge');
    const telatMenit = document.getElementById('telat-menit');
    if ((data.menit_telat ?? 0) > 0) {
        telatBadge.style.display = 'inline-flex';
        telatMenit.textContent = `${data.menit_telat} mnt`;
    } else {
        telatBadge.style.display = 'none';
    }
}

function hideCheckinCard() {
    const card = document.getElementById('checkin-card');
    if (card) card.style.display = 'none';
}

function showCheckoutCard(data) {
    const card = document.getElementById('checkout-card');
    if (!card) return;
    card.style.display = 'flex';

    document.getElementById('checkout-time').textContent = formatTime(data.waktu_check_out);
    document.getElementById('menit-kerja-info').textContent =
        `Menit kerja normal: ${formatMinutes(data.menit_kerja_normal)}`;
}

function hideCheckoutCard() {
    const card = document.getElementById('checkout-card');
    if (card) card.style.display = 'none';
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. GPS WATCH
// ══════════════════════════════════════════════════════════════════════════════

function startGpsWatch() {
    updateGpsUI({ status: 'pending', text: 'Mengakses lokasi GPS…' });

    gpsWatchId = watchGps({
        onUpdate: (coords) => {
            gpsState.lat      = coords.lat;
            gpsState.lng      = coords.lng;
            gpsState.accuracy = coords.accuracy;
            onGpsSuccess(coords);
        },
        onError: (msg) => {
            onGpsError(msg);
        },
    });
}

function onGpsSuccess({ lat, lng, accuracy }) {
    const coordText = `${lat.toFixed(6)}, ${lng.toFixed(6)} ±${Math.round(accuracy)}m`;
    updateGpsUI({ status: 'ok', text: 'Lokasi GPS aktif', coords: coordText });
    enableAbsensiButton();
    showRings();

    // Tampilkan progress jarak jika areaConfig tersedia
    // (areaConfig di-set dari response server jika diekspos, untuk saat ini UI bar tidak wajib)
}

function onGpsError(msg) {
    updateGpsUI({ status: 'error', text: msg, coords: '—' });
    disableAbsensiButton(msg.length > 50 ? 'GPS tidak tersedia' : msg);
    hideRings();
    hideDistanceBar();
}

function resetGpsUI() {
    gpsState.lat = null;
    gpsState.lng = null;
    gpsState.accuracy = null;
    updateGpsUI({ status: 'pending', text: 'Mengakses lokasi GPS…', coords: '—' });
    disableAbsensiButton('Menunggu GPS…');
    hideRings();
    hideDistanceBar();
}

// ── GPS UI helpers ────────────────────────────────────────────────────────────

function updateGpsUI({ status, text, coords = null }) {
    const dot      = document.getElementById('gps-dot');
    const statusEl = document.getElementById('gps-status-text');
    const coordEl  = document.getElementById('gps-coords');

    if (dot) {
        dot.className = `k-gps-dot k-gps-dot--${status}`;
    }
    if (statusEl) statusEl.textContent = text;
    if (coordEl && coords !== null) coordEl.textContent = coords;
}

function enableAbsensiButton() {
    // Hanya enable jika state bukan selesai/libur
    if (absensiState === 'selesai' || absensiState === 'libur') return;

    const btn = document.getElementById('btn-checkin') ?? document.getElementById('btn-checkout');
    if (!btn) return;
    btn.disabled = false;
    btn.classList.remove('k-absensi-btn--disabled');

    const subEl = document.getElementById('btn-sub-text');
    if (subEl) {
        subEl.textContent = absensiState === 'sudah_checkin'
            ? 'Tap untuk absen pulang'
            : 'Tap untuk absen masuk';
    }
}

function disableAbsensiButton(reason = 'GPS tidak aktif') {
    const btn = document.getElementById('btn-checkin') ?? document.getElementById('btn-checkout');
    if (!btn) return;
    btn.disabled = true;
    btn.classList.add('k-absensi-btn--disabled');

    const subEl = document.getElementById('btn-sub-text');
    if (subEl) subEl.textContent = reason;
}

function showRings() {
    ['ring-1','ring-2','ring-3'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = '';
    });
}

function hideRings() {
    ['ring-1','ring-2','ring-3'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function hideDistanceBar() {
    const row = document.getElementById('gps-distance-row');
    if (row) row.style.display = 'none';
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. CHECK-IN
// ══════════════════════════════════════════════════════════════════════════════

async function handleCheckin() {
    if (!gpsState.lat || !gpsState.lng) {
        toast('Koordinat GPS belum tersedia. Tunggu sebentar dan coba lagi.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-checkin');
    setButtonLoading(btn, true);

    try {
        const res = await apiFetch('/api/karyawan/check-in', {
            method: 'POST',
            body: { latitude: gpsState.lat, longitude: gpsState.lng },
        });

        if (!res.status) {
            toast(res.message ?? 'Gagal melakukan check-in.', 'error', 5000);
            return;
        }

        const data = res.data ?? {};
        toast(res.message, 'success', 4000);

        // Update UI ke state sudah_checkin
        setAbsensiState('sudah_checkin', {
            waktu_check_in: `${data.tanggal ?? ''} ${data.waktu_check_in ?? ''}`,
            menit_telat:    data.menit_telat ?? 0,
        });

        // Reload riwayat mini
        loadRiwayatMini();

    } catch (err) {
        toast(err.message, 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. CHECK-OUT
// ══════════════════════════════════════════════════════════════════════════════

async function handleCheckout() {
    if (!gpsState.lat || !gpsState.lng) {
        toast('Koordinat GPS belum tersedia. Tunggu sebentar dan coba lagi.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-checkout');
    setButtonLoading(btn, true);

    try {
        const res = await apiFetch('/api/karyawan/check-out', {
            method: 'POST',
            body: { latitude: gpsState.lat, longitude: gpsState.lng },
        });

        if (!res.status) {
            toast(res.message ?? 'Gagal melakukan check-out.', 'error', 5000);
            return;
        }

        const data = res.data ?? {};
        toast('Check-out berhasil dicatat!', 'success', 4000);

        // Update UI ke state selesai
        setAbsensiState('selesai', {
            waktu_check_in:     null, // sudah ditampilkan dari state sebelumnya
            waktu_check_out:    data.waktu_check_out,
            menit_kerja_normal: data.menit_kerja_normal ?? 0,
            menit_telat:        0,
        });

        // Tampilkan banner pending lembur jika ada kelebihan
        if (data.pending_lembur) {
            showPendingLemburBanner(data);
        }

        // Stop GPS watch setelah selesai (hemat baterai)
        clearGpsWatch(gpsWatchId);
        gpsWatchId = null;

        // Reload riwayat mini
        loadRiwayatMini();

    } catch (err) {
        toast(err.message, 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}

function showPendingLemburBanner(data) {
    const banner = document.getElementById('pending-lembur-banner');
    const text   = document.getElementById('pending-lembur-text');
    if (!banner) return;

    if (text) {
        text.textContent =
            `Anda memiliki ${data.menit_kelebihan} menit kelebihan waktu kerja. ` +
            `Ajukan form lembur paling lambat ${data.batas_lembur ?? 'H+1'}.`;
    }

    banner.style.display = '';
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ══════════════════════════════════════════════════════════════════════════════
// 9. RIWAYAT MINI (5 baris terakhir)
// ══════════════════════════════════════════════════════════════════════════════

async function loadRiwayatMini() {
    const tbody = document.getElementById('tbody-riwayat-absensi-mini');
    if (!tbody) return;

    tbody.innerHTML = renderSkeleton(3, 5);

    const now = new Date();
    const bulan = now.getMonth() + 1;
    const tahun = now.getFullYear();

    try {
        const res = await apiFetch(`/api/karyawan/riwayat?bulan=${bulan}&tahun=${tahun}&page=1`);

        if (!res.status) {
            tbody.innerHTML = `<tr><td colspan="5" class="k-empty-title" style="text-align:center;padding:16px;">
                                   Gagal memuat riwayat.</td></tr>`;
            return;
        }

        const rows = (res.data?.data ?? []).slice(0, 5);

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;
                                   color:var(--text-muted);font-size:13px;">
                                   Belum ada data absensi bulan ini.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>
                    <span style="font-size:12px;color:var(--text-secondary);">
                        ${row.hari ?? '—'}, ${row.tanggal_absensi ?? '—'}
                    </span>
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
                <td>${getBadgeHtml(row.status_kehadiran)}</td>
            </tr>`).join('');

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:16px;
                               color:var(--text-muted);font-size:13px;">
                               ${_escapeHtml(err.message)}</td></tr>`;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 10. BUTTON LOADING STATE
// ══════════════════════════════════════════════════════════════════════════════

function setButtonLoading(btn, isLoading) {
    if (!btn) return;
    btn.disabled = isLoading;
    if (isLoading) {
        btn.classList.add('k-absensi-btn--loading');
    } else {
        btn.classList.remove('k-absensi-btn--loading');
    }
}