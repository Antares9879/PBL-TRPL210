/**
 * resources/js/admin-outsource/_utils.js
 * Shared utilities untuk semua halaman Admin Outsource.
 *
 * Pola identik dengan super-admin/_utils.js, disesuaikan:
 *   - Warna accent: amber (bukan eco-green)
 *   - Badge tambahan: badgeKehadiran, badgeValidasi, badgeStatusIzin
 *   - Helper: fmtWaktu, fmtTanggal, fmtMenit
 */

// ── CSRF + fetch ──────────────────────────────────────────────────────────────
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

// Fetch multipart/form-data (untuk upload file) — tanpa Content-Type header
export function apiFetchFormData(url, formData) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Accept':           'application/json',
            'X-CSRF-TOKEN':     getCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    });
}

// ── Escape HTML ───────────────────────────────────────────────────────────────
export function esc(str) {
    if (str === null || str === undefined) return '—';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Format helpers ────────────────────────────────────────────────────────────
export function fmtWaktu(dt) {
    if (!dt) return '—';
    const s = String(dt);
    // ISO string: ambil HH:MM dari index 11
    if (s.includes('T') || s.includes(' ')) return s.slice(s.indexOf('T') >= 0 ? s.indexOf('T') + 1 : 11, (s.indexOf('T') >= 0 ? s.indexOf('T') + 1 : 11) + 5);
    // sudah format HH:MM:SS
    return s.slice(0, 5);
}

export function fmtTanggal(dt) {
    if (!dt) return '—';
    try {
        return new Date(dt).toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
    } catch { return dt; }
}

export function fmtTanggalPendek(dt) {
    if (!dt) return '—';
    try {
        return new Date(dt).toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short',
        });
    } catch { return dt; }
}

export function fmtMenit(menit) {
    if (!menit && menit !== 0) return '—';
    menit = parseInt(menit);
    if (menit === 0) return '0 mnt';
    const j = Math.floor(menit / 60);
    const m = menit % 60;
    if (j === 0) return `${m} mnt`;
    if (m === 0) return `${j} jam`;
    return `${j} jam ${m} mnt`;
}

// ── Toast ─────────────────────────────────────────────────────────────────────
let _toastContainer = null;

function ensureToast() {
    if (_toastContainer) return;
    _toastContainer = document.createElement('div');
    _toastContainer.style.cssText =
        'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
    document.body.appendChild(_toastContainer);
}

export function toast(message, type = 'success', duration = 3500) {
    ensureToast();

    const map = {
        success: { bg: '#f0faf0', border: '#bbecbb', text: '#1a6e1a',  icon: '✓' },
        error:   { bg: '#fef2f2', border: '#fecaca', text: '#b91c1c',  icon: '✕' },
        warning: { bg: '#fffbeb', border: '#fef3c7', text: '#92400e',  icon: '!' },
        info:    { bg: '#eff6ff', border: '#dbeafe', text: '#1d4ed8',  icon: 'i' },
    };
    const c = map[type] ?? map.info;

    const el = document.createElement('div');
    el.style.cssText = `
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;background:${c.bg};border:1px solid ${c.border};
        border-radius:10px;color:${c.text};
        font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;
        box-shadow:0 4px 16px rgba(0,0,0,0.1);pointer-events:all;
        min-width:260px;max-width:380px;
        transform:translateX(120%);transition:transform 0.3s cubic-bezier(0.16,1,0.3,1),opacity 0.3s;
        opacity:0;
    `;
    el.innerHTML = `
        <span style="width:20px;height:20px;border-radius:50%;background:${c.border};
            display:flex;align-items:center;justify-content:center;
            font-size:11px;font-weight:700;flex-shrink:0;">${c.icon}</span>
        <span style="flex:1;line-height:1.4;">${message}</span>
        <button onclick="this.closest('div').remove()" style="
            background:none;border:none;cursor:pointer;color:${c.text};
            opacity:0.5;font-size:16px;padding:0;line-height:1;">×</button>
    `;

    _toastContainer.appendChild(el);
    requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; el.style.opacity = '1'; });
    setTimeout(() => {
        el.style.transform = 'translateX(120%)';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, duration);
}

// ── Confirm Delete ────────────────────────────────────────────────────────────
export function confirmDelete(itemName = 'data ini') {
    return new Promise((resolve) => {
        document.getElementById('confirm-overlay')?.remove();

        const overlay = document.createElement('div');
        overlay.id = 'confirm-overlay';
        overlay.style.cssText = `
            position:fixed;inset:0;background:rgba(0,0,0,0.45);
            backdrop-filter:blur(2px);z-index:9998;
            display:flex;align-items:center;justify-content:center;
        `;
        overlay.innerHTML = `
            <div style="background:#fff;border-radius:14px;padding:28px;max-width:380px;
                width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.15);
                animation:slideUp 0.2s cubic-bezier(0.16,1,0.3,1);">
                <div style="width:44px;height:44px;border-radius:50%;background:#fef2f2;
                    display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <svg width="20" height="20" fill="none" stroke="#b91c1c" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;
                    color:#0f172a;margin:0 0 8px;">Hapus ${esc(itemName)}?</h3>
                <p style="font-family:'DM Sans',sans-serif;font-size:13px;color:#64748b;
                    margin:0 0 24px;line-height:1.5;">Tindakan ini tidak dapat dibatalkan.</p>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button id="confirm-cancel" style="padding:9px 18px;border:1px solid #e2e8f0;
                        border-radius:8px;background:#fff;font-family:'DM Sans',sans-serif;
                        font-size:13px;font-weight:500;color:#475569;cursor:pointer;">Batal</button>
                    <button id="confirm-ok" style="padding:9px 18px;border:none;border-radius:8px;
                        background:#dc2626;font-family:'DM Sans',sans-serif;
                        font-size:13px;font-weight:600;color:#fff;cursor:pointer;">Ya, Hapus</button>
                </div>
            </div>
            <style>
                @keyframes slideUp{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}
            </style>
        `;
        document.body.appendChild(overlay);
        overlay.querySelector('#confirm-ok').addEventListener('click', () => { overlay.remove(); resolve(true); });
        overlay.querySelector('#confirm-cancel').addEventListener('click', () => { overlay.remove(); resolve(false); });
        overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
    });
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
export function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.style.display = 'flex';
    requestAnimationFrame(() => m.classList.add('modal--open'));
}

export function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('modal--open');
    setTimeout(() => { m.style.display = 'none'; }, 200);
}

// ── Paginasi ──────────────────────────────────────────────────────────────────
export function renderPaginasi(meta, containerId, onPage) {
    const c = document.getElementById(containerId);
    if (!c || meta.last_page <= 1) { if (c) c.innerHTML = ''; return; }

    const prev = meta.current_page > 1;
    const next = meta.current_page < meta.last_page;

    c.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;
            padding:14px 0 0;font-family:'DM Sans',sans-serif;font-size:13px;">
            <span style="color:#64748b;">
                Halaman ${meta.current_page} dari ${meta.last_page}
                &nbsp;·&nbsp; Total ${meta.total} data
            </span>
            <div style="display:flex;gap:6px;">
                <button onclick="(${onPage.toString()})(${meta.current_page - 1})"
                    ${prev ? '' : 'disabled'}
                    style="padding:6px 14px;border-radius:7px;border:1px solid #e2e8f0;
                        background:#fff;font-size:13px;cursor:${prev ? 'pointer' : 'not-allowed'};
                        color:${prev ? '#374151' : '#cbd5e1'};">← Prev</button>
                <button onclick="(${onPage.toString()})(${meta.current_page + 1})"
                    ${next ? '' : 'disabled'}
                    style="padding:6px 14px;border-radius:7px;border:1px solid #e2e8f0;
                        background:#fff;font-size:13px;cursor:${next ? 'pointer' : 'not-allowed'};
                        color:${next ? '#374151' : '#cbd5e1'};">Next →</button>
            </div>
        </div>
    `;
}

// ── Badge helpers ─────────────────────────────────────────────────────────────
export function badgeStatus(status) {
    return status === 'aktif'
        ? `<span class="badge badge--success">● Aktif</span>`
        : `<span class="badge badge--danger">● Nonaktif</span>`;
}

export function badgeKehadiran(status) {
    const map = {
        hadir:   `<span class="badge badge--success">Hadir</span>`,
        telat:   `<span class="badge badge--warning">Telat</span>`,
        izin:    `<span class="badge badge--info">Izin</span>`,
        alpa:    `<span class="badge badge--danger">Alpa</span>`,
        pending: `<span class="badge badge--neutral">Pending</span>`,
    };
    return map[status] ?? `<span class="badge badge--neutral">${esc(status)}</span>`;
}

export function badgeValidasi(status) {
    const map = {
        menunggu:  `<span class="badge badge--warning">Menunggu</span>`,
        disetujui: `<span class="badge badge--success">Disetujui</span>`,
        ditolak:   `<span class="badge badge--danger">Ditolak</span>`,
    };
    return map[status] ?? `<span class="badge badge--neutral">${esc(status)}</span>`;
}

export function badgeStatusIzin(status) {
    const map = {
        menunggu:  `<span class="badge badge--warning">Menunggu</span>`,
        disetujui: `<span class="badge badge--success">Disetujui</span>`,
        ditolak:   `<span class="badge badge--danger">Ditolak</span>`,
        kadaluarsa:`<span class="badge badge--neutral">Kadaluarsa</span>`,
    };
    return map[status] ?? `<span class="badge badge--neutral">${esc(status)}</span>`;
}

export function badgeLokasi(valid) {
    return valid
        ? `<span class="badge badge--success">✓ Valid</span>`
        : `<span class="badge badge--danger">✗ Tidak Valid</span>`;
}

// ── Shared modal CSS (inject sekali ke <head>) ────────────────────────────────
export function injectModalStyles() {
    if (document.getElementById('ao-modal-styles')) return;
    const s = document.createElement('style');
    s.id = 'ao-modal-styles';
    s.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);
            backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center;}
        .modal-overlay.modal--open{display:flex!important;}
        .modal-box{background:#fff;border-radius:16px;width:90%;max-width:540px;
            max-height:90vh;overflow-y:auto;
            box-shadow:0 24px 64px rgba(0,0,0,0.15);
            animation:aoSlideUp 0.22s cubic-bezier(0.16,1,0.3,1);}
        @keyframes aoSlideUp{
            from{transform:translateY(24px);opacity:0}
            to{transform:translateY(0);opacity:1}}
        .modal-header{display:flex;align-items:center;justify-content:space-between;
            padding:20px 24px 16px;border-bottom:1px solid #f1f5f9;}
        .modal-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;
            color:#0f172a;margin:0;}
        .modal-close{background:none;border:none;cursor:pointer;font-size:22px;
            color:#94a3b8;padding:0;width:28px;height:28px;
            display:flex;align-items:center;justify-content:center;border-radius:6px;}
        .modal-close:hover{background:#f1f5f9;color:#374151;}
        .modal-body{padding:20px 24px;}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;
            padding-top:20px;margin-top:4px;border-top:1px solid #f1f5f9;}
        .form-group{margin-bottom:16px;}
        .form-label{display:block;font-family:'DM Sans',sans-serif;font-size:11px;
            font-weight:600;color:#64748b;text-transform:uppercase;
            letter-spacing:0.07em;margin-bottom:6px;}
        .form-input{width:100%;padding:10px 14px;border:1px solid #e2e8f0;
            border-radius:9px;font-family:'DM Sans',sans-serif;font-size:13.5px;
            color:#0f172a;background:#f8fafc;outline:none;
            transition:border-color .15s,box-shadow .15s;box-sizing:border-box;}
        .form-input:focus{border-color:#f59e0b;
            box-shadow:0 0 0 3px rgba(245,158,11,0.12);background:#fff;}
        .form-error{display:none;font-size:12px;color:#ef4444;
            margin-top:4px;font-family:'DM Sans',sans-serif;}
        .btn-cancel{padding:9px 18px;border:1px solid #e2e8f0;border-radius:8px;
            background:#fff;font-family:'DM Sans',sans-serif;font-size:13px;
            font-weight:500;color:#475569;cursor:pointer;}
        .btn-cancel:hover{background:#f8fafc;}
        .btn-primary-sm{padding:9px 20px;border:none;border-radius:8px;
            background:linear-gradient(135deg,#f59e0b,#d97706);
            font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;
            color:#fff;cursor:pointer;}
        .btn-primary-sm:hover{opacity:0.9;}
        .btn-primary-sm:disabled{opacity:0.6;cursor:not-allowed;}
        .btn-approve{padding:7px 14px;border:none;border-radius:7px;
            background:#f0faf0;color:#1a6e1a;border:1px solid #bbecbb;
            font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;
            cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
        .btn-approve:hover{background:#dcf5dc;}
        .btn-reject{padding:7px 14px;border:none;border-radius:7px;
            background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;
            font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;
            cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
        .btn-reject:hover{background:#ffe4e6;}
        .btn-aksi{width:30px;height:30px;border-radius:7px;
            border:1px solid #e2e8f0;background:#fff;
            display:inline-flex;align-items:center;justify-content:center;
            cursor:pointer;color:#64748b;
            transition:background .15s,color .15s,border-color .15s;}
        .btn-aksi.btn-edit:hover{background:#eff6ff;color:#2563eb;border-color:#bfdbfe;}
        .btn-aksi.btn-hapus:hover{background:#fef2f2;color:#dc2626;border-color:#fecaca;}
        .btn-aksi.btn-view:hover{background:#fffbeb;color:#d97706;border-color:#fde68a;}
        .btn-aksi.btn-reset:hover{background:#f5f3ff;color:#7c3aed;border-color:#ede9fe;}
        .skel{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
            background-size:200% 100%;animation:aoShimmer 1.5s ease infinite;}
        @keyframes aoShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .ao-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;
            margin-top:16px;margin-bottom:4px;}
        .ao-search{padding:8px 14px;border:1px solid #e2e8f0;border-radius:8px;
            font-size:13px;font-family:'DM Sans',sans-serif;outline:none;width:240px;}
        .ao-search:focus{border-color:#f59e0b;box-shadow:0 0 0 2px rgba(245,158,11,0.1);}
        .ao-select{padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;
            font-size:13px;font-family:'DM Sans',sans-serif;outline:none;color:#374151;}
        .ao-select:focus{border-color:#f59e0b;}
        .catatan-box{width:100%;padding:10px 14px;border:1px solid #e2e8f0;
            border-radius:9px;font-family:'DM Sans',sans-serif;font-size:13px;
            color:#0f172a;background:#f8fafc;outline:none;resize:vertical;
            min-height:80px;box-sizing:border-box;}
        .catatan-box:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.12);}
    `;
    document.head.appendChild(s);
}