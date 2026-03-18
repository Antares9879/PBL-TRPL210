/**
 * resources/js/super-admin/akun.js
 * F17 — Manajemen Akun Super Admin
 *
 * Fitur:
 *  - Tabel akun dengan paginasi
 *  - Search realtime (debounce 400ms)
 *  - Filter by role & status
 *  - Modal tambah / edit akun
 *  - Hapus akun dengan konfirmasi
 *  - Reset password akun
 *  - Badge role & status berwarna
 */

import {
    apiFetch, toast, confirmDelete,
    openModal, closeModal,
    badgeStatus, badgeRole, formatDateTime,
    renderPaginasi,
} from './_utils.js';

// ── State ─────────────────────────────────────────────────────────────────────
let currentPage  = 1;
let searchQuery  = '';
let filterRole   = '';
let filterStatus = '';
let editingId    = null;   // null = mode tambah, angka = mode edit
let debounceTimer = null;

// ── DOM refs ──────────────────────────────────────────────────────────────────
const tbody         = () => document.getElementById('tbody-pengguna');
const paginasiEl    = 'paginasi-pengguna';

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    injectModalHtml();
    injectStyles();
    bindPageEvents();
    loadAkun();
});

// ── Load data ─────────────────────────────────────────────────────────────────
async function loadAkun(page = 1) {
    currentPage = page;
    showSkeleton();

    const params = new URLSearchParams({ page });
    if (searchQuery)  params.set('search', searchQuery);
    if (filterRole)   params.set('role',   filterRole);
    if (filterStatus) params.set('status', filterStatus);

    try {
        const res  = await apiFetch(`/api/super-admin/akun?${params}`);
        const json = await res.json();

        if (!json.status) {
            toast(json.message, 'error');
            tbody().innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">Gagal memuat data.</td></tr>`;
            return;
        }

        renderTabel(json.data.data);
        renderPaginasi(json.data, paginasiEl, loadAkun);

    } catch (err) {
        console.error('[Akun] Load error:', err);
        toast('Gagal terhubung ke server.', 'error');
    }
}

// ── Render tabel ──────────────────────────────────────────────────────────────
function renderTabel(rows) {
    if (!rows.length) {
        tbody().innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">Tidak ada data akun.</td></tr>`;
        return;
    }

    tbody().innerHTML = rows.map(p => `
        <tr data-id="${p.id_pengguna}">
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="
                        width:32px;height:32px;border-radius:8px;
                        background:linear-gradient(135deg,#1a6e1a,#164916);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:12px;font-weight:700;color:#87dc87;
                        flex-shrink:0;
                    ">${p.nama_lengkap.charAt(0).toUpperCase()}</div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${escHtml(p.nama_lengkap)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${escHtml(p.email)}</div>
                    </div>
                </div>
            </td>
            <td>${badgeRole(p.role)}</td>
            <td>${renderProfil(p)}</td>
            <td>${badgeStatus(p.status)}</td>
            <td style="font-size:12px;color:#64748b;">${formatDateTime(p.last_login)}</td>
            <td>
                <div style="display:flex;gap:6px;">
                    <button class="btn-aksi btn-edit" data-id="${p.id_pengguna}" title="Edit">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button class="btn-aksi btn-reset-pw" data-id="${p.id_pengguna}" data-nama="${escHtml(p.nama_lengkap)}" title="Reset Password">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.743 5.743L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.586a1 1 0 0 1 .293-.707l5.964-5.964A6 6 0 1 1 21 9z"/>
                        </svg>
                    </button>
                    <button class="btn-aksi btn-hapus" data-id="${p.id_pengguna}" data-nama="${escHtml(p.nama_lengkap)}" title="Hapus">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderProfil(p) {
    if (!p.profil) return '<span style="color:#94a3b8;font-size:12px;">—</span>';
    if (p.role === 'admin_outsource') return `<span style="font-size:12px;color:#475569;">${escHtml(p.profil.nama_perusahaan ?? '—')}</span>`;
    if (p.role === 'user_departemen') return `<span style="font-size:12px;color:#475569;">${escHtml(p.profil.nama_departemen ?? '—')}</span>`;
    return '<span style="color:#94a3b8;font-size:12px;">—</span>';
}

function showSkeleton() {
    tbody().innerHTML = `
        <tr><td colspan="6">
            <div style="display:flex;flex-direction:column;gap:10px;padding:16px 0;">
                ${Array(5).fill(`
                    <div style="display:flex;gap:12px;align-items:center;">
                        <div class="skel" style="width:32px;height:32px;border-radius:8px;flex-shrink:0;"></div>
                        <div class="skel" style="height:10px;width:40%;border-radius:4px;"></div>
                        <div class="skel" style="height:10px;width:15%;border-radius:4px;margin-left:auto;"></div>
                    </div>
                `).join('')}
            </div>
        </td></tr>`;
}

// ── Event binding ─────────────────────────────────────────────────────────────
function bindPageEvents() {

    // Tombol tambah
    document.getElementById('btn-tambah-pengguna')?.addEventListener('click', () => {
        bukaModalTambah();
    });

    // Search dengan debounce
    document.getElementById('input-search-akun')?.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchQuery = e.target.value.trim();
            loadAkun(1);
        }, 400);
    });

    // Filter role
    document.getElementById('filter-role')?.addEventListener('change', (e) => {
        filterRole = e.target.value;
        loadAkun(1);
    });

    // Filter status
    document.getElementById('filter-status')?.addEventListener('change', (e) => {
        filterStatus = e.target.value;
        loadAkun(1);
    });

    // Delegasi event pada tabel (edit, hapus, reset-pw)
    document.getElementById('tabel-pengguna')?.addEventListener('click', async (e) => {
        const editBtn  = e.target.closest('.btn-edit');
        const hapusBtn = e.target.closest('.btn-hapus');
        const resetBtn = e.target.closest('.btn-reset-pw');

        if (editBtn)  bukaModalEdit(parseInt(editBtn.dataset.id));
        if (hapusBtn) await hapusAkun(parseInt(hapusBtn.dataset.id), hapusBtn.dataset.nama);
        if (resetBtn) bukaModalResetPassword(parseInt(resetBtn.dataset.id), resetBtn.dataset.nama);
    });

    // Form submit modal akun
    document.getElementById('form-akun')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await simpanAkun();
    });

    // Form submit modal reset password
    document.getElementById('form-reset-pw')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await simpanResetPassword();
    });

    // Role change → tampilkan/sembunyikan field profil
    document.getElementById('akun-role')?.addEventListener('change', (e) => {
        toggleProfilFields(e.target.value);
    });

    // Tutup modal
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
}

// ── CRUD Operations ───────────────────────────────────────────────────────────
async function bukaModalTambah() {
    editingId = null;
    resetFormAkun();
    document.getElementById('modal-akun-title').textContent = 'Tambah Pengguna';
    document.getElementById('akun-status-group').style.display = 'none';
    document.getElementById('akun-password-group').style.display = 'block';

    await loadDropdownPerusahaan();
    await loadDropdownDepartemen();
    openModal('modal-akun');
}

async function bukaModalEdit(id) {
    editingId = id;
    resetFormAkun();
    document.getElementById('modal-akun-title').textContent = 'Edit Pengguna';
    document.getElementById('akun-status-group').style.display = 'block';
    document.getElementById('akun-password-group').style.display = 'none';

    await loadDropdownPerusahaan();
    await loadDropdownDepartemen();

    try {
        const res  = await apiFetch(`/api/super-admin/akun/${id}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); return; }

        const p = json.data;
        document.getElementById('akun-nama').value   = p.nama_lengkap;
        document.getElementById('akun-email').value  = p.email;
        document.getElementById('akun-role').value   = p.role;
        document.getElementById('akun-status').value = p.status;

        toggleProfilFields(p.role);

        if (p.profil?.id_perusahaan) {
            document.getElementById('akun-perusahaan').value = p.profil.id_perusahaan;
        }
        if (p.profil?.id_departemen) {
            document.getElementById('akun-departemen').value = p.profil.id_departemen;
        }

        openModal('modal-akun');

    } catch (err) {
        toast('Gagal memuat data akun.', 'error');
    }
}

async function simpanAkun() {
    const btnSubmit = document.getElementById('btn-simpan-akun');
    btnSubmit.disabled    = true;
    btnSubmit.textContent = 'Menyimpan...';

    const body = {
        nama_lengkap:   document.getElementById('akun-nama').value,
        email:          document.getElementById('akun-email').value,
        role:           document.getElementById('akun-role').value,
    };

    if (editingId === null) {
        body.password              = document.getElementById('akun-password').value;
        body.password_confirmation = document.getElementById('akun-password-confirm').value;
    } else {
        body.status = document.getElementById('akun-status').value;
    }

    const role = body.role;
    if (role === 'admin_outsource') body.id_perusahaan = document.getElementById('akun-perusahaan').value;
    if (role === 'user_departemen') body.id_departemen = document.getElementById('akun-departemen').value;

    const url    = editingId ? `/api/super-admin/akun/${editingId}` : '/api/super-admin/akun';
    const method = editingId ? 'PUT' : 'POST';

    try {
        const res  = await apiFetch(url, { method, body: JSON.stringify(body) });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-akun');
            loadAkun(currentPage);
        } else {
            showFormErrors('form-akun', json.data);
            toast(json.message, 'error');
        }
    } catch (err) {
        toast('Gagal menyimpan akun.', 'error');
    } finally {
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'Simpan';
    }
}

async function hapusAkun(id, nama) {
    const ok = await confirmDelete(nama);
    if (!ok) return;

    try {
        const res  = await apiFetch(`/api/super-admin/akun/${id}`, { method: 'DELETE' });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            loadAkun(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch (err) {
        toast('Gagal menghapus akun.', 'error');
    }
}

function bukaModalResetPassword(id, nama) {
    document.getElementById('reset-pw-nama').textContent = nama;
    document.getElementById('reset-pw-id').value = id;
    document.getElementById('reset-pw-password').value = '';
    document.getElementById('reset-pw-confirm').value  = '';
    clearFormErrors('form-reset-pw');
    openModal('modal-reset-pw');
}

async function simpanResetPassword() {
    const id     = document.getElementById('reset-pw-id').value;
    const btn    = document.getElementById('btn-simpan-reset-pw');
    btn.disabled    = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res  = await apiFetch(`/api/super-admin/akun/${id}/reset-password`, {
            method: 'PUT',
            body: JSON.stringify({
                password:              document.getElementById('reset-pw-password').value,
                password_confirmation: document.getElementById('reset-pw-confirm').value,
            }),
        });
        const json = await res.json();

        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-reset-pw');
        } else {
            showFormErrors('form-reset-pw', json.data);
            toast(json.message, 'error');
        }
    } catch (err) {
        toast('Gagal reset password.', 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Reset Password';
    }
}

// ── Dropdown helpers ──────────────────────────────────────────────────────────
async function loadDropdownPerusahaan() {
    try {
        const res  = await apiFetch('/api/super-admin/perusahaan?status=aktif&page=1');
        const json = await res.json();
        const sel  = document.getElementById('akun-perusahaan');
        sel.innerHTML = '<option value="">— Pilih Perusahaan —</option>';
        (json.data?.data ?? []).forEach(p => {
            sel.innerHTML += `<option value="${p.id_perusahaan}">${escHtml(p.nama_perusahaan)}</option>`;
        });
    } catch (_) {}
}

async function loadDropdownDepartemen() {
    try {
        const res  = await apiFetch('/api/super-admin/departemen?status=aktif&page=1');
        const json = await res.json();
        const sel  = document.getElementById('akun-departemen');
        sel.innerHTML = '<option value="">— Pilih Departemen —</option>';
        (json.data?.data ?? []).forEach(d => {
            sel.innerHTML += `<option value="${d.id_departemen}">${escHtml(d.nama_departemen)}</option>`;
        });
    } catch (_) {}
}

function toggleProfilFields(role) {
    const perusahaanGroup = document.getElementById('akun-perusahaan-group');
    const departemenGroup = document.getElementById('akun-departemen-group');
    perusahaanGroup.style.display = role === 'admin_outsource' ? 'block' : 'none';
    departemenGroup.style.display = role === 'user_departemen' ? 'block' : 'none';
}

// ── Form helpers ──────────────────────────────────────────────────────────────
function resetFormAkun() {
    document.getElementById('form-akun').reset();
    clearFormErrors('form-akun');
    toggleProfilFields('');
}

function showFormErrors(formId, errors) {
    clearFormErrors(formId);
    if (!errors || typeof errors !== 'object') return;
    Object.entries(errors).forEach(([field, messages]) => {
        const errEl = document.getElementById(`err-${field}`);
        if (errEl) {
            errEl.textContent = Array.isArray(messages) ? messages[0] : messages;
            errEl.style.display = 'block';
        }
    });
}

function clearFormErrors(formId) {
    document.getElementById(formId)?.querySelectorAll('[id^="err-"]')
        .forEach(el => { el.textContent = ''; el.style.display = 'none'; });
}

// ── Inject HTML (modal) ───────────────────────────────────────────────────────
function injectModalHtml() {
    const existing = document.getElementById('modal-akun');
    if (existing) return;

    // Inject toolbar ke halaman
    const toolbar = document.querySelector('.page-header');
    if (toolbar) {
        const toolbarExtra = document.createElement('div');
        toolbarExtra.style.cssText = 'display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:16px;';
        toolbarExtra.innerHTML = `
            <input id="input-search-akun" type="text" placeholder="Cari nama atau email..."
                style="padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;
                       font-family:'DM Sans',sans-serif;outline:none;width:240px;">
            <select id="filter-role" style="padding:8px 12px;border:1px solid #e2e8f0;
                border-radius:8px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;color:#374151;">
                <option value="">Semua Role</option>
                <option value="super_admin">Super Admin</option>
                <option value="hr">HR</option>
                <option value="user_departemen">User Departemen</option>
                <option value="admin_outsource">Admin Outsource</option>
            </select>
            <select id="filter-status" style="padding:8px 12px;border:1px solid #e2e8f0;
                border-radius:8px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;color:#374151;">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        `;
        toolbar.after(toolbarExtra);
    }

    // Update header tabel
    const thead = document.querySelector('#tabel-pengguna thead tr');
    if (thead) {
        thead.innerHTML = `
            <th>Pengguna</th>
            <th>Role</th>
            <th>Perusahaan / Departemen</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Aksi</th>
        `;
    }

    // Modal akun
    document.body.insertAdjacentHTML('beforeend', `
        <div id="modal-akun" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 id="modal-akun-title" class="modal-title">Tambah Pengguna</h3>
                    <button data-close-modal="modal-akun" class="modal-close">×</button>
                </div>
                <form id="form-akun" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input id="akun-nama" type="text" class="form-input" placeholder="Nama lengkap pengguna">
                        <span id="err-nama_lengkap" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input id="akun-email" type="email" class="form-input" placeholder="email@contoh.com">
                        <span id="err-email" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select id="akun-role" class="form-input">
                            <option value="">— Pilih Role —</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="hr">HR</option>
                            <option value="user_departemen">User Departemen</option>
                            <option value="admin_outsource">Admin Outsource</option>
                        </select>
                        <span id="err-role" class="form-error"></span>
                    </div>
                    <div id="akun-perusahaan-group" class="form-group" style="display:none;">
                        <label class="form-label">Perusahaan Outsource</label>
                        <select id="akun-perusahaan" class="form-input">
                            <option value="">— Pilih Perusahaan —</option>
                        </select>
                        <span id="err-id_perusahaan" class="form-error"></span>
                    </div>
                    <div id="akun-departemen-group" class="form-group" style="display:none;">
                        <label class="form-label">Departemen</label>
                        <select id="akun-departemen" class="form-input">
                            <option value="">— Pilih Departemen —</option>
                        </select>
                        <span id="err-id_departemen" class="form-error"></span>
                    </div>
                    <div id="akun-status-group" class="form-group">
                        <label class="form-label">Status</label>
                        <select id="akun-status" class="form-input">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        <span id="err-status" class="form-error"></span>
                    </div>
                    <div id="akun-password-group" class="form-group">
                        <label class="form-label">Password</label>
                        <input id="akun-password" type="password" class="form-input" placeholder="Minimal 8 karakter">
                        <span id="err-password" class="form-error"></span>
                    </div>
                    <div id="akun-password-group" class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input id="akun-password-confirm" type="password" class="form-input" placeholder="Ulangi password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-akun" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-akun" class="btn-primary-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="modal-reset-pw" class="modal-overlay" style="display:none;">
            <div class="modal-box" style="max-width:400px;">
                <div class="modal-header">
                    <h3 class="modal-title">Reset Password</h3>
                    <button data-close-modal="modal-reset-pw" class="modal-close">×</button>
                </div>
                <form id="form-reset-pw" class="modal-body">
                    <input id="reset-pw-id" type="hidden">
                    <p style="font-size:13px;color:#64748b;margin:0 0 20px;">
                        Reset password untuk: <strong id="reset-pw-nama" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input id="reset-pw-password" type="password" class="form-input" placeholder="Minimal 8 karakter">
                        <span id="err-password" class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input id="reset-pw-confirm" type="password" class="form-input" placeholder="Ulangi password baru">
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-close-modal="modal-reset-pw" class="btn-cancel">Batal</button>
                        <button type="submit" id="btn-simpan-reset-pw" class="btn-primary-sm">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    `);

    // Re-bind setelah inject
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
    document.getElementById('form-akun')?.addEventListener('submit', async (e) => {
        e.preventDefault(); await simpanAkun();
    });
    document.getElementById('form-reset-pw')?.addEventListener('submit', async (e) => {
        e.preventDefault(); await simpanResetPassword();
    });
    document.getElementById('akun-role')?.addEventListener('change', (e) => {
        toggleProfilFields(e.target.value);
    });
}

// ── Shared CSS ────────────────────────────────────────────────────────────────
function injectStyles() {
    if (document.getElementById('sa-modal-styles')) return;
    const style = document.createElement('style');
    style.id = 'sa-modal-styles';
    style.textContent = `
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(3px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.modal--open { display: flex !important; }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.15);
            animation: slideUp 0.22s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes slideUp {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 24px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px; font-weight: 700;
            color: #0f172a; margin: 0;
        }
        .modal-close {
            background: none; border: none; cursor: pointer;
            font-size: 22px; color: #94a3b8; padding: 0;
            line-height: 1; width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px;
        }
        .modal-close:hover { background: #f1f5f9; color: #374151; }
        .modal-body { padding: 20px 24px; }
        .modal-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding-top: 20px; margin-top: 4px;
            border-top: 1px solid #f1f5f9;
        }
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-family: 'DM Sans', sans-serif;
            font-size: 11px; font-weight: 600; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 6px;
        }
        .form-input {
            width: 100%; padding: 10px 14px;
            border: 1px solid #e2e8f0; border-radius: 9px;
            font-family: 'DM Sans', sans-serif; font-size: 13.5px; color: #0f172a;
            background: #f8fafc; outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .form-input:focus {
            border-color: #2da82d;
            box-shadow: 0 0 0 3px rgba(45,168,45,0.12);
            background: #fff;
        }
        .form-error {
            display: none; font-size: 12px; color: #ef4444;
            margin-top: 4px; font-family: 'DM Sans', sans-serif;
        }
        .btn-cancel {
            padding: 9px 18px; border: 1px solid #e2e8f0;
            border-radius: 8px; background: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 500; color: #475569; cursor: pointer;
        }
        .btn-cancel:hover { background: #f8fafc; }
        .btn-primary-sm {
            padding: 9px 20px; border: none; border-radius: 8px;
            background: linear-gradient(135deg, #1f8a1f, #1a6e1a);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 600; color: #fff; cursor: pointer;
        }
        .btn-primary-sm:hover { opacity: 0.9; }
        .btn-primary-sm:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-aksi {
            width: 30px; height: 30px; border-radius: 7px;
            border: 1px solid #e2e8f0; background: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; color: #64748b;
            transition: background 0.15s, color 0.15s;
        }
        .btn-edit:hover   { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .btn-reset-pw:hover { background: #fffbeb; color: #d97706; border-color: #fde68a; }
        .btn-hapus:hover  { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .skel {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s ease infinite;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .btn-primary {
            padding: 9px 18px; border: none; border-radius: 9px;
            background: linear-gradient(135deg, #1f8a1f, #1a6e1a);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 600; color: #fff; cursor: pointer;
        }
        .btn-primary:hover { opacity: 0.9; }
    `;
    document.head.appendChild(style);
}

// ── Escape HTML ───────────────────────────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
