/**
 * resources/js/user-departemen/_utils.js
 * Shared utilities untuk semua halaman User Departemen.
 *
 * Pola identik dengan admin-outsource/_utils.js, disesuaikan:
 *   - Warna accent: teal (bukan amber)
 *   - Badge tambahan: badgeLembur
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
    if (s.includes('T') || s.includes(' ')) return s.slice(s.indexOf('T') >= 0 ? s.indexOf('T') + 1 : 11, (s.indexOf('T') >= 0 ? s.indexOf('T') + 1 : 11) + 5);
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
        success: { bg: '#f0fdfa', border: '#99f6e4', text: '#0f766e',  icon: '✓' },
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
        <div class="paginasi-wrap">
            <span class="paginasi-info">
                Halaman ${meta.current_page} dari ${meta.last_page}
                &nbsp;·&nbsp; Total ${meta.total} data
            </span>
            <div class="paginasi-buttons">
                <button onclick="(${onPage.toString()})(${meta.current_page - 1})"
                    ${prev ? '' : 'disabled'}
                    class="paginasi-btn">← Prev</button>
                <button onclick="(${onPage.toString()})(${meta.current_page + 1})"
                    ${next ? '' : 'disabled'}
                    class="paginasi-btn">Next →</button>
            </div>
        </div>
    `;
}

// ── Badge helpers ─────────────────────────────────────────────────────────────
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

export function badgeLembur(status) {
    const map = {
        menunggu:   `<span class="lembur-badge lembur-badge--menunggu">Menunggu</span>`,
        disetujui:  `<span class="lembur-badge lembur-badge--disetujui">Disetujui</span>`,
        ditolak:    `<span class="lembur-badge lembur-badge--ditolak">Ditolak</span>`,
        kadaluarsa: `<span class="lembur-badge lembur-badge--kadaluarsa">Kadaluarsa</span>`,
    };
    return map[status] ?? `<span class="lembur-badge lembur-badge--menunggu">${esc(status)}</span>`;
}

// ── Shared modal CSS (inject sekali ke <head>) ────────────────────────────────
export function injectModalStyles() {
    if (document.getElementById('dept-modal-styles')) return;
    const s = document.createElement('style');
    s.id = 'dept-modal-styles';
    s.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);
            backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center;}
        .modal-overlay.modal--open{display:flex!important;}
        .skel{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
            background-size:200% 100%;animation:deptShimmer 1.5s ease infinite;}
        @keyframes deptShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    `;
    document.head.appendChild(s);
}
