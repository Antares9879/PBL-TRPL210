/**
 * resources/js/super-admin/konfigurasi-area.js
 * F19 — Konfigurasi Area GPS
 *
 * Fitur:
 *  - Tabel area GPS
 *  - Modal tambah/edit dengan Leaflet map
 *  - Klik peta untuk set koordinat otomatis
 *  - Radius circle pada peta
 *  - Enforce satu area aktif (UI warning)
 *  - Guard: area aktif tidak bisa dihapus
 */

/**
 * Catatan:
 *
 * Fix:
 *  - Hapus duplikasi event listener submit form-area yang sebelumnya
 *    terdaftar dua kali:
 *      1. di bindEvents() via: document.getElementById('form-area')?.addEventListener(...)
 *      2. di injectModalHtml() via: document.getElementById('form-area')?.addEventListener(...)
 *    Solusi: listener submit HANYA ada di bindEvents(). injectModalHtml()
 *    hanya inject HTML dan bind listener slider radius (bukan submit).
 *
 *  - Pola yang sama diperbaiki untuk tombol [data-close-modal]:
 *    bindEvents() sudah handle lewat querySelector, tidak perlu diulang
 *    di injectModalHtml().
 */

import {
    apiFetch, toast, confirmDelete,
    openModal, closeModal,
    formatDateTime,
    renderPaginasi,
} from './_utils.js';

let map         = null;
let marker      = null;
let circle      = null;
let editingId   = null;

const DEFAULT_LAT = 1.1209;
const DEFAULT_LNG = 104.0429;

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    injectLeafletCss();
    injectStyles();
    injectModalHtml();   // inject HTML dulu
    updateThead();
    bindEvents();        // bind semua listener, termasuk submit — SEKALI
    loadArea();
});

// ── Load data ─────────────────────────────────────────────────────────────────
async function loadArea() {
    showSkeleton();
    try {
        const res  = await apiFetch('/api/super-admin/konfigurasi-area');
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }
        renderTabel(json.data);
    } catch { toast('Gagal memuat data area.', 'error'); }
}

// ── Render tabel ──────────────────────────────────────────────────────────────
function renderTabel(rows) {
    const tbody = document.getElementById('tbody-area');
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">Belum ada konfigurasi area.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(a => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    ${a.is_aktif ? `<span style="width:8px;height:8px;border-radius:50%;background:#16a34a;
                        box-shadow:0 0 6px #16a34a;flex-shrink:0;display:inline-block;"></span>` : ''}
                    <span style="font-weight:${a.is_aktif ? '600' : '400'};color:#0f172a;">
                        ${escHtml(a.nama_area)}</span>
                </div>
            </td>
            <td style="font-family:'Syne',sans-serif;font-size:12px;color:#475569;">
                ${parseFloat(a.latitude_pusat).toFixed(6)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:12px;color:#475569;">
                ${parseFloat(a.longitude_pusat).toFixed(6)}</td>
            <td>
                <span style="font-family:'Syne',sans-serif;font-weight:600;color:#0f172a;">
                    ${a.radius_meter}</span>
                <span style="font-size:11px;color:#94a3b8;"> m</span>
            </td>
            <td>${badgeAktif(a.is_aktif)}</td>
            <td>
                <div style="display:flex;gap:6px;">
                    <button class="btn-aksi btn-edit" data-id="${a.id_konfigurasi}" title="Edit">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button class="btn-aksi btn-hapus ${a.is_aktif ? 'btn-hapus--disabled' : ''}"
                        data-id="${a.id_konfigurasi}" data-nama="${escHtml(a.nama_area)}"
                        data-aktif="${a.is_aktif}" title="${a.is_aktif ? 'Nonaktifkan dulu sebelum menghapus' : 'Hapus'}">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function badgeAktif(isAktif) {
    if (isAktif) return `<span class="badge badge--success">● Aktif</span>`;
    return `<span class="badge badge--neutral">Nonaktif</span>`;
}

function showSkeleton() {
    document.getElementById('tbody-area').innerHTML = `
        <tr><td colspan="6">
            <div style="display:flex;flex-direction:column;gap:10px;padding:16px 0;">
                ${Array(3).fill(`<div class="skel" style="height:10px;width:60%;border-radius:4px;"></div>`).join('')}
            </div>
        </td></tr>`;
}

// ── Event binding — SATU TEMPAT, tidak ada duplikasi ─────────────────────────
function bindEvents() {
    document.getElementById('btn-tambah-area')?.addEventListener('click', () => bukaModal(null));

    document.getElementById('tabel-area')?.addEventListener('click', async (e) => {
        const editBtn  = e.target.closest('.btn-edit');
        const hapusBtn = e.target.closest('.btn-hapus');

        if (editBtn) bukaModal(parseInt(editBtn.dataset.id));

        if (hapusBtn) {
            if (hapusBtn.dataset.aktif === 'true') {
                toast('Area yang sedang aktif tidak dapat dihapus. Nonaktifkan dulu.', 'warning');
                return;
            }
            const ok = await confirmDelete(hapusBtn.dataset.nama);
            if (!ok) return;
            await hapusArea(hapusBtn.dataset.id, hapusBtn.dataset.nama);
        }
    });

    // ── Submit form — didaftarkan SEKALI di sini ──────────────────────────────
    document.getElementById('form-area')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await simpanArea();
    });

    // Tombol tutup modal
    document.querySelector('[data-close-modal="modal-area"]')?.addEventListener('click', () => {
        closeModal('modal-area');
    });

    // Update circle saat radius berubah (range slider)
    document.getElementById('a-radius')?.addEventListener('input', updateMapCircle);
}

// ── CRUD ──────────────────────────────────────────────────────────────────────
async function bukaModal(id) {
    editingId = id;
    document.getElementById('modal-area-title').textContent = id ? 'Edit Area GPS' : 'Tambah Area GPS';
    document.getElementById('form-area').reset();
    clearFormErrors();

    let lat = DEFAULT_LAT, lng = DEFAULT_LNG, radius = 100;

    if (id) {
        try {
            const res  = await apiFetch(`/api/super-admin/konfigurasi-area/${id}`);
            const json = await res.json();
            if (!json.status) { toast(json.message, 'error'); return; }
            const a = json.data;
            setValue('a-nama',       a.nama_area);
            setValue('a-lat',        parseFloat(a.latitude_pusat).toFixed(8));
            setValue('a-lng',        parseFloat(a.longitude_pusat).toFixed(8));
            setValue('a-radius',     a.radius_meter);
            setValue('a-radius-input', a.radius_meter);
            setValue('a-aktif',      a.is_aktif ? '1' : '0');
            lat    = parseFloat(a.latitude_pusat);
            lng    = parseFloat(a.longitude_pusat);
            radius = a.radius_meter;
            // Sync display label
            const display = document.getElementById('a-radius-display');
            if (display) display.textContent = `${a.radius_meter} m`;
        } catch { toast('Gagal memuat data.', 'error'); return; }
    }

    openModal('modal-area');
    setTimeout(() => initMap(lat, lng, radius), 150);
}

async function simpanArea() {
    const btn = document.getElementById('btn-simpan-area');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const isAktif = getValue('a-aktif') === '1';

    if (isAktif && !editingId) {
        toast('Area lain yang aktif akan otomatis dinonaktifkan.', 'warning', 2500);
    }

    const body = {
        nama_area:       getValue('a-nama'),
        latitude_pusat:  parseFloat(getValue('a-lat')),
        longitude_pusat: parseFloat(getValue('a-lng')),
        radius_meter:    parseInt(getValue('a-radius-input') || getValue('a-radius')),
        is_aktif:        isAktif,
    };

    const url    = editingId ? `/api/super-admin/konfigurasi-area/${editingId}` : '/api/super-admin/konfigurasi-area';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-area');
            loadArea();
        } else {
            showFormErrors(json.data);
            toast(json.message, 'error');
        }
    } catch { toast('Gagal menyimpan.', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Simpan'; }
}

async function hapusArea(id, nama) {
    try {
        const res  = await apiFetch(`/api/super-admin/konfigurasi-area/${id}`, { method: 'DELETE' });
        const json = await res.json();
        json.status ? (toast(json.message, 'success'), loadArea())
                    : toast(json.message, 'error');
    } catch { toast('Gagal menghapus.', 'error'); }
}

// ── Leaflet Map ───────────────────────────────────────────────────────────────
function initMap(lat, lng, radius) {
    const container = document.getElementById('area-map');
    if (!container) return;

    if (map) { map.remove(); map = null; marker = null; circle = null; }

    if (!window.L) {
        setTimeout(() => initMap(lat, lng, radius), 200);
        return;
    }

    map = L.map('area-map').setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.bindPopup('Pusat Area').openPopup();

    circle = L.circle([lat, lng], {
        radius:      radius,
        color:       '#1f8a1f',
        fillColor:   '#2da82d',
        fillOpacity: 0.12,
        weight:      2,
    }).addTo(map);

    marker.on('dragend', (e) => {
        const pos = e.target.getLatLng();
        setValue('a-lat', pos.lat.toFixed(8));
        setValue('a-lng', pos.lng.toFixed(8));
        circle.setLatLng(pos);
    });

    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
        setValue('a-lat', lat.toFixed(8));
        setValue('a-lng', lng.toFixed(8));
    });
}

function updateMapCircle() {
    if (!circle || !marker) return;
    const radius = parseInt(getValue('a-radius')) || 100;
    circle.setRadius(radius);
}

// ── Inject Leaflet CSS/JS dari CDN ────────────────────────────────────────────
function injectLeafletCss() {
    if (document.getElementById('leaflet-css')) return;

    const link = document.createElement('link');
    link.id   = 'leaflet-css';
    link.rel  = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
    document.head.appendChild(link);

    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
    document.head.appendChild(script);
}

// ── Inject Modal HTML — HANYA inject HTML, TANPA listener submit/close ────────
function injectModalHtml() {
    if (document.getElementById('modal-area')) return;

    document.getElementById('btn-tambah-area')?.closest('.page-header')
        ?.insertAdjacentHTML('afterend', `
            <div style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;
                padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:13px;color:#1d4ed8;margin-bottom:0;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                Hanya satu area yang boleh aktif dalam satu waktu. Mengaktifkan area baru akan otomatis menonaktifkan area sebelumnya.
            </div>
        `);

    // Modal HTML — TANPA addEventListener di sini (sudah di bindEvents)
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-area" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:640px;">
                <div class="modal-header">
                    <h3 id="modal-area-title" class="modal-title">Tambah Area GPS</h3>
                    <button data-close-modal="modal-area" class="modal-close">×</button>
                </div>
                <form id="form-area" class="modal-body">

                    <div class="form-group">
                        <label class="form-label">Nama Area</label>
                        <input id="a-nama" type="text" class="form-input" placeholder="Area PT Ecogreen Batam Plant">
                        <span id="err-nama_area" class="form-error"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pilih Lokasi di Peta</label>
                        <p style="font-size:12px;color:#94a3b8;margin:0 0 8px;">
                            Klik pada peta atau drag marker untuk menentukan koordinat pusat area.
                        </p>
                        <div id="area-map" style="height:280px;border-radius:10px;border:1px solid #e2e8f0;
                            overflow:hidden;"></div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input id="a-lat" type="number" step="0.00000001" class="form-input"
                                placeholder="1.12345678">
                            <span id="err-latitude_pusat" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input id="a-lng" type="number" step="0.00000001" class="form-input"
                                placeholder="104.04567890">
                            <span id="err-longitude_pusat" class="form-error"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Radius (meter)</label>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <input id="a-radius" type="range" min="10" max="2000" value="100"
                                style="flex:1;accent-color:#1f8a1f;">
                            <span id="a-radius-display" style="font-family:'Syne',sans-serif;
                                font-weight:700;font-size:14px;color:#0f172a;min-width:60px;text-align:right;">
                                100 m</span>
                        </div>
                        <input id="a-radius-input" type="number" class="form-input" value="100" min="1"
                            style="margin-top:8px;" placeholder="Atau ketik langsung (meter)">
                        <span id="err-radius_meter" class="form-error"></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Area</label>
                        <select id="a-aktif" class="form-input">
                            <option value="0">Nonaktif</option>
                            <option value="1">Aktif (jadikan area absensi utama)</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-area" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-area" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    `);

    // Sinkronkan range slider dan input angka — ini bukan submit, aman di sini
    document.getElementById('a-radius')?.addEventListener('input', (e) => {
        const val = e.target.value;
        document.getElementById('a-radius-display').textContent = `${val} m`;
        document.getElementById('a-radius-input').value = val;
        updateMapCircle();
    });

    document.getElementById('a-radius-input')?.addEventListener('input', (e) => {
        const val = Math.max(1, Math.min(2000, parseInt(e.target.value) || 100));
        document.getElementById('a-radius').value = val;
        document.getElementById('a-radius-display').textContent = `${val} m`;
        updateMapCircle();
    });

    // TIDAK ada addEventListener submit atau close di sini
    // Semua sudah didaftarkan di bindEvents()
}

function updateThead() {
    const thead = document.querySelector('table.data-table thead tr');
    if (thead) thead.innerHTML = `
        <th>Nama Area</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Radius</th>
        <th>Status</th>
        <th>Aksi</th>
    `;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function getValue(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
}
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showFormErrors(errors) {
    clearFormErrors();
    if (!errors || typeof errors !== 'object') return;
    Object.entries(errors).forEach(([field, messages]) => {
        const el = document.getElementById(`err-${field}`);
        if (el) { el.textContent = Array.isArray(messages) ? messages[0] : messages; el.style.display = 'block'; }
    });
}
function clearFormErrors() {
    document.getElementById('form-area')?.querySelectorAll('[id^="err-"]')
        .forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}

function injectStyles() {
    if (document.getElementById('sa-modal-styles')) return;
    const s = document.createElement('style');
    s.id = 'sa-modal-styles';
    s.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);
            z-index:1000;align-items:center;justify-content:center;}
        .modal-overlay.modal--open{display:flex!important;}
        .modal-box{background:#fff;border-radius:16px;width:90%;max-width:520px;max-height:90vh;
            overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.15);
            animation:slideUp 0.22s cubic-bezier(0.16,1,0.3,1);}
        @keyframes slideUp{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}
        .modal-header{display:flex;align-items:center;justify-content:space-between;
            padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;}
        .modal-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:#0f172a;margin:0;}
        .modal-close{background:none;border:none;cursor:pointer;font-size:22px;color:#94a3b8;
            padding:0;line-height:1;width:28px;height:28px;display:flex;align-items:center;
            justify-content:center;border-radius:6px;}
        .modal-close:hover{background:#f1f5f9;color:#374151;}
        .modal-body{padding:20px 24px;}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding-top:20px;
            margin-top:4px;border-top:1px solid #f1f5f9;}
        .form-group{margin-bottom:16px;}
        .form-label{display:block;font-family:'DM Sans',sans-serif;font-size:11px;font-weight:600;
            color:#64748b;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;}
        .form-input{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:9px;
            font-family:'DM Sans',sans-serif;font-size:13.5px;color:#0f172a;background:#f8fafc;
            outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box;}
        .form-input:focus{border-color:#2da82d;box-shadow:0 0 0 3px rgba(45,168,45,0.12);background:#fff;}
        .form-error{display:none;font-size:12px;color:#ef4444;margin-top:4px;font-family:'DM Sans',sans-serif;}
        .btn-cancel{padding:9px 18px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;
            font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;color:#475569;cursor:pointer;}
        .btn-primary-sm{padding:9px 20px;border:none;border-radius:8px;
            background:linear-gradient(135deg,#1f8a1f,#1a6e1a);font-family:'DM Sans',sans-serif;
            font-size:13px;font-weight:600;color:#fff;cursor:pointer;}
        .btn-primary-sm:disabled{opacity:0.6;cursor:not-allowed;}
        .btn-aksi{width:30px;height:30px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;
            display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;
            transition:background .15s,color .15s;}
        .btn-edit:hover{background:#eff6ff;color:#2563eb;border-color:#bfdbfe;}
        .btn-hapus:hover{background:#fef2f2;color:#dc2626;border-color:#fecaca;}
        .btn-hapus--disabled{opacity:0.4;cursor:not-allowed!important;}
        .btn-hapus--disabled:hover{background:#fff!important;color:#64748b!important;border-color:#e2e8f0!important;}
        .skel{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
            background-size:200% 100%;animation:shimmer 1.5s ease infinite;}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .btn-primary{padding:9px 18px;border:none;border-radius:9px;
            background:linear-gradient(135deg,#1f8a1f,#1a6e1a);font-family:'DM Sans',sans-serif;
            font-size:13px;font-weight:600;color:#fff;cursor:pointer;}
    `;
    document.head.appendChild(s);
}