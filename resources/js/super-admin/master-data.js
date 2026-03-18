/**
 * resources/js/super-admin/master-data.js
 * F18 — Master Data: Perusahaan, Departemen, Shift
 *
 * Fix:
 * 1. ID tbody disesuaikan dengan Blade: tbody-perusahaan / tbody-departemen / tbody-shift
 * 2. Deteksi halaman via path segment terakhir (sudah benar, dipertahankan)
 * 3. Tabel ID selector disesuaikan per halaman
 */

import {
    apiFetch, toast, confirmDelete,
    openModal, closeModal,
    badgeStatus,
    renderPaginasi,
} from './_utils.js';

// ── Deteksi halaman via segment path terakhir ─────────────────────────────────
const pathSegments  = window.location.pathname.replace(/\/$/, '').split('/');
const lastSegment   = pathSegments[pathSegments.length - 1]; // 'perusahaan' | 'departemen' | 'shift'

const isPerusahaan  = lastSegment === 'perusahaan';
const isDepartemen  = lastSegment === 'departemen';
const isShift       = lastSegment === 'shift';

let currentPage   = 1;
let searchQuery   = '';
let filterStatus  = '';
let editingId     = null;
let debounceTimer = null;

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    injectSharedStyles();

    if (isPerusahaan) initPerusahaan();
    else if (isDepartemen) initDepartemen();
    else if (isShift)      initShift();
    else {
        // Fallback: deteksi via URL string jika path segment gagal
        const path = window.location.pathname;
        if (path.includes('/perusahaan'))      initPerusahaan();
        else if (path.includes('/departemen')) initDepartemen();
        else if (path.includes('/shift'))      initShift();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
//  PERUSAHAAN
// ═══════════════════════════════════════════════════════════════════════════════

function initPerusahaan() {
    injectToolbar('input-search', 'Cari nama perusahaan...');
    injectModalPerusahaan();
    updateThead('perusahaan');
    bindCommonEvents(loadPerusahaan, simpanPerusahaan, 'modal-perusahaan', 'form-perusahaan');
    document.getElementById('btn-tambah-perusahaan')
        ?.addEventListener('click', () => bukaModalPerusahaan(null));
    loadPerusahaan();
}

async function loadPerusahaan(page = 1) {
    currentPage = page;
    showSkeleton(5, 'tbody-perusahaan');

    const params = new URLSearchParams({ page });
    if (searchQuery)  params.set('search', searchQuery);
    if (filterStatus) params.set('status', filterStatus);

    try {
        const res  = await apiFetch(`/api/super-admin/perusahaan?${params}`);
        const json = await res.json();

        if (!json.status) {
            toast(json.message ?? 'Gagal memuat data perusahaan.', 'error');
            return;
        }

        // Handle both paginated & non-paginated response
        const rows = Array.isArray(json.data) ? json.data : (json.data?.data ?? []);
        renderPerusahaan(rows);

        if (!Array.isArray(json.data)) {
            renderPaginasi(json.data, 'paginasi-perusahaan', loadPerusahaan);
        }

    } catch (err) {
        console.error('[Perusahaan] Error:', err);
        toast('Gagal memuat data perusahaan.', 'error');
    }
}

function renderPerusahaan(rows) {
    const tbody = document.getElementById('tbody-perusahaan');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = emptyRow(6, 'Belum ada data perusahaan.');
        return;
    }

    tbody.innerHTML = rows.map(p => `
        <tr>
            <td style="font-weight:500;color:#0f172a;">${escHtml(p.nama_perusahaan)}</td>
            <td style="font-size:12px;color:#475569;">${escHtml(p.email ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${escHtml(p.no_telepon ?? '—')}</td>
            <td>
                <span style="font-size:12px;font-weight:500;color:#0f172a;">
                    ${p.karyawan_count ?? 0}
                </span>
            </td>
            <td>${badgeStatus(p.status)}</td>
            <td>${aksiButtons(p.id_perusahaan, p.nama_perusahaan)}</td>
        </tr>
    `).join('');

    bindAksiPerusahaan();
}

async function bukaModalPerusahaan(id) {
    editingId = id;
    document.getElementById('modal-perusahaan-title').textContent =
        id ? 'Edit Perusahaan' : 'Tambah Perusahaan';
    document.getElementById('form-perusahaan').reset();
    clearFormErrors('form-perusahaan');
    document.getElementById('p-status-group').style.display = id ? 'block' : 'none';

    if (id) {
        try {
            const res  = await apiFetch(`/api/super-admin/perusahaan/${id}`);
            const json = await res.json();
            if (!json.status) { toast(json.message, 'error'); return; }
            const d = json.data;
            setValue('p-nama',    d.nama_perusahaan);
            setValue('p-email',   d.email ?? '');
            setValue('p-telepon', d.no_telepon ?? '');
            setValue('p-alamat',  d.alamat ?? '');
            setValue('p-status',  d.status);
        } catch {
            toast('Gagal memuat data.', 'error');
            return;
        }
    }

    openModal('modal-perusahaan');
}

async function simpanPerusahaan() {
    const btn = document.getElementById('btn-simpan-perusahaan');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const body = {
        nama_perusahaan: getValue('p-nama'),
        email:           getValue('p-email') || null,
        no_telepon:      getValue('p-telepon') || null,
        alamat:          getValue('p-alamat') || null,
    };
    if (editingId) body.status = getValue('p-status');

    const url    = editingId ? `/api/super-admin/perusahaan/${editingId}` : '/api/super-admin/perusahaan';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-perusahaan');
            loadPerusahaan(currentPage);
        } else {
            showFormErrors('form-perusahaan', json.data);
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

function bindAksiPerusahaan() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => bukaModalPerusahaan(parseInt(btn.dataset.id)));
    });
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await confirmDelete(btn.dataset.nama);
            if (!ok) return;
            try {
                const res  = await apiFetch(`/api/super-admin/perusahaan/${btn.dataset.id}`, { method: 'DELETE' });
                const json = await res.json();
                json.status
                    ? (toast(json.message, 'success'), loadPerusahaan(currentPage))
                    : toast(json.message, 'error');
            } catch { toast('Gagal menghapus.', 'error'); }
        });
    });
}

function injectModalPerusahaan() {
    if (document.getElementById('modal-perusahaan')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-perusahaan" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 id="modal-perusahaan-title" class="modal-title">Tambah Perusahaan</h3>
                    <button id="close-perusahaan" class="modal-close">×</button>
                </div>
                <form id="form-perusahaan" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Perusahaan</label>
                        <input id="p-nama" type="text" class="form-input" placeholder="PT Contoh Jaya">
                        <span id="err-nama_perusahaan" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input id="p-email" type="email" class="form-input" placeholder="email@perusahaan.com">
                        <span id="err-email" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input id="p-telepon" type="text" class="form-input" placeholder="08xxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea id="p-alamat" class="form-input" rows="3"
                            placeholder="Alamat lengkap perusahaan"></textarea>
                    </div>
                    <div id="p-status-group" class="form-group" style="display:none;">
                        <label class="form-label">Status</label>
                        <select id="p-status" class="form-input">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancel-perusahaan" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-perusahaan" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    `);
    document.getElementById('close-perusahaan')
        ?.addEventListener('click', () => closeModal('modal-perusahaan'));
    document.getElementById('cancel-perusahaan')
        ?.addEventListener('click', () => closeModal('modal-perusahaan'));
}

// ═══════════════════════════════════════════════════════════════════════════════
//  DEPARTEMEN
// ═══════════════════════════════════════════════════════════════════════════════

function initDepartemen() {
    injectToolbar('input-search', 'Cari nama atau kode...');
    injectModalDepartemen();
    updateThead('departemen');
    bindCommonEvents(loadDepartemen, simpanDepartemen, 'modal-departemen', 'form-departemen');
    document.getElementById('btn-tambah-departemen')
        ?.addEventListener('click', () => bukaModalDepartemen(null));
    loadDepartemen();
}

async function loadDepartemen(page = 1) {
    currentPage = page;
    showSkeleton(4, 'tbody-departemen');

    const params = new URLSearchParams({ page });
    if (searchQuery)  params.set('search', searchQuery);
    if (filterStatus) params.set('status', filterStatus);

    try {
        const res  = await apiFetch(`/api/super-admin/departemen?${params}`);
        const json = await res.json();

        if (!json.status) {
            toast(json.message ?? 'Gagal memuat data departemen.', 'error');
            return;
        }

        const rows = Array.isArray(json.data) ? json.data : (json.data?.data ?? []);
        renderDepartemen(rows);

        if (!Array.isArray(json.data)) {
            renderPaginasi(json.data, 'paginasi-departemen', loadDepartemen);
        }

    } catch (err) {
        console.error('[Departemen] Error:', err);
        toast('Gagal memuat data departemen.', 'error');
    }
}

function renderDepartemen(rows) {
    const tbody = document.getElementById('tbody-departemen');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = emptyRow(4, 'Belum ada data departemen.');
        return;
    }

    tbody.innerHTML = rows.map(d => `
        <tr>
            <td>
                <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;
                    padding:3px 10px;background:#f0faf0;color:#1a6e1a;border-radius:6px;">
                    ${escHtml(d.kode_departemen)}
                </span>
            </td>
            <td style="font-weight:500;color:#0f172a;">${escHtml(d.nama_departemen)}</td>
            <td>${badgeStatus(d.status)}</td>
            <td>${aksiButtons(d.id_departemen, d.nama_departemen)}</td>
        </tr>
    `).join('');

    bindAksiDepartemen();
}

async function bukaModalDepartemen(id) {
    editingId = id;
    document.getElementById('modal-departemen-title').textContent =
        id ? 'Edit Departemen' : 'Tambah Departemen';
    document.getElementById('form-departemen').reset();
    clearFormErrors('form-departemen');
    document.getElementById('d-status-group').style.display = id ? 'block' : 'none';

    if (id) {
        try {
            const res  = await apiFetch(`/api/super-admin/departemen/${id}`);
            const json = await res.json();
            if (!json.status) { toast(json.message, 'error'); return; }
            setValue('d-nama',   json.data.nama_departemen);
            setValue('d-kode',   json.data.kode_departemen);
            setValue('d-status', json.data.status);
        } catch {
            toast('Gagal memuat data.', 'error');
            return;
        }
    }

    openModal('modal-departemen');
}

async function simpanDepartemen() {
    const btn = document.getElementById('btn-simpan-departemen');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const body = {
        nama_departemen: getValue('d-nama'),
        kode_departemen: getValue('d-kode').toUpperCase(),
    };
    if (editingId) body.status = getValue('d-status');

    const url    = editingId ? `/api/super-admin/departemen/${editingId}` : '/api/super-admin/departemen';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-departemen');
            loadDepartemen(currentPage);
        } else {
            showFormErrors('form-departemen', json.data);
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

function bindAksiDepartemen() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => bukaModalDepartemen(parseInt(btn.dataset.id)));
    });
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await confirmDelete(btn.dataset.nama);
            if (!ok) return;
            try {
                const res  = await apiFetch(`/api/super-admin/departemen/${btn.dataset.id}`, { method: 'DELETE' });
                const json = await res.json();
                json.status
                    ? (toast(json.message, 'success'), loadDepartemen(currentPage))
                    : toast(json.message, 'error');
            } catch { toast('Gagal menghapus.', 'error'); }
        });
    });
}

function injectModalDepartemen() {
    if (document.getElementById('modal-departemen')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-departemen" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:440px;">
                <div class="modal-header">
                    <h3 id="modal-departemen-title" class="modal-title">Tambah Departemen</h3>
                    <button id="close-departemen" class="modal-close">×</button>
                </div>
                <form id="form-departemen" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Departemen</label>
                        <input id="d-nama" type="text" class="form-input" placeholder="Produksi">
                        <span id="err-nama_departemen" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Departemen</label>
                        <input id="d-kode" type="text" class="form-input" placeholder="PROD"
                            style="text-transform:uppercase;">
                        <span id="err-kode_departemen" class="form-error"></span>
                    </div>
                    <div id="d-status-group" class="form-group" style="display:none;">
                        <label class="form-label">Status</label>
                        <select id="d-status" class="form-input">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancel-departemen" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-departemen" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    `);
    document.getElementById('close-departemen')
        ?.addEventListener('click', () => closeModal('modal-departemen'));
    document.getElementById('cancel-departemen')
        ?.addEventListener('click', () => closeModal('modal-departemen'));
}

// ═══════════════════════════════════════════════════════════════════════════════
//  SHIFT
// ═══════════════════════════════════════════════════════════════════════════════

function initShift() {
    injectModalShift();
    updateThead('shift');
    document.getElementById('btn-tambah-shift')
        ?.addEventListener('click', () => bukaModalShift(null));
    document.getElementById('form-shift')
        ?.addEventListener('submit', async (e) => { e.preventDefault(); await simpanShift(); });
    document.getElementById('close-shift')
        ?.addEventListener('click', () => closeModal('modal-shift'));
    document.getElementById('cancel-shift')
        ?.addEventListener('click', () => closeModal('modal-shift'));
    loadShift();
}

async function loadShift() {
    showSkeleton(6, 'tbody-shift');
    try {
        const res  = await apiFetch('/api/super-admin/shift');
        const json = await res.json();

        if (!json.status) {
            toast(json.message ?? 'Gagal memuat data shift.', 'error');
            return;
        }

        // ShiftApiController mengembalikan array langsung (tanpa paginate)
        const rows = Array.isArray(json.data) ? json.data : (json.data?.data ?? []);
        renderShift(rows);

    } catch (err) {
        console.error('[Shift] Error:', err);
        toast('Gagal memuat data shift.', 'error');
    }
}

function renderShift(rows) {
    const tbody = document.getElementById('tbody-shift');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = emptyRow(6, 'Belum ada data shift.');
        return;
    }

    tbody.innerHTML = rows.map(s => `
        <tr>
            <td style="font-weight:500;color:#0f172a;">${escHtml(s.nama_shift)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${s.jam_masuk.slice(0,5)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${s.jam_pulang.slice(0,5)}</td>
            <td style="font-size:12px;color:#475569;">${s.durasi_normal_menit} menit</td>
            <td>${badgeStatus(s.status)}</td>
            <td>${aksiButtons(s.id_shift, s.nama_shift)}</td>
        </tr>
    `).join('');

    bindAksiShift();
}

async function bukaModalShift(id) {
    editingId = id;
    document.getElementById('modal-shift-title').textContent =
        id ? 'Edit Shift' : 'Tambah Shift';
    document.getElementById('form-shift').reset();
    clearFormErrors('form-shift');
    document.getElementById('s-status-group').style.display = id ? 'block' : 'none';

    // Set default durasi
    setValue('s-durasi', '480');

    if (id) {
        try {
            const res  = await apiFetch(`/api/super-admin/shift/${id}`);
            const json = await res.json();
            if (!json.status) { toast(json.message, 'error'); return; }
            const s = json.data;
            setValue('s-nama',   s.nama_shift);
            setValue('s-masuk',  s.jam_masuk.slice(0, 5));
            setValue('s-pulang', s.jam_pulang.slice(0, 5));
            setValue('s-durasi', s.durasi_normal_menit);
            setValue('s-status', s.status);
        } catch {
            toast('Gagal memuat data.', 'error');
            return;
        }
    }

    openModal('modal-shift');
}

async function simpanShift() {
    const btn = document.getElementById('btn-simpan-shift');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const body = {
        nama_shift:          getValue('s-nama'),
        jam_masuk:           getValue('s-masuk'),
        jam_pulang:          getValue('s-pulang'),
        durasi_normal_menit: parseInt(getValue('s-durasi')) || 480,
    };
    if (editingId) body.status = getValue('s-status');

    const url    = editingId ? `/api/super-admin/shift/${editingId}` : '/api/super-admin/shift';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-shift');
            loadShift();
        } else {
            showFormErrors('form-shift', json.data);
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

function bindAksiShift() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => bukaModalShift(parseInt(btn.dataset.id)));
    });
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await confirmDelete(btn.dataset.nama);
            if (!ok) return;
            try {
                const res  = await apiFetch(`/api/super-admin/shift/${btn.dataset.id}`, { method: 'DELETE' });
                const json = await res.json();
                json.status
                    ? (toast(json.message, 'success'), loadShift())
                    : toast(json.message, 'error');
            } catch { toast('Gagal menghapus.', 'error'); }
        });
    });
}

function injectModalShift() {
    if (document.getElementById('modal-shift')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-shift" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:440px;">
                <div class="modal-header">
                    <h3 id="modal-shift-title" class="modal-title">Tambah Shift</h3>
                    <button id="close-shift" class="modal-close">×</button>
                </div>
                <form id="form-shift" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Shift</label>
                        <input id="s-nama" type="text" class="form-input" placeholder="Shift Pagi">
                        <span id="err-nama_shift" class="form-error"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label">Jam Masuk</label>
                            <input id="s-masuk" type="time" class="form-input">
                            <span id="err-jam_masuk" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Pulang</label>
                            <input id="s-pulang" type="time" class="form-input">
                            <span id="err-jam_pulang" class="form-error"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durasi Normal (menit)</label>
                        <input id="s-durasi" type="number" class="form-input"
                            value="480" min="1" max="1440">
                        <span id="err-durasi_normal_menit" class="form-error"></span>
                    </div>
                    <div id="s-status-group" class="form-group" style="display:none;">
                        <label class="form-label">Status</label>
                        <select id="s-status" class="form-input">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="cancel-shift" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-shift" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    `);
}

// ═══════════════════════════════════════════════════════════════════════════════
//  SHARED HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

function bindCommonEvents(loadFn, simpanFn, modalId, formId) {
    document.getElementById('input-search')?.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchQuery = e.target.value.trim();
            loadFn(1);
        }, 400);
    });

    document.getElementById('filter-status')?.addEventListener('change', (e) => {
        filterStatus = e.target.value;
        loadFn(1);
    });

    document.getElementById(formId)?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await simpanFn();
    });

    const modalName = modalId.replace('modal-', '');
    document.getElementById(`close-${modalName}`)
        ?.addEventListener('click', () => closeModal(modalId));
    document.getElementById(`cancel-${modalName}`)
        ?.addEventListener('click', () => closeModal(modalId));
}

function updateThead(type) {
    const thead = document.querySelector('table.data-table thead tr');
    if (!thead) return;

    const headers = {
        perusahaan: `<th>Nama Perusahaan</th><th>Email</th><th>No. Telepon</th>
                     <th>Karyawan</th><th>Status</th><th>Aksi</th>`,
        departemen: `<th>Kode</th><th>Nama Departemen</th><th>Status</th><th>Aksi</th>`,
        shift:      `<th>Nama Shift</th><th>Jam Masuk</th><th>Jam Pulang</th>
                     <th>Durasi</th><th>Status</th><th>Aksi</th>`,
    };

    thead.innerHTML = headers[type] ?? '';
}

function injectToolbar(searchId, placeholder) {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById(searchId)) return;

    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:16px;';
    toolbar.innerHTML = `
        <input id="${searchId}" type="text" placeholder="${placeholder}"
            style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;
                   font-family:'DM Sans',sans-serif;outline:none;width:240px;">
        <select id="filter-status"
            style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;
                   font-family:'DM Sans',sans-serif;outline:none;color:#374151;">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
    `;
    header.after(toolbar);
}

function aksiButtons(id, nama) {
    return `
        <div style="display:flex;gap:6px;">
            <button class="btn-aksi btn-edit"
                data-id="${id}" data-nama="${escHtml(nama)}" title="Edit">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5
                           m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            <button class="btn-aksi btn-hapus"
                data-id="${id}" data-nama="${escHtml(nama)}" title="Hapus">
                <svg width="14" height="14" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858
                           L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    `;
}

/**
 * @param {number} cols   — jumlah kolom tabel
 * @param {string} tbodyId — ID elemen tbody yang akan diisi skeleton
 */
function showSkeleton(cols, tbodyId) {
    const el = document.getElementById(tbodyId);
    if (!el) return;
    el.innerHTML = `
        <tr><td colspan="${cols}">
            <div style="display:flex;flex-direction:column;gap:10px;padding:16px 0;">
                ${Array(4).fill(`
                    <div class="skel" style="height:10px;width:70%;border-radius:4px;"></div>
                `).join('')}
            </div>
        </td></tr>
    `;
}

function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}"
        style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
        ${msg}</td></tr>`;
}

function showFormErrors(formId, errors) {
    clearFormErrors(formId);
    if (!errors || typeof errors !== 'object') return;
    Object.entries(errors).forEach(([field, messages]) => {
        const el = document.getElementById(`err-${field}`);
        if (el) {
            el.textContent = Array.isArray(messages) ? messages[0] : messages;
            el.style.display = 'block';
        }
    });
}

function clearFormErrors(formId) {
    document.getElementById(formId)?.querySelectorAll('[id^="err-"]')
        .forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}

function getValue(id)      { return document.getElementById(id)?.value ?? ''; }
function setValue(id, val) { const el = document.getElementById(id); if (el) el.value = val; }

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function injectSharedStyles() {
    if (document.getElementById('sa-modal-styles')) return;
    const s = document.createElement('style');
    s.id = 'sa-modal-styles';
    s.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);
            backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center;}
        .modal-overlay.modal--open{display:flex!important;}
        .modal-box{background:#fff;border-radius:16px;width:90%;max-width:520px;max-height:90vh;
            overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.15);
            animation:slideUp 0.22s cubic-bezier(0.16,1,0.3,1);}
        @keyframes slideUp{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}
        .modal-header{display:flex;align-items:center;justify-content:space-between;
            padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;}
        .modal-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;
            color:#0f172a;margin:0;}
        .modal-close{background:none;border:none;cursor:pointer;font-size:22px;color:#94a3b8;
            padding:0;width:28px;height:28px;display:flex;align-items:center;
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
        .form-input:focus{border-color:#2da82d;
            box-shadow:0 0 0 3px rgba(45,168,45,0.12);background:#fff;}
        .form-error{display:none;font-size:12px;color:#ef4444;margin-top:4px;
            font-family:'DM Sans',sans-serif;}
        .btn-cancel{padding:9px 18px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;
            font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;
            color:#475569;cursor:pointer;}
        .btn-cancel:hover{background:#f8fafc;}
        .btn-primary-sm{padding:9px 20px;border:none;border-radius:8px;
            background:linear-gradient(135deg,#1f8a1f,#1a6e1a);
            font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;
            color:#fff;cursor:pointer;}
        .btn-primary-sm:disabled{opacity:0.6;cursor:not-allowed;}
        .btn-aksi{width:30px;height:30px;border-radius:7px;border:1px solid #e2e8f0;
            background:#fff;display:inline-flex;align-items:center;justify-content:center;
            cursor:pointer;color:#64748b;transition:background .15s,color .15s;}
        .btn-edit:hover{background:#eff6ff;color:#2563eb;border-color:#bfdbfe;}
        .btn-hapus:hover{background:#fef2f2;color:#dc2626;border-color:#fecaca;}
        .skel{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
            background-size:200% 100%;animation:shimmer 1.5s ease infinite;}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .btn-primary{padding:9px 18px;border:none;border-radius:9px;
            background:linear-gradient(135deg,#1f8a1f,#1a6e1a);
            font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;
            color:#fff;cursor:pointer;}
    `;
    document.head.appendChild(s);
}