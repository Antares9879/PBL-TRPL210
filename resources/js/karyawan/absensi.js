/**
 * resources/js/karyawan/absensi.js
 *
 * Halaman Absensi GPS (F01).
 * Fitur:
 *   - navigator.geolocation.watchPosition() untuk update koordinat real-time
 *   - Animasi ring pulse GPS aktif
 *   - Tombol check-in / check-out berubah state berdasarkan status absensi hari ini
 *   - Progress bar jarak ke area
 *   - Feedback menit telat setelah check-in
 *   - Notifikasi pending lembur setelah check-out
 *   - [NEw] Tombol "Lihat Peta" → modal preview map Leaflet
 *   - [NEW] Modal konfirmasi sebelum check-in / check-out dengan info lokasi
 *
 * Endpoints:
 *   GET  /api/karyawan/jadwal?bulan=X&tahun=Y  → cek jadwal & absensi hari ini
 *   GET  /api/karyawan/riwayat?bulan=X&tahun=Y → tabel riwayat mini
 *   GET  /api/karyawan/area-aktif              → koordinat area + radius untuk peta
 *   POST /api/karyawan/check-in               → check-in
 *   POST /api/karyawan/check-out              → check-out
 *
 * Dependencies (CDN, sudah di-load di Blade):
 *   - Leaflet.js  https://unpkg.com/leaflet@1.9.4/dist/leaflet.js
 *   - Leaflet CSS https://unpkg.com/leaflet@1.9.4/dist/leaflet.css
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

/** State absensi hari ini */
let absensiState = null; // null | 'belum_checkin' | 'sudah_checkin' | 'selesai' | 'libur'

/**
 * Data area aktif dari GET /api/karyawan/area-aktif
 * @type {{ id_konfigurasi, nama_area, latitude_pusat, longitude_pusat, radius_meter }|null}
 */
let areaConfig = null;

/**
 * Instance peta Leaflet (jika sudah diinisialisasi)
 * @type {import('leaflet').Map|null}
 */
let leafletMap   = null;
let leafletKaryawanMarker = null;
let leafletAreaCircle     = null;
let leafletPusatMarker    = null;

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    await Promise.allSettled([
        loadAbsensiStatus(),
        loadAreaConfig(),
    ]);
    startGpsWatch();
    loadRiwayatMini();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. BIND EVENTS
// ══════════════════════════════════════════════════════════════════════════════

function bindEvents() {
    // Refresh GPS
    document.getElementById('btn-refresh-gps')?.addEventListener('click', () => {
        clearGpsWatch(gpsWatchId);
        resetGpsUI();
        startGpsWatch();
    });

    // Tombol lihat peta (selalu tersedia)
    document.getElementById('btn-lihat-peta')?.addEventListener('click', openMapModal);

    // Tutup modal peta
    document.getElementById('btn-close-map-modal')?.addEventListener('click', closeMapModal);
    document.getElementById('map-modal-overlay')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('map-modal-overlay')) closeMapModal();
    });

    // Tutup modal konfirmasi
    document.getElementById('btn-close-confirm-modal')?.addEventListener('click', closeConfirmModal);
    document.getElementById('btn-cancel-confirm')?.addEventListener('click', closeConfirmModal);
    document.getElementById('confirm-modal-overlay')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('confirm-modal-overlay')) closeConfirmModal();
    });

    // Tombol konfirmasi di modal konfirmasi
    document.getElementById('btn-proceed-absensi')?.addEventListener('click', executePendingAbsensi);
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. LOAD AREA CONFIG
// ══════════════════════════════════════════════════════════════════════════════

async function loadAreaConfig() {
    try {
        const res = await apiFetch('/api/karyawan/area-aktif');
        if (res.status && res.data) {
            areaConfig = res.data;
        }
    } catch {
        // silent — peta tetap bisa ditampilkan dengan marker karyawan saja
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. LOAD STATUS ABSENSI HARI INI
// ══════════════════════════════════════════════════════════════════════════════

async function loadAbsensiStatus() {
    const now   = new Date();
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

    const today      = now.toISOString().split('T')[0];
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
// 6. STATE MACHINE ABSENSI UI
// ══════════════════════════════════════════════════════════════════════════════

function setAbsensiState(state, data = {}) {
    absensiState = state;
    const infoText = document.getElementById('absensi-info-text');

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
    container.innerHTML = `
        ${_buildRings('k-absensi-ring')}
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
    container.innerHTML = `
        ${_buildRings('k-absensi-ring k-absensi-ring--checkout')}
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
            <span style="font-size:14px;font-weight:700;color:#fff;letter-spacing:0.05em;">SELESAI</span>
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
            <span style="font-size:14px;font-weight:700;color:#fff;letter-spacing:0.05em;">LIBUR</span>
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
        document.getElementById('checkin-jadwal').textContent = `Jadwal masuk: ${shift.jam_masuk ?? '—'}`;
    }
    const telatBadge = document.getElementById('telat-badge');
    const telatMenit = document.getElementById('telat-menit');
    if ((data.menit_telat ?? 0) > 0) {
        telatBadge.style.display = 'inline-flex';
        telatMenit.textContent   = `${data.menit_telat} mnt`;
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
    document.getElementById('checkout-time').textContent   = formatTime(data.waktu_check_out);
    document.getElementById('menit-kerja-info').textContent = `Menit kerja normal: ${formatMinutes(data.menit_kerja_normal)}`;
}

function hideCheckoutCard() {
    const card = document.getElementById('checkout-card');
    if (card) card.style.display = 'none';
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. GPS WATCH
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
    updateDistanceBar(lat, lng);

    // Update marker karyawan di peta jika sudah terbuka
    if (leafletMap && leafletKaryawanMarker) {
        leafletKaryawanMarker.setLatLng([lat, lng]);
        updateMapDistanceInfo(lat, lng);
    }
}

function onGpsError(msg) {
    updateGpsUI({ status: 'error', text: msg, coords: '—' });
    disableAbsensiButton(msg.length > 50 ? 'GPS tidak tersedia' : msg);
    hideRings();
}

function resetGpsUI() {
    gpsState.lat = null;
    gpsState.lng = null;
    gpsState.accuracy = null;
    updateGpsUI({ status: 'pending', text: 'Mengakses lokasi GPS…', coords: '—' });
    disableAbsensiButton('Menunggu GPS…');
    hideRings();
}

// ── GPS UI helpers ────────────────────────────────────────────────────────────

function updateGpsUI({ status, text, coords = null }) {
    const dot      = document.getElementById('gps-dot');
    const statusEl = document.getElementById('gps-status-text');
    const coordEl  = document.getElementById('gps-coords');
    if (dot)     dot.className = `k-gps-dot k-gps-dot--${status}`;
    if (statusEl) statusEl.textContent = text;
    if (coordEl && coords !== null) coordEl.textContent = coords;
}

function enableAbsensiButton() {
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
    ['ring-1', 'ring-2', 'ring-3'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = '';
    });
}

function hideRings() {
    ['ring-1', 'ring-2', 'ring-3'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function updateDistanceBar(lat, lng) {
    const row  = document.getElementById('gps-distance-row');
    const bar  = document.getElementById('gps-distance-bar');
    const text = document.getElementById('gps-distance-text');
    if (!areaConfig || !row) return;

    row.style.display = '';
    const jarak  = haversineDistance(lat, lng, areaConfig.latitude_pusat, areaConfig.longitude_pusat);
    const radius = areaConfig.radius_meter;
    const pct    = Math.min(100, Math.round((jarak / (radius * 2)) * 100));

    if (bar) {
        bar.style.width      = `${pct}%`;
        bar.style.background = jarak <= radius ? '#16a34a' : '#ef4444';
    }
    if (text) text.textContent = formatDistance(jarak);
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. MODAL PETA LEAFLET
// ══════════════════════════════════════════════════════════════════════════════

function openMapModal() {
    const overlay = document.getElementById('map-modal-overlay');
    if (!overlay) return;
    overlay.classList.add('k-modal--open');

    // Inisialisasi Leaflet setelah modal terlihat
    // (Leaflet butuh elemen yang visible untuk menghitung dimensi)
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            initLeafletMap();
        });
    });
}

function closeMapModal() {
    const overlay = document.getElementById('map-modal-overlay');
    overlay?.classList.remove('k-modal--open');
}

/**
 * Inisialisasi atau refresh peta Leaflet.
 * Dipanggil setiap kali modal dibuka.
 */
function initLeafletMap() {
    const container = document.getElementById('leaflet-map-container');
    if (!container) return;

    // Tentukan center: prioritas area config → GPS → default Batam
    const centerLat = areaConfig?.latitude_pusat ?? gpsState.lat ?? 1.0456;
    const centerLng = areaConfig?.longitude_pusat ?? gpsState.lng ?? 104.0305;
    const zoom      = areaConfig ? 16 : 13;

    if (!leafletMap) {
        // Buat instance baru
        /* global L */
        leafletMap = L.map(container, {
            center: [centerLat, centerLng],
            zoom,
            zoomControl: true,
            attributionControl: true,
        });

        // Tile layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(leafletMap);

    } else {
        // Reset view
        leafletMap.setView([centerLat, centerLng], zoom);
    }

    // Hapus layer lama sebelum re-render
    if (leafletAreaCircle)    { leafletAreaCircle.remove();    leafletAreaCircle    = null; }
    if (leafletPusatMarker)   { leafletPusatMarker.remove();   leafletPusatMarker   = null; }
    if (leafletKaryawanMarker){ leafletKaryawanMarker.remove(); leafletKaryawanMarker = null; }

    // Lingkaran radius area + marker pusat
    if (areaConfig) {
        const { latitude_pusat: aLat, longitude_pusat: aLng, radius_meter, nama_area } = areaConfig;

        leafletAreaCircle = L.circle([aLat, aLng], {
            radius:      radius_meter,
            color:       '#16a34a',
            fillColor:   '#16a34a',
            fillOpacity: 0.10,
            weight:      2,
            dashArray:   '6 4',
        }).addTo(leafletMap).bindPopup(
            `<strong>${_escapeHtml(nama_area ?? 'Area Absensi')}</strong><br>Radius: ${radius_meter} m`
        );

        leafletPusatMarker = L.marker([aLat, aLng], {
            icon: L.divIcon({
                className: '',
                html: `<div style="
                    width:14px;height:14px;border-radius:50%;
                    background:#166534;border:3px solid #fff;
                    box-shadow:0 0 0 2px #16a34a;"></div>`,
                iconSize:   [14, 14],
                iconAnchor: [7, 7],
            }),
        }).addTo(leafletMap).bindPopup(`<strong>Pusat Area</strong><br>${_escapeHtml(nama_area ?? '')}`);
    }

    // Marker posisi karyawan (jika GPS sudah dapat)
    if (gpsState.lat !== null && gpsState.lng !== null) {
        leafletKaryawanMarker = L.marker([gpsState.lat, gpsState.lng], {
            icon: L.divIcon({
                className: '',
                html: `<div style="
                    width:18px;height:18px;border-radius:50%;
                    background:#2563eb;border:3px solid #fff;
                    box-shadow:0 0 0 2px #3b82f6,0 0 12px rgba(37,99,235,0.5);
                    animation:pulse-blue 1.5s infinite;"></div>`,
                iconSize:   [18, 18],
                iconAnchor: [9, 9],
            }),
        }).addTo(leafletMap).bindPopup('<strong>Posisi Anda</strong>');

        updateMapDistanceInfo(gpsState.lat, gpsState.lng);

        // Fit bounds agar area + karyawan keduanya terlihat
        if (areaConfig) {
            try {
                leafletMap.fitBounds(
                    L.latLngBounds(
                        [areaConfig.latitude_pusat, areaConfig.longitude_pusat],
                        [gpsState.lat, gpsState.lng]
                    ).pad(0.3)
                );
            } catch { /* ignore */ }
        }
    } else {
        // GPS belum dapat — tampilkan info
        const infoEl = document.getElementById('map-gps-info');
        if (infoEl) infoEl.textContent = 'Lokasi GPS belum tersedia. Menunggu sinyal…';
    }

    // Paksa Leaflet resize setelah modal selesai tampil
    setTimeout(() => leafletMap?.invalidateSize(), 150);
}

/**
 * Update info jarak di dalam modal peta.
 */
function updateMapDistanceInfo(lat, lng) {
    const infoEl = document.getElementById('map-gps-info');
    if (!infoEl) return;

    if (!areaConfig) {
        infoEl.textContent = `Posisi Anda: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        return;
    }

    const jarak  = haversineDistance(lat, lng, areaConfig.latitude_pusat, areaConfig.longitude_pusat);
    const radius = areaConfig.radius_meter;
    const dlmArea = jarak <= radius;

    infoEl.innerHTML = dlmArea
        ? `<span style="color:#16a34a;font-weight:600;">✓ Dalam radius area</span>
           &nbsp;·&nbsp; Jarak: <strong>${formatDistance(jarak)}</strong>
           &nbsp;·&nbsp; Radius: ${radius} m`
        : `<span style="color:#ef4444;font-weight:600;">✕ Di luar radius area</span>
           &nbsp;·&nbsp; Jarak: <strong>${formatDistance(jarak)}</strong>
           &nbsp;·&nbsp; Radius: ${radius} m`;
}

// ══════════════════════════════════════════════════════════════════════════════
// 9. MODAL KONFIRMASI ABSENSI
// ══════════════════════════════════════════════════════════════════════════════

/** Aksi yang sedang menunggu konfirmasi: 'checkin' | 'checkout' | null */
let pendingAction = null;

/**
 * Buka modal konfirmasi dengan ringkasan lokasi + aksi.
 * @param {'checkin'|'checkout'} action
 */
function openConfirmModal(action) {
    pendingAction = action;
    const overlay   = document.getElementById('confirm-modal-overlay');
    const titleEl   = document.getElementById('confirm-modal-title');
    const subtitleEl= document.getElementById('confirm-modal-subtitle');
    const bodyEl    = document.getElementById('confirm-modal-body');
    const proceedBtn= document.getElementById('btn-proceed-absensi');

    if (!overlay) return;

    const isCheckin  = action === 'checkin';
    const actionLabel= isCheckin ? 'Check-In' : 'Check-Out';
    const now        = new Date();
    const jamNow     = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });

    if (titleEl)    titleEl.textContent    = `Konfirmasi ${actionLabel}`;
    if (subtitleEl) subtitleEl.textContent = `Waktu sekarang: ${jamNow} WIB`;

    if (proceedBtn) {
        proceedBtn.textContent = `Ya, ${actionLabel} Sekarang`;
        proceedBtn.className   = isCheckin
            ? 'k-btn k-btn--primary'
            : 'k-btn k-btn--primary k-btn--checkout';
    }

    // Susun isi modal
    let jarak = null;
    let dalamArea = false;

    if (areaConfig && gpsState.lat !== null) {
        jarak    = haversineDistance(gpsState.lat, gpsState.lng, areaConfig.latitude_pusat, areaConfig.longitude_pusat);
        dalamArea= jarak <= areaConfig.radius_meter;
    }

    const statusLokasiHtml = gpsState.lat === null
        ? `<div class="k-confirm-info-row k-confirm-info-row--warning">
               <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round"
                         d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
               </svg>
               GPS belum tersedia
           </div>`
        : dalamArea
            ? `<div class="k-confirm-info-row k-confirm-info-row--success">
                   <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round"
                             d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                   </svg>
                   Dalam radius area · Jarak ${formatDistance(jarak)}
               </div>`
            : `<div class="k-confirm-info-row k-confirm-info-row--danger">
                   <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round"
                             d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                   </svg>
                   Di luar radius area · Jarak ${formatDistance(jarak)}
               </div>`;

    const koordinatHtml = gpsState.lat !== null
        ? `<div class="k-confirm-info-row">
               <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round"
                         d="M17.657 16.657L13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/>
                   <path stroke-linecap="round" stroke-linejoin="round"
                         d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
               </svg>
               ${gpsState.lat.toFixed(6)}, ${gpsState.lng.toFixed(6)}
               ±${Math.round(gpsState.accuracy ?? 0)} m akurasi
           </div>`
        : '';

    if (bodyEl) {
        bodyEl.innerHTML = `
            <div class="k-confirm-info-list">
                ${statusLokasiHtml}
                ${koordinatHtml}
                <div class="k-confirm-info-row">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    Waktu: ${jamNow} WIB
                </div>
            </div>
            <p class="k-confirm-note">
                Validasi lokasi akhir dilakukan di server. Pastikan Anda berada di area yang benar sebelum melanjutkan.
            </p>`;
    }

    overlay.classList.add('k-modal--open');
}

function closeConfirmModal() {
    pendingAction = null;
    document.getElementById('confirm-modal-overlay')?.classList.remove('k-modal--open');
}

/** Eksekusi aksi yang sudah dikonfirmasi */
async function executePendingAbsensi() {
    closeConfirmModal();
    if (pendingAction === 'checkin') {
        await doCheckin();
    } else if (pendingAction === 'checkout') {
        await doCheckout();
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 10. HANDLER TOMBOL CHECK-IN / CHECK-OUT
//     (sekarang membuka modal konfirmasi dulu)
// ══════════════════════════════════════════════════════════════════════════════

async function handleCheckin() {
    if (!gpsState.lat || !gpsState.lng) {
        toast('Koordinat GPS belum tersedia. Tunggu sebentar dan coba lagi.', 'warning');
        return;
    }
    openConfirmModal('checkin');
}

async function handleCheckout() {
    if (!gpsState.lat || !gpsState.lng) {
        toast('Koordinat GPS belum tersedia. Tunggu sebentar dan coba lagi.', 'warning');
        return;
    }
    openConfirmModal('checkout');
}

// ══════════════════════════════════════════════════════════════════════════════
// 11. EKSEKUSI CHECK-IN / CHECK-OUT
// ══════════════════════════════════════════════════════════════════════════════

async function doCheckin() {
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

        setAbsensiState('sudah_checkin', {
            waktu_check_in: `${data.tanggal ?? ''} ${data.waktu_check_in ?? ''}`,
            menit_telat:    data.menit_telat ?? 0,
        });

        loadRiwayatMini();

    } catch (err) {
        toast(err.message, 'error');
    } finally {
        setButtonLoading(btn, false);
    }
}

async function doCheckout() {
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

        setAbsensiState('selesai', {
            waktu_check_in:     null,
            waktu_check_out:    data.waktu_check_out,
            menit_kerja_normal: data.menit_kerja_normal ?? 0,
            menit_telat:        0,
        });

        if (data.pending_lembur) {
            showPendingLemburBanner(data);
        }

        clearGpsWatch(gpsWatchId);
        gpsWatchId = null;

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
// 12. RIWAYAT MINI (5 baris terakhir)
// ══════════════════════════════════════════════════════════════════════════════

async function loadRiwayatMini() {
    const tbody = document.getElementById('tbody-riwayat-absensi-mini');
    if (!tbody) return;

    tbody.innerHTML = renderSkeleton(3, 5);

    const now   = new Date();
    const bulan = now.getMonth() + 1;
    const tahun = now.getFullYear();

    try {
        const res = await apiFetch(`/api/karyawan/riwayat?bulan=${bulan}&tahun=${tahun}&page=1`);

        if (!res.status) {
            tbody.innerHTML = `<tr><td colspan="5" class="k-empty-title"
                style="text-align:center;padding:16px;">Gagal memuat riwayat.</td></tr>`;
            return;
        }

        const rows = (res.data?.data ?? []).slice(0, 5);

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;
                color:var(--text-muted);font-size:13px;">Belum ada data absensi bulan ini.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td><span style="font-size:12px;color:var(--text-secondary);">
                    ${row.hari ?? '—'}, ${row.tanggal_absensi ?? '—'}
                </span></td>
                <td><span style="font-size:12px;color:var(--text-muted);">
                    ${_escapeHtml(row.shift?.nama_shift ?? '—')}
                </span></td>
                <td><span class="k-table-time">${formatTime(row.waktu_check_in)}</span></td>
                <td><span class="k-table-time">${formatTime(row.waktu_check_out)}</span></td>
                <td>${getBadgeHtml(row.status_kehadiran)}</td>
            </tr>`).join('');

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:16px;
            color:var(--text-muted);font-size:13px;">${_escapeHtml(err.message)}</td></tr>`;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 13. BUTTON LOADING STATE
// ══════════════════════════════════════════════════════════════════════════════

function setButtonLoading(btn, isLoading) {
    if (!btn) return;
    btn.disabled = isLoading;
    if (isLoading) btn.classList.add('k-absensi-btn--loading');
    else           btn.classList.remove('k-absensi-btn--loading');
}