/**
 * resources/js/super-admin/_utils.js
 * Shared utilities untuk semua halaman Super Admin.
 *
 * Export:
 *   - setupCsrf()        — set CSRF header default untuk semua fetch
 *   - toast(msg, type)   — tampilkan toast notification
 *   - confirmDelete(msg) — promise-based konfirmasi dialog sebelum delete
 *   - badgeStatus(status)— return HTML badge berwarna
 *   - badgeRole(role)    — return HTML badge role
 *   - formatDateTime(dt) — format ISO string ke tanggal Indonesia
 */

// ── CSRF ─────────────────────────────────────────────────────────────────────

export function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function apiFetch(url, options = {}) {
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type':     'application/json',
            'Accept':           'application/json',
            'X-CSRF-TOKEN':     getCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers ?? {}),
        },
    });
}

// ── Toast ─────────────────────────────────────────────────────────────────────

let toastContainer = null;

function ensureToastContainer() {
    if (toastContainer) return;
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    `;
    document.body.appendChild(toastContainer);
}

/**
 * Tampilkan toast notification.
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration  ms sebelum hilang (default 3500)
 */
export function toast(message, type = 'success', duration = 3500) {
    ensureToastContainer();

    const colors = {
        success: { bg: '#f0faf0', border: '#bbecbb', text: '#1a6e1a', icon: '✓' },
        error:   { bg: '#fef2f2', border: '#fecaca', text: '#b91c1c', icon: '✕' },
        warning: { bg: '#fffbeb', border: '#fef3c7', text: '#92400e', icon: '!' },
        info:    { bg: '#eff6ff', border: '#dbeafe', text: '#1d4ed8', icon: 'i' },
    };
    const c = colors[type] ?? colors.info;

    const el = document.createElement('div');
    el.style.cssText = `
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: ${c.bg};
        border: 1px solid ${c.border};
        border-radius: 10px;
        color: ${c.text};
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        pointer-events: all;
        min-width: 260px;
        max-width: 380px;
        transform: translateX(120%);
        transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), opacity 0.3s ease;
        opacity: 0;
    `;
    el.innerHTML = `
        <span style="
            width: 20px; height: 20px; border-radius: 50%;
            background: ${c.border}; display: flex; align-items: center;
            justify-content: center; font-size: 11px; font-weight: 700;
            flex-shrink: 0;
        ">${c.icon}</span>
        <span style="flex:1; line-height:1.4;">${message}</span>
        <button onclick="this.closest('div').remove()" style="
            background:none; border:none; cursor:pointer; color:${c.text};
            opacity:0.5; font-size:16px; padding:0; line-height:1;
        ">×</button>
    `;

    toastContainer.appendChild(el);

    // Animate in
    requestAnimationFrame(() => {
        el.style.transform = 'translateX(0)';
        el.style.opacity   = '1';
    });

    // Auto remove
    setTimeout(() => {
        el.style.transform = 'translateX(120%)';
        el.style.opacity   = '0';
        setTimeout(() => el.remove(), 300);
    }, duration);
}

// ── Confirm Delete Dialog ─────────────────────────────────────────────────────

/**
 * Tampilkan dialog konfirmasi delete.
 * @returns {Promise<boolean>} true jika user konfirmasi, false jika batal
 */
export function confirmDelete(itemName = 'data ini') {
    return new Promise((resolve) => {
        // Hapus overlay lama jika ada
        document.getElementById('confirm-overlay')?.remove();

        const overlay = document.createElement('div');
        overlay.id = 'confirm-overlay';
        overlay.style.cssText = `
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(2px);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.15s ease;
        `;

        overlay.innerHTML = `
            <div style="
                background: #fff;
                border-radius: 14px;
                padding: 28px;
                max-width: 380px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.15);
                animation: slideUp 0.2s cubic-bezier(0.16,1,0.3,1);
            ">
                <div style="
                    width: 44px; height: 44px; border-radius: 50%;
                    background: #fef2f2; display: flex;
                    align-items: center; justify-content: center;
                    margin-bottom: 16px;
                ">
                    <svg width="20" height="20" fill="none" stroke="#b91c1c" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 style="
                    font-family: 'Syne', sans-serif;
                    font-size: 16px; font-weight: 700;
                    color: #0f172a; margin: 0 0 8px;
                ">Hapus ${itemName}?</h3>
                <p style="
                    font-family: 'DM Sans', sans-serif;
                    font-size: 13px; color: #64748b;
                    margin: 0 0 24px; line-height: 1.5;
                ">Tindakan ini tidak dapat dibatalkan. Data yang dihapus tidak dapat dipulihkan.</p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button id="confirm-cancel" style="
                        padding: 9px 18px;
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        background: #fff;
                        font-family: 'DM Sans', sans-serif;
                        font-size: 13px; font-weight: 500;
                        color: #475569; cursor: pointer;
                    ">Batal</button>
                    <button id="confirm-ok" style="
                        padding: 9px 18px;
                        border: none;
                        border-radius: 8px;
                        background: #dc2626;
                        font-family: 'DM Sans', sans-serif;
                        font-size: 13px; font-weight: 600;
                        color: #fff; cursor: pointer;
                    ">Ya, Hapus</button>
                </div>
            </div>
            <style>
                @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
                @keyframes slideUp { from { transform: translateY(16px); opacity: 0; }
                                     to   { transform: translateY(0);    opacity: 1; } }
            </style>
        `;

        document.body.appendChild(overlay);

        overlay.querySelector('#confirm-ok').addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });
        overlay.querySelector('#confirm-cancel').addEventListener('click', () => {
            overlay.remove();
            resolve(false);
        });
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) { overlay.remove(); resolve(false); }
        });
    });
}

// ── Modal Helper ──────────────────────────────────────────────────────────────

/**
 * Buka modal dengan id tertentu.
 */
export function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.classList.add('modal--open'));
}

/**
 * Tutup modal dengan id tertentu.
 */
export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('modal--open');
    setTimeout(() => { modal.style.display = 'none'; }, 200);
}

// ── Badge Helpers ─────────────────────────────────────────────────────────────

export function badgeStatus(status) {
    if (status === 'aktif') {
        return `<span class="badge badge--success">● Aktif</span>`;
    }
    return `<span class="badge badge--danger">● Nonaktif</span>`;
}

export function badgeRole(role) {
    const map = {
        super_admin:     { cls: 'badge--info',    label: 'Super Admin' },
        hr:              { cls: 'badge--success',  label: 'HR' },
        user_departemen: { cls: 'badge--warning',  label: 'User Dept.' },
        admin_outsource: { cls: 'badge--neutral',  label: 'Admin Outsource' },
        karyawan:        { cls: 'badge--neutral',  label: 'Karyawan' },
    };
    const b = map[role] ?? { cls: 'badge--neutral', label: role };
    return `<span class="badge ${b.cls}">${b.label}</span>`;
}

// ── Format Helpers ────────────────────────────────────────────────────────────

export function formatDateTime(isoString) {
    if (!isoString) return '—';
    return new Date(isoString).toLocaleString('id-ID', {
        day:    '2-digit',
        month:  'short',
        year:   'numeric',
        hour:   '2-digit',
        minute: '2-digit',
    });
}

export function formatDate(isoString) {
    if (!isoString) return '—';
    return new Date(isoString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}

// ── Pagination Renderer ───────────────────────────────────────────────────────

/**
 * Render tombol paginasi ke dalam container.
 * @param {object} meta   — object paginasi dari Laravel (current_page, last_page, dll)
 * @param {string} containerId — id elemen container paginasi
 * @param {function} onPage    — callback(page) saat tombol diklik
 */
export function renderPaginasi(meta, containerId, onPage) {
    const container = document.getElementById(containerId);
    if (!container || meta.last_page <= 1) {
        if (container) container.innerHTML = '';
        return;
    }

    const prev = meta.current_page > 1;
    const next = meta.current_page < meta.last_page;

    container.innerHTML = `
        <div style="
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0 0; font-family: 'DM Sans', sans-serif; font-size: 13px;
        ">
            <span style="color: #64748b;">
                Halaman ${meta.current_page} dari ${meta.last_page}
                &nbsp;·&nbsp; Total ${meta.total} data
            </span>
            <div style="display: flex; gap: 6px;">
                <button
                    onclick="(${onPage.toString()})(${meta.current_page - 1})"
                    ${prev ? '' : 'disabled'}
                    style="
                        padding: 6px 14px; border-radius: 7px;
                        border: 1px solid #e2e8f0; background: #fff;
                        font-size: 13px; cursor: ${prev ? 'pointer' : 'not-allowed'};
                        color: ${prev ? '#374151' : '#cbd5e1'};
                    ">← Prev</button>
                <button
                    onclick="(${onPage.toString()})(${meta.current_page + 1})"
                    ${next ? '' : 'disabled'}
                    style="
                        padding: 6px 14px; border-radius: 7px;
                        border: 1px solid #e2e8f0; background: #fff;
                        font-size: 13px; cursor: ${next ? 'pointer' : 'not-allowed'};
                        color: ${next ? '#374151' : '#cbd5e1'};
                    ">Next →</button>
            </div>
        </div>
    `;
}
