/**
 * resources/js/karyawan/_utils.js
 *
 * Shared utilities untuk semua halaman karyawan.
 * Pola: stateful SPA Sanctum (session cookie), CSRF via meta tag.
 *
 * Exports:
 *   - apiFetch(url, options)          → fetch wrapper dengan error handling
 *   - toast(message, type, duration)  → in-app toast notification
 *   - formatTime(datetime)            → "07:30"
 *   - formatDate(date)                → "Senin, 24 Mar 2025"
 *   - formatDateShort(date)           → "24 Mar"
 *   - formatMinutes(menit)            → "2j 30m" atau "30 mnt"
 *   - getBadgeHtml(status, type)      → badge HTML string
 *   - renderPagination(meta, cb)      → render tombol prev/next
 *   - renderSkeleton(rows, cols)      → skeleton table rows HTML
 *   - watchGps(callbacks)             → wrapper navigator.geolocation.watchPosition
 *   - clearGpsWatch(watchId)          → clear watchPosition
 *   - haversineDistance(lat1,lng1,lat2,lng2) → jarak meter antara dua koordinat
 */

'use strict';

// ══════════════════════════════════════════════════════════════════════════════
// 1. CSRF TOKEN
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Ambil CSRF token dari meta tag.
 * @returns {string}
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ══════════════════════════════════════════════════════════════════════════════
// 2. API FETCH WRAPPER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Wrapper fetch untuk API endpoint Laravel Sanctum (stateful SPA).
 * Selalu kirim CSRF token, credentials: 'same-origin'.
 * Response format: { status: bool, message: string, data: any }
 *
 * @param {string} url       - Relative URL, contoh: '/api/karyawan/check-in'
 * @param {object} [options] - Fetch options tambahan
 * @param {string} [options.method]  - HTTP method (default: 'GET')
 * @param {object|FormData} [options.body] - Request body
 * @param {object} [options.headers] - Header tambahan
 * @returns {Promise<{status: boolean, message: string, data: any}>}
 * @throws {Error} jika network error atau response tidak bisa di-parse
 */
async function apiFetch(url, options = {}) {
    const { method = 'GET', body = null, headers = {} } = options;

    const isFormData = body instanceof FormData;

    const requestHeaders = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        ...headers,
    };

    // Jangan set Content-Type untuk FormData — biarkan browser set boundary
    if (!isFormData && body !== null) {
        requestHeaders['Content-Type'] = 'application/json';
    }

    const fetchOptions = {
        method,
        headers: requestHeaders,
        credentials: 'same-origin',
    };

    if (body !== null) {
        fetchOptions.body = isFormData ? body : JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(url, fetchOptions);
    } catch (networkError) {
        throw new Error('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
    }

    // Handle 401 — session expired
    if (response.status === 401) {
        toast('Sesi Anda telah berakhir. Silakan login kembali.', 'error', 5000);
        setTimeout(() => { window.location.href = '/login'; }, 2000);
        throw new Error('Unauthenticated');
    }

    let json;
    try {
        json = await response.json();
    } catch {
        throw new Error(`Server mengembalikan respons tidak valid (${response.status}).`);
    }

    return json;
}

// ══════════════════════════════════════════════════════════════════════════════
// 3. TOAST NOTIFICATION
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Tampilkan toast notification.
 * Menggunakan .k-toast-container dan .k-toast dari karyawan.css.
 *
 * @param {string} message           - Pesan yang ditampilkan
 * @param {'success'|'error'|'warning'|'info'} [type='info'] - Tipe toast
 * @param {number} [duration=3500]   - Durasi tampil dalam ms
 */
function toast(message, type = 'info', duration = 3500) {
    const container = _getOrCreateToastContainer();

    const icons = {
        success: '✓',
        error:   '✕',
        warning: '!',
        info:    'i',
    };

    const el = document.createElement('div');
    el.className = `k-toast k-toast--${type}`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML = `
        <span class="k-toast-icon">${icons[type] ?? 'i'}</span>
        <span class="k-toast-msg">${_escapeHtml(message)}</span>
        <button class="k-toast-close" aria-label="Tutup notifikasi">&times;</button>
    `;

    container.appendChild(el);

    // Trigger CSS transition
    requestAnimationFrame(() => {
        requestAnimationFrame(() => el.classList.add('k-toast--visible'));
    });

    const dismiss = () => {
        el.classList.remove('k-toast--visible');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
    };

    const timer = setTimeout(dismiss, duration);
    el.querySelector('.k-toast-close').addEventListener('click', () => {
        clearTimeout(timer);
        dismiss();
    });
}

/** @private */
function _getOrCreateToastContainer() {
    let container = document.getElementById('k-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'k-toast-container';
        container.className = 'k-toast-container';
        container.setAttribute('aria-label', 'Notifikasi');
        document.body.appendChild(container);
    }
    return container;
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. FORMAT HELPERS
// ══════════════════════════════════════════════════════════════════════════════

const BULAN_ID = [
    'Januari','Februari','Maret','April','Mei','Juni',
    'Juli','Agustus','September','Oktober','November','Desember',
];

const BULAN_SHORT = [
    'Jan','Feb','Mar','Apr','Mei','Jun',
    'Jul','Ags','Sep','Okt','Nov','Des',
];

const HARI_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

/**
 * Format datetime ke string jam:menit — "07:30"
 * @param {string|null} datetime
 * @returns {string}
 */
function formatTime(datetime) {
    if (!datetime) return '—';
    const d = new Date(datetime);
    if (isNaN(d)) return datetime;
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
}

/**
 * Format date ke "Senin, 24 Mar 2025"
 * @param {string|null} date
 * @returns {string}
 */
function formatDate(date) {
    if (!date) return '—';
    const d = new Date(date + (date.length === 10 ? 'T00:00:00' : ''));
    if (isNaN(d)) return date;
    return `${HARI_ID[d.getDay()]}, ${d.getDate()} ${BULAN_SHORT[d.getMonth()]} ${d.getFullYear()}`;
}

/**
 * Format date ke "24 Mar"
 * @param {string|null} date
 * @returns {string}
 */
function formatDateShort(date) {
    if (!date) return '—';
    const d = new Date(date + (date.length === 10 ? 'T00:00:00' : ''));
    if (isNaN(d)) return date;
    return `${d.getDate()} ${BULAN_SHORT[d.getMonth()]}`;
}

/**
 * Format menit ke "2j 30m" (≥60) atau "30 mnt" (<60)
 * @param {number|null} menit
 * @returns {string}
 */
function formatMinutes(menit) {
    if (menit === null || menit === undefined || menit === '') return '—';
    const m = parseInt(menit, 10);
    if (isNaN(m)) return '—';
    if (m === 0) return '0 mnt';
    if (m < 60) return `${m} mnt`;
    const jam = Math.floor(m / 60);
    const sisa = m % 60;
    return sisa > 0 ? `${jam}j ${sisa}m` : `${jam}j`;
}

/**
 * Nama bulan Bahasa Indonesia dari nomor (1-12)
 * @param {number} num
 * @returns {string}
 */
function getBulanNama(num) {
    return BULAN_ID[num - 1] ?? '?';
}

/**
 * Escape HTML untuk output aman ke innerHTML
 * @param {string} str
 * @returns {string}
 */
function _escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. BADGE HTML
// ══════════════════════════════════════════════════════════════════════════════

const BADGE_MAP = {
    // status_kehadiran
    hadir:    { cls: 'k-badge--hadir',    label: 'Hadir' },
    izin:     { cls: 'k-badge--izin',     label: 'Izin' },
    alpa:     { cls: 'k-badge--alpa',     label: 'Alpa' },
    pending:  { cls: 'k-badge--pending',  label: 'Pending' },
    // status_validasi
    menunggu:  { cls: 'k-badge--pending',  label: 'Menunggu' },
    disetujui: { cls: 'k-badge--approved', label: 'Disetujui' },
    ditolak:   { cls: 'k-badge--rejected', label: 'Ditolak' },
    kadaluarsa:{ cls: 'k-badge--expired',  label: 'Kadaluarsa' },
    // shift
    pagi:   { cls: 'k-shift-pill--pagi',   label: 'Pagi' },
    siang:  { cls: 'k-shift-pill--siang',  label: 'Siang' },
    malam:  { cls: 'k-shift-pill--malam',  label: 'Malam' },
    normal: { cls: 'k-shift-pill--normal', label: 'Normal' },
};

/**
 * Generate HTML badge berdasarkan status
 * @param {string} status
 * @param {'kehadiran'|'validasi'|'shift'} [type='kehadiran']
 * @returns {string} HTML string
 */
function getBadgeHtml(status, type = 'kehadiran') {
    const key = (status ?? '').toLowerCase();
    const def = BADGE_MAP[key];
    if (!def) return `<span class="k-badge k-badge--pending">${_escapeHtml(status ?? '—')}</span>`;
    const baseClass = type === 'shift' ? 'k-shift-pill' : 'k-badge';
    return `<span class="${baseClass} ${def.cls}">${def.label}</span>`;
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. PAGINATION RENDERER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render tombol paginasi ke dalam container element.
 *
 * @param {HTMLElement} container        - Element target untuk render pagination
 * @param {{current_page:number, last_page:number, total:number, per_page:number}} meta
 * @param {function(number):void} onPageChange - Callback saat halaman berubah
 */
function renderPagination(container, meta, onPageChange) {
    if (!container) return;

    const { current_page: cur, last_page: last, total, per_page: perPage } = meta;

    if (last <= 1) {
        container.innerHTML = '';
        return;
    }

    const dari = (cur - 1) * perPage + 1;
    const sampai = Math.min(cur * perPage, total);

    container.innerHTML = `
        <div class="k-pagination">
            <span>${dari}–${sampai} dari ${total} data</span>
            <div class="k-pagination-btns">
                <button class="k-pagination-btn"
                        id="pag-prev"
                        ${cur <= 1 ? 'disabled' : ''}
                        aria-label="Halaman sebelumnya">
                    &laquo; Prev
                </button>
                <button class="k-pagination-btn"
                        id="pag-next"
                        ${cur >= last ? 'disabled' : ''}
                        aria-label="Halaman berikutnya">
                    Next &raquo;
                </button>
            </div>
        </div>
    `;

    container.querySelector('#pag-prev')?.addEventListener('click', () => onPageChange(cur - 1));
    container.querySelector('#pag-next')?.addEventListener('click', () => onPageChange(cur + 1));
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. SKELETON HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Generate skeleton table rows HTML
 * @param {number} rows  - Jumlah baris skeleton
 * @param {number} cols  - Jumlah kolom per baris
 * @returns {string} HTML string berisi <tr> skeleton
 */
function renderSkeleton(rows = 5, cols = 5) {
    const widths = [80, 60, 50, 50, 65]; // variasi lebar agar tidak monoton
    let html = '';
    for (let r = 0; r < rows; r++) {
        html += '<tr>';
        for (let c = 0; c < cols; c++) {
            const w = widths[c % widths.length];
            html += `<td><div class="k-skel k-skel--text" style="width:${w}px;"></div></td>`;
        }
        html += '</tr>';
    }
    return html;
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. GPS HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/** Radius bumi dalam meter (Haversine) */
const EARTH_RADIUS_METER = 6_371_000;

/**
 * Hitung jarak antara dua koordinat menggunakan Haversine formula.
 * Menggunakan logika yang sama dengan GpsValidationService.php di backend.
 *
 * @param {number} lat1
 * @param {number} lng1
 * @param {number} lat2
 * @param {number} lng2
 * @returns {number} Jarak dalam meter
 */
function haversineDistance(lat1, lng1, lat2, lng2) {
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return EARTH_RADIUS_METER * c;
}

/**
 * Format jarak meter ke string yang mudah dibaca.
 * @param {number} meter
 * @returns {string}
 */
function formatDistance(meter) {
    if (meter < 1000) return `${Math.round(meter)} m`;
    return `${(meter / 1000).toFixed(1)} km`;
}

/**
 * Wrapper watchPosition dengan error handling terpusat.
 * Mengembalikan watchId yang bisa di-clear dengan clearGpsWatch().
 *
 * @param {{
 *   onUpdate: function({lat:number, lng:number, accuracy:number}):void,
 *   onError:  function(string):void,
 * }} callbacks
 * @returns {number|null} watchId atau null jika GPS tidak tersedia
 */
function watchGps({ onUpdate, onError }) {
    if (!navigator.geolocation) {
        onError('Perangkat ini tidak mendukung GPS. Tidak dapat melakukan absensi.');
        return null;
    }

    const watchId = navigator.geolocation.watchPosition(
        (position) => {
            onUpdate({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
            });
        },
        (error) => {
            const messages = {
                1: 'Izin GPS ditolak. Aktifkan izin lokasi di pengaturan browser untuk melanjutkan absensi.',
                2: 'Posisi GPS tidak dapat ditentukan. Pastikan Anda berada di luar ruangan atau area dengan sinyal GPS baik.',
                3: 'Waktu permintaan GPS habis. Coba lagi.',
            };
            onError(messages[error.code] ?? `GPS error: ${error.message}`);
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 5000,
        },
    );

    return watchId;
}

/**
 * Clear watchPosition.
 * @param {number|null} watchId
 */
function clearGpsWatch(watchId) {
    if (watchId !== null && watchId !== undefined) {
        navigator.geolocation.clearWatch(watchId);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 9. DEBOUNCE
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Debounce function.
 * @param {function} fn
 * @param {number} delay
 * @returns {function}
 */
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

// ══════════════════════════════════════════════════════════════════════════════
// 10. EXPORTS
// ══════════════════════════════════════════════════════════════════════════════

export {
    apiFetch,
    toast,
    formatTime,
    formatDate,
    formatDateShort,
    formatMinutes,
    formatDistance,
    getBulanNama,
    getBadgeHtml,
    renderPagination,
    renderSkeleton,
    watchGps,
    clearGpsWatch,
    haversineDistance,
    debounce,
    _escapeHtml,
};