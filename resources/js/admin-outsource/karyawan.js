/**
 * resources/js/admin-outsource/karyawan.js
 * F07 — Manajemen Karyawan Admin Outsource
 *
 * Fitur:
 *   - Tabel karyawan dengan paginasi + search + filter status/departemen
 *   - Modal tambah (buat akun + profil sekaligus)
 *   - Modal edit
 *   - Aktifkan / Nonaktifkan akun
 *   - Reset password
 *
 * Endpoint:
 *   GET    /api/admin/karyawan
 *   POST   /api/admin/karyawan
 *   GET    /api/admin/karyawan/{id}
 *   PUT    /api/admin/karyawan/{id}
 *   DELETE /api/admin/karyawan/{id}          → nonaktifkan
 *   PUT    /api/admin/karyawan/{id}/aktifkan
 *   PUT    /api/admin/karyawan/{id}/reset-password
 */

import {
    apiFetch, esc, fmtTanggal, toast, confirmDelete,
    openModal, closeModal, badgeStatus, renderPaginasi,
    injectModalStyles,
} from './_utils.js';

let currentPage   = 1;
let searchQuery   = '';
let filterStatus  = '';
let filterDept    = '';
let editingId     = null;
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();
    injectToolbar();
    injectModals();
    updateThead();
    bindEvents();
    loadDepartemenDropdown();
    loadKaryawan();
});

// ── Load data ─────────────────────────────────────────────────────────────────
async function loadKaryawan(page = 1) {
    currentPage = page;
    showSkeleton();

    const params = new URLSearchParams({ page });
    if (searchQuery)   params.set('search',        searchQuery);
    if (filterStatus)  params.set('status',         filterStatus);
    if (filterDept)    params.set('id_departemen',  filterDept);

    try {
        const res  = await apiFetch(`/api/admin/karyawan?${params}`);
        const json = await res.json();

        if (!json.status) {
            toast(json.message, 'error');
            showEmpty('Gagal memuat data karyawan.');
            return;
        }

        renderTabel(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-karyawan', loadKaryawan);

    } catch (err) {
        console.error('[Karyawan] Load error:', err);
        toast('Gagal terhubung ke server.', 'error');
    }
}

// ── Render ────────────────────────────────────────────────────────────────────
function renderTabel(rows) {
    const tbody = document.getElementById('tbody-karyawan');
    if (!tbody) return;

    if (!rows.length) {
        showEmpty('Belum ada data karyawan.');
        return;
    }

    tbody.innerHTML = rows.map(k => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="karyawan-avatar" style="
                        width:34px;height:34px;border-radius:9px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#87dc87;">
                        ${esc(k.nama_lengkap.charAt(0).toUpperCase())}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(k.nama_lengkap)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(k.akun?.email ?? '—')}</div>
                    </div>
                </div>
            </td>
            <td style="font-family:'Syne',sans-serif;font-size:12px;color:#475569;">${esc(k.nik ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${esc(k.posisi ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${esc(k.departemen?.nama_departemen ?? '—')}</td>
            <td style="font-size:12px;color:#64748b;">${fmtTanggal(k.tanggal_bergabung)}</td>
            <td>${badgeStatus(k.status)}</td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <button class="btn-aksi btn-edit" data-id="${k.id_karyawan}" title="Edit">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5
                                m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    ${k.status === 'aktif'
                        ? `<button class="btn-aksi btn-hapus" data-id="${k.id_karyawan}" data-nama="${esc(k.nama_lengkap)}" title="Nonaktifkan">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                           </button>`
                        : `<button class="btn-aksi btn-view btn-aktifkan" data-id="${k.id_karyawan}" data-nama="${esc(k.nama_lengkap)}" title="Aktifkan kembali">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                </svg>
                           </button>`
                    }
                    <button class="btn-aksi btn-reset" data-id="${k.id_karyawan}" data-nama="${esc(k.nama_lengkap)}" title="Reset Password">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.743 5.743L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.586a1 1 0 0 1 .293-.707l5.964-5.964A6 6 0 1 1 21 9z"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showSkeleton() {
    const tbody = document.getElementById('tbody-karyawan');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`
        <tr>
            <td><div style="display:flex;gap:10px;align-items:center;">
                <div class="skel" style="width:34px;height:34px;border-radius:9px;flex-shrink:0;"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                    <div class="skel" style="height:10px;width:130px;border-radius:4px;"></div>
                    <div class="skel" style="height:8px;width:100px;border-radius:4px;"></div>
                </div>
            </div></td>
            <td><div class="skel" style="height:10px;width:80px;border-radius:4px;"></div></td>
            <td><div class="skel" style="height:10px;width:100px;border-radius:4px;"></div></td>
            <td><div class="skel" style="height:10px;width:90px;border-radius:4px;"></div></td>
            <td><div class="skel" style="height:10px;width:75px;border-radius:4px;"></div></td>
            <td><div class="skel" style="height:20px;width:55px;border-radius:999px;"></div></td>
            <td><div style="display:flex;gap:5px;">
                <div class="skel" style="width:30px;height:30px;border-radius:7px;"></div>
                <div class="skel" style="width:30px;height:30px;border-radius:7px;"></div>
                <div class="skel" style="width:30px;height:30px;border-radius:7px;"></div>
            </div></td>
        </tr>
    `).join('');
}

function showEmpty(msg) {
    const tbody = document.getElementById('tbody-karyawan');
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">${msg}</td></tr>`;
}

// ── CRUD Operations ───────────────────────────────────────────────────────────
async function bukaModalTambah() {
    editingId = null;
    document.getElementById('modal-karyawan-title').textContent = 'Tambah Karyawan';
    document.getElementById('form-karyawan').reset();
    document.getElementById('k-password-group').style.display = 'block';
    document.getElementById('k-status-group').style.display   = 'none';
    clearFormErrors();
    openModal('modal-karyawan');
}

async function bukaModalEdit(id) {
    editingId = id;
    document.getElementById('modal-karyawan-title').textContent = 'Edit Karyawan';
    document.getElementById('form-karyawan').reset();
    document.getElementById('k-password-group').style.display = 'none';
    document.getElementById('k-status-group').style.display   = 'block';
    clearFormErrors();

    try {
        const res  = await apiFetch(`/api/admin/karyawan/${id}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        const k = json.data;
        setVal('k-nama',          k.nama_lengkap);
        setVal('k-email',         k.akun?.email ?? '');
        setVal('k-nik',           k.nik ?? '');
        setVal('k-nomor-karyawan',k.nomor_karyawan ?? '');
        setVal('k-posisi',        k.posisi ?? '');
        setVal('k-departemen',    k.departemen?.id_departemen ?? '');
        setVal('k-tgl-bergabung', k.tanggal_bergabung ?? '');
        setVal('k-status',        k.status);

        openModal('modal-karyawan');
    } catch {
        toast('Gagal memuat data karyawan.', 'error');
    }
}

async function simpanKaryawan() {
    const btn = document.getElementById('btn-simpan-karyawan');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const body = {
        nama_lengkap:    getVal('k-nama'),
        email:           getVal('k-email'),
        nik:             getVal('k-nik'),
        nomor_karyawan:  getVal('k-nomor-karyawan'),
        posisi:          getVal('k-posisi'),
        id_departemen:   getVal('k-departemen') || null,
        tanggal_bergabung: getVal('k-tgl-bergabung'),
    };

    if (!editingId) {
        body.password              = getVal('k-password');
        body.password_confirmation = getVal('k-password-confirm');
    } else {
        body.status = getVal('k-status');
    }

    const url    = editingId ? `/api/admin/karyawan/${editingId}` : '/api/admin/karyawan';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-karyawan');
            loadKaryawan(currentPage);
        } else {
            showFormErrors(json.data);
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Simpan';
    }
}

async function nonaktifkanKaryawan(id, nama) {
    const ok = await confirmDelete(`akun karyawan ${nama}`);
    if (!ok) return;

    try {
        const res  = await apiFetch(`/api/admin/karyawan/${id}`, { method: 'DELETE' });
        const json = await res.json();
        json.status
            ? (toast(json.message, 'success'), loadKaryawan(currentPage))
            : toast(json.message, 'error');
    } catch {
        toast('Gagal menonaktifkan karyawan.', 'error');
    }
}

async function aktifkanKaryawan(id, nama) {
    try {
        const res  = await apiFetch(`/api/admin/karyawan/${id}/aktifkan`, { method: 'PUT' });
        const json = await res.json();
        json.status
            ? (toast(json.message, 'success'), loadKaryawan(currentPage))
            : toast(json.message, 'error');
    } catch {
        toast('Gagal mengaktifkan karyawan.', 'error');
    }
}

function bukaModalResetPassword(id, nama) {
    setVal('reset-pw-id', id);
    document.getElementById('reset-pw-nama').textContent = nama;
    setVal('reset-pw-baru', '');
    setVal('reset-pw-confirm', '');
    clearFormErrors('form-reset-pw-k');
    openModal('modal-reset-pw-k');
}

async function simpanResetPassword() {
    const id  = getVal('reset-pw-id');
    const btn = document.getElementById('btn-simpan-reset-pw-k');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        const res  = await apiFetch(`/api/admin/karyawan/${id}/reset-password`, {
            method: 'PUT',
            body: JSON.stringify({
                password:              getVal('reset-pw-baru'),
                password_confirmation: getVal('reset-pw-confirm'),
            }),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-reset-pw-k');
        } else {
            showFormErrors(json.data, 'form-reset-pw-k');
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal reset password.', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Reset Password';
    }
}

// ── Dropdown departemen ───────────────────────────────────────────────────────
async function loadDepartemenDropdown() {
    try {
        const res = await apiFetch('/api/admin/lookup/departemen?status=aktif');
        const json = await res.json();
        const sel  = document.getElementById('k-departemen');
        const selF = document.getElementById('filter-dept');
        const rows = Array.isArray(json.data) ? json.data : (json.data?.data ?? []);

        rows.forEach(d => {
            const opt = `<option value="${d.id_departemen}">${esc(d.nama_departemen)}</option>`;
            if (sel) sel.insertAdjacentHTML('beforeend', opt);
            if (selF) selF.insertAdjacentHTML('beforeend', opt);
        });
    } catch { /* dropdown kosong */ }
}

// ── Event binding ─────────────────────────────────────────────────────────────
function bindEvents() {
    document.getElementById('btn-tambah-karyawan')?.addEventListener('click', bukaModalTambah);

    document.getElementById('search-karyawan')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { searchQuery = e.target.value.trim(); loadKaryawan(1); }, 400);
    });

    document.getElementById('filter-status-k')?.addEventListener('change', e => {
        filterStatus = e.target.value; loadKaryawan(1);
    });

    document.getElementById('filter-dept')?.addEventListener('change', e => {
        filterDept = e.target.value; loadKaryawan(1);
    });

    // Delegasi event tabel
    document.getElementById('tabel-karyawan')?.addEventListener('click', async e => {
        const editBtn     = e.target.closest('.btn-edit');
        const hapusBtn    = e.target.closest('.btn-hapus');
        const aktifBtn    = e.target.closest('.btn-aktifkan');
        const resetBtn    = e.target.closest('.btn-reset');

        if (editBtn)  bukaModalEdit(parseInt(editBtn.dataset.id));
        if (hapusBtn) await nonaktifkanKaryawan(parseInt(hapusBtn.dataset.id), hapusBtn.dataset.nama);
        if (aktifBtn) await aktifkanKaryawan(parseInt(aktifBtn.dataset.id), aktifBtn.dataset.nama);
        if (resetBtn) bukaModalResetPassword(parseInt(resetBtn.dataset.id), resetBtn.dataset.nama);
    });

    // Form submit
    document.getElementById('form-karyawan')?.addEventListener('submit', async e => { e.preventDefault(); await simpanKaryawan(); });
    document.getElementById('form-reset-pw-k')?.addEventListener('submit', async e => { e.preventDefault(); await simpanResetPassword(); });

    // Close modals
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── Inject UI ─────────────────────────────────────────────────────────────────
function updateThead() {
    const thead = document.querySelector('#tabel-karyawan thead tr');
    if (thead) thead.innerHTML = `
        <th>Karyawan</th>
        <th>NIK</th>
        <th>Posisi</th>
        <th>Departemen</th>
        <th>Tgl. Bergabung</th>
        <th>Status</th>
        <th>Aksi</th>
    `;
}

function injectToolbar() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('search-karyawan')) return;

    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan" class="ao-search" type="text"
            placeholder="Cari nama, NIK, nomor karyawan...">
        <select id="filter-status-k" class="ao-select">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        <select id="filter-dept" class="ao-select">
            <option value="">Semua Departemen</option>
        </select>
    `;

    // Tombol tambah — tambahkan ke page-header jika belum ada
    if (!document.getElementById('btn-tambah-karyawan')) {
        const btn = document.createElement('button');
        btn.id = 'btn-tambah-karyawan';
        btn.className = 'btn-primary';
        btn.textContent = '+ Tambah Karyawan';
        header.appendChild(btn);
    }

    header.after(wrap);
    // Inject paginasi container setelah tabel
    const panel = document.querySelector('.dash-panel--full .dash-panel-body');
    if (panel && !document.getElementById('paginasi-karyawan')) {
        panel.insertAdjacentHTML('beforeend', '<div id="paginasi-karyawan"></div>');
    }
}

function injectModals() {
    if (document.getElementById('modal-karyawan')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <!-- Modal Karyawan (tambah / edit) -->
        <div id="modal-karyawan" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:580px;">
                <div class="modal-header">
                    <h3 id="modal-karyawan-title" class="modal-title">Tambah Karyawan</h3>
                    <button data-close-modal="modal-karyawan" class="modal-close">×</button>
                </div>
                <form id="form-karyawan" class="modal-body">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="form-label">Nama Lengkap</label>
                            <input id="k-nama" type="text" class="form-input" placeholder="Ahmad Surya Pratama">
                            <span id="err-nama_lengkap" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input id="k-email" type="email" class="form-input" placeholder="ahmad@contoh.com">
                            <span id="err-email" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NIK (KTP)</label>
                            <input id="k-nik" type="text" class="form-input" placeholder="3272xxxxxxxxxxxxx">
                            <span id="err-nik" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor Karyawan</label>
                            <input id="k-nomor-karyawan" type="text" class="form-input" placeholder="EKO-2025-001">
                            <span id="err-nomor_karyawan" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Posisi / Jabatan</label>
                            <input id="k-posisi" type="text" class="form-input" placeholder="Operator Produksi">
                            <span id="err-posisi" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Departemen</label>
                            <select id="k-departemen" class="form-input">
                                <option value="">— Pilih Departemen —</option>
                            </select>
                            <span id="err-id_departemen" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input id="k-tgl-bergabung" type="date" class="form-input">
                            <span id="err-tanggal_bergabung" class="form-error"></span>
                        </div>
                        <div id="k-status-group" class="form-group" style="display:none;">
                            <label class="form-label">Status</label>
                            <select id="k-status" class="form-input">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div id="k-password-group" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input id="k-password" type="password" class="form-input" placeholder="Min. 8 karakter">
                            <span id="err-password" class="form-error"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password</label>
                            <input id="k-password-confirm" type="password" class="form-input" placeholder="Ulangi password">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-karyawan" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-karyawan" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Reset Password Karyawan -->
        <div id="modal-reset-pw-k" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:420px;">
                <div class="modal-header">
                    <h3 class="modal-title">Reset Password</h3>
                    <button data-close-modal="modal-reset-pw-k" class="modal-close">×</button>
                </div>
                <form id="form-reset-pw-k" class="modal-body">
                    <input id="reset-pw-id" type="hidden">
                    <p style="font-size:13px;color:#64748b;margin:0 0 20px;">
                        Reset password untuk: <strong id="reset-pw-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input id="reset-pw-baru" type="password" class="form-input" placeholder="Min. 8 karakter">
                        <span id="err-password" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input id="reset-pw-confirm" type="password" class="form-input" placeholder="Ulangi password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-reset-pw-k" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-reset-pw-k" class="btn-primary-sm">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    `);

    // Re-bind setelah inject
    document.getElementById('form-karyawan')?.addEventListener('submit', async e => { e.preventDefault(); await simpanKaryawan(); });
    document.getElementById('form-reset-pw-k')?.addEventListener('submit', async e => { e.preventDefault(); await simpanResetPassword(); });
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function getVal(id) { return document.getElementById(id)?.value ?? ''; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ''; }

function showFormErrors(errors, formId = 'form-karyawan') {
    clearFormErrors(formId);
    if (!errors || typeof errors !== 'object') return;
    Object.entries(errors).forEach(([field, msgs]) => {
        const el = document.getElementById(`err-${field}`);
        if (el) { el.textContent = Array.isArray(msgs) ? msgs[0] : msgs; el.style.display = 'block'; }
    });
}

function clearFormErrors(formId = 'form-karyawan') {
    document.getElementById(formId)?.querySelectorAll('[id^="err-"]').forEach(el => {
        el.textContent = ''; el.style.display = 'none';
    });
}