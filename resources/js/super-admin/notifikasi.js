/**
 * resources/js/super-admin/notifikasi.js
 * Fitur Notifikasi In-App — Role Super Admin
 * E-Outsourcing PT Ecogreen Oleochemicals
 */

import { apiFetch, toast } from './_utils.js';

// ═══════════════════════════════════════════════════════════════════════════
//  STATE & CONFIG
// ═══════════════════════════════════════════════════════════════════════════

const STATE = {
    panelOpen: false,
    pollingInterval: null,
    currentPage: 1,
    currentFilter: '',
};

const CONFIG = {
    POLLING_INTERVAL: 30000, // 30 detik
    PANEL_PER_PAGE: 4,
    HALAMAN_PER_PAGE: 25,
};

// ═══════════════════════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════════════════════

function init() {
    console.log('[Notifikasi Super Admin] Init function called');
    
    // Selector bertingkat karena tombol lonceng tidak memiliki id khusus
    const btnNotif = document.querySelector('.topbar-icon-btn[aria-label="Notifikasi"]')
                  || document.querySelector('.topbar-right .topbar-icon-btn');

    console.log('[Notifikasi Super Admin] Button element:', btnNotif);

    if (!btnNotif) {
        console.warn('[Notifikasi Super Admin] Tombol notifikasi tidak ditemukan');
        return; // guard: jangan jalankan jika elemen tidak ada
    }

    console.log('[Notifikasi Super Admin] Init berhasil, tombol notifikasi ditemukan');
    
    // Debug: cek panel overlay ada atau tidak
    const overlay = document.getElementById('notif-panel-overlay');
    console.log('[Notifikasi Super Admin] Panel overlay element:', overlay);

    // Set URL "Lihat semua" berdasarkan role (untuk Super Admin)
    const seeAllLink = document.getElementById('notif-see-all-link');
    if (seeAllLink) {
        seeAllLink.href = '/super-admin/notifikasi';
    }

    // Event listener: buka panel overlay
    btnNotif.addEventListener('click', (e) => {
        e.stopPropagation();
        openPanel();
    });

    // Event listener: tutup panel via backdrop
    const backdrop = document.getElementById('notif-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', closePanel);
    } else {
        console.warn('[Notifikasi Super Admin] Backdrop tidak ditemukan');
    }

    // Event listener: tutup panel via tombol X
    const btnTutup = document.getElementById('btn-tutup-notif-panel');
    if (btnTutup) {
        btnTutup.addEventListener('click', closePanel);
    } else {
        console.warn('[Notifikasi Super Admin] Tombol tutup panel tidak ditemukan');
    }

    // Event listener: tandai semua dibaca di panel
    const btnTandaiSemua = document.getElementById('btn-tandai-semua-baca');
    if (btnTandaiSemua) {
        btnTandaiSemua.addEventListener('click', tandaiSemuaBaca);
    } else {
        console.warn('[Notifikasi Super Admin] Tombol tandai semua tidak ditemukan');
    }

    // Event listener: ESC key untuk tutup panel
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && STATE.panelOpen) {
            closePanel();
        }
    });

    // Fetch badge count pertama kali
    fetchBadgeCount();

    // Start polling badge count
    startPolling();

    // Jika halaman ini adalah halaman penuh notifikasi
    if (window.location.pathname === '/super-admin/notifikasi') {
        initHalamanPenuh();
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  BADGE COUNT
// ═══════════════════════════════════════════════════════════════════════════

async function fetchBadgeCount() {
    try {
        const res = await apiFetch('/api/notifikasi/jumlah-baru');
        const json = await res.json();
        
        if (json.status && json.data) {
            updateBadge(json.data.jumlah_belum_dibaca || 0);
        }
    } catch (err) {
        console.error('[Notifikasi Super Admin] Gagal fetch badge count:', err);
    }
}

function updateBadge(jumlah) {
    // Update badge di topbar menggunakan class selector (bukan id untuk avoid conflict)
    const dot = document.querySelector('.topbar-notif-dot');
    // Update badge di sidebar (optional, jika ada)
    const sidebarBadge = document.getElementById('sidebar-notif-badge-sa');
    
    if (jumlah > 0) {
        if (dot) {
            dot.style.display = 'block';
            dot.textContent = jumlah > 99 ? '99+' : jumlah;
        }
        if (sidebarBadge) {
            sidebarBadge.style.display = 'inline-flex';
            sidebarBadge.textContent = jumlah;
        }
    } else {
        if (dot) dot.style.display = 'none';
        if (sidebarBadge) sidebarBadge.style.display = 'none';
    }
}

function startPolling() {
    if (STATE.pollingInterval) clearInterval(STATE.pollingInterval);
    STATE.pollingInterval = setInterval(fetchBadgeCount, CONFIG.POLLING_INTERVAL);
}

// ═══════════════════════════════════════════════════════════════════════════
//  PANEL OVERLAY
// ═══════════════════════════════════════════════════════════════════════════

function openPanel() {
    const overlay = document.getElementById('notif-panel-overlay');
    if (!overlay) {
        console.error('[Notifikasi Super Admin] Panel overlay tidak ditemukan. Pastikan kondisi @if di layouts/app.blade.php sudah benar dan role adalah super_admin');
        return;
    }

    console.log('[Notifikasi Super Admin] Membuka panel overlay');
    
    overlay.classList.add('app-notif--open');
    overlay.setAttribute('aria-hidden', 'false');
    STATE.panelOpen = true;

    // Fetch data notifikasi panel (4 terbaru)
    fetchPanelNotifikasi();
}

function closePanel() {
    const overlay = document.getElementById('notif-panel-overlay');
    if (!overlay) return;

    overlay.classList.remove('app-notif--open');
    overlay.setAttribute('aria-hidden', 'true');
    STATE.panelOpen = false;
}

async function fetchPanelNotifikasi() {
    const listContainer = document.getElementById('notif-panel-list');
    if (!listContainer) {
        console.warn('[Notifikasi Super Admin] Element #notif-panel-list tidak ditemukan');
        return;
    }

    // Loading state
    listContainer.innerHTML = renderSkeletonPanel();

    try {
        const res = await apiFetch(`/api/notifikasi?per_page=${CONFIG.PANEL_PER_PAGE}`);
        const json = await res.json();
        
        if (json.status && json.data) {
            // Cek apakah data.data ada dan merupakan array
            const items = json.data.data || [];
            
            if (items.length === 0) {
                listContainer.innerHTML = renderEmptyPanel();
            } else {
                renderPanelItems(items);
            }
        } else {
            listContainer.innerHTML = `<div style="padding:20px;text-align:center;color:#64748b;font-size:13px;">Gagal memuat notifikasi</div>`;
        }
    } catch (err) {
        console.error('[Notifikasi Super Admin] Gagal fetch panel notifikasi:', err);
        listContainer.innerHTML = `<div style="padding:20px;text-align:center;color:#ef4444;font-size:13px;">Terjadi kesalahan saat memuat notifikasi</div>`;
    }
}

function renderPanelItems(items) {
    const listContainer = document.getElementById('notif-panel-list');
    if (!listContainer) return;

    listContainer.innerHTML = items.map(item => {
        const isUnread = !item.is_dibaca;
        const ikon = getIkonSvg(item.jenis);
        const waktu = formatWaktuRelatif(item.created_at);
        const url = getNotifikasiUrl(item.jenis, item.id_referensi);

        return `
            <div class="app-notif-item ${isUnread ? 'app-notif-item--unread' : ''}"
                 data-id="${item.id_notifikasi}"
                 data-url="${escapeHtml(url)}"
                 role="listitem">
                <div class="app-notif-icon">
                    ${ikon}
                </div>
                <div class="app-notif-content">
                    <div class="app-notif-title">${escapeHtml(item.judul)}</div>
                    <div class="app-notif-time">${escapeHtml(waktu)}</div>
                </div>
                ${isUnread ? '<div class="app-notif-dot"></div>' : ''}
            </div>
        `;
    }).join('');

    // Event listener untuk klik item
    listContainer.querySelectorAll('.app-notif-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const url = this.dataset.url;
            tandaiBaca(id); // fire-and-forget
            window.location.href = url;
        });
    });
}

function renderSkeletonPanel() {
    return Array(3).fill(0).map(() => `
        <div class="app-notif-item" style="pointer-events:none;">
            <div class="skeleton-line" style="width:36px;height:36px;flex-shrink:0;border-radius:8px;"></div>
            <div style="flex:1;">
                <div class="skeleton-line" style="width:80%;height:14px;margin-bottom:6px;"></div>
                <div class="skeleton-line" style="width:50%;height:12px;"></div>
            </div>
        </div>
    `).join('');
}

function renderEmptyPanel() {
    return `
        <div style="padding:40px 20px;text-align:center;">
            <svg style="width:48px;height:48px;margin:0 auto 12px;color:#cbd5e1;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
            </svg>
            <div style="font-size:14px;color:#64748b;">Tidak ada notifikasi</div>
        </div>
    `;
}

// ═══════════════════════════════════════════════════════════════════════════
//  TANDAI BACA
// ═══════════════════════════════════════════════════════════════════════════

async function tandaiBaca(id) {
    try {
        await apiFetch(`/api/notifikasi/${id}/baca`, { method: 'PATCH' });
        // Berhasil, update badge
        fetchBadgeCount();
    } catch (err) {
        console.error('[Notifikasi Super Admin] Gagal tandai baca:', err);
    }
}

async function tandaiSemuaBaca() {
    const btn = document.getElementById('btn-tandai-semua-baca');
    if (btn) btn.disabled = true;

    try {
        const res = await apiFetch('/api/notifikasi/baca-semua', { method: 'PATCH' });
        const json = await res.json();
        
        if (json.status) {
            // Refresh panel
            fetchPanelNotifikasi();
            // Update badge
            fetchBadgeCount();
            toast('Semua notifikasi ditandai sebagai dibaca', 'success');
        }
    } catch (err) {
        console.error('[Notifikasi Super Admin] Gagal tandai semua baca:', err);
        toast('Gagal menandai semua notifikasi', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  HALAMAN PENUH
// ═══════════════════════════════════════════════════════════════════════════

function initHalamanPenuh() {
    // Event listener: tab filter
    document.querySelectorAll('.app-notif-page-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.app-notif-page-tab').forEach(t => t.classList.remove('app-notif-page-tab--active'));
            this.classList.add('app-notif-page-tab--active');

            // Fetch dengan filter
            const filter = this.dataset.filter || '';
            STATE.currentFilter = filter;
            STATE.currentPage = 1;
            fetchHalamanPenuh(1, filter);
        });
    });

    // Event listener: tandai semua dibaca di halaman penuh
    const btnTandaiSemuaHalaman = document.getElementById('btn-tandai-semua-halaman');
    if (btnTandaiSemuaHalaman) {
        btnTandaiSemuaHalaman.addEventListener('click', async function() {
            this.disabled = true;
            try {
                const res = await apiFetch('/api/notifikasi/baca-semua', { method: 'PATCH' });
                const json = await res.json();
                
                if (json.status) {
                    fetchHalamanPenuh(STATE.currentPage, STATE.currentFilter);
                    fetchBadgeCount();
                    toast('Semua notifikasi ditandai sebagai dibaca', 'success');
                }
            } catch (err) {
                console.error('[Notifikasi Super Admin] Gagal tandai semua:', err);
                toast('Gagal menandai semua notifikasi', 'error');
            } finally {
                this.disabled = false;
            }
        });
    }

    // Fetch data pertama kali
    fetchHalamanPenuh(1, '');
}

async function fetchHalamanPenuh(page = 1, filter = '') {
    const listContainer = document.getElementById('notif-halaman-list');
    if (!listContainer) return;

    // Loading state
    listContainer.innerHTML = Array(5).fill(0).map(() => `
        <div class="app-notif-item" style="display:flex;gap:12px;padding:12px 20px;">
            <div class="skeleton-line" style="width:36px;height:36px;flex-shrink:0;border-radius:8px;"></div>
            <div style="flex:1;">
                <div class="skeleton-line" style="width:80%;height:14px;margin-bottom:6px;"></div>
                <div class="skeleton-line" style="width:50%;height:12px;"></div>
            </div>
        </div>
    `).join('');

    let url = `/api/notifikasi?per_page=${CONFIG.HALAMAN_PER_PAGE}&page=${page}`;
    if (filter !== '') {
        url += `&is_dibaca=${filter}`;
    }

    try {
        const res = await apiFetch(url);
        const json = await res.json();
        
        if (json.status && json.data) {
            renderHalamanPenuh(json.data);
        } else {
            listContainer.innerHTML = `<div style="padding:40px 20px;text-align:center;color:#64748b;">Gagal memuat notifikasi</div>`;
        }
    } catch (err) {
        console.error('[Notifikasi Super Admin] Gagal fetch halaman penuh:', err);
        listContainer.innerHTML = `<div style="padding:40px 20px;text-align:center;color:#ef4444;">Terjadi kesalahan</div>`;
    }
}

function renderHalamanPenuh(data) {
    const listContainer = document.getElementById('notif-halaman-list');
    if (!listContainer) return;

    if (data.data.length === 0) {
        listContainer.innerHTML = `
            <div style="padding:60px 20px;text-align:center;">
                <svg style="width:64px;height:64px;margin:0 auto 16px;color:#cbd5e1;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                </svg>
                <div style="font-size:15px;color:#64748b;font-weight:500;">Tidak ada notifikasi</div>
            </div>
        `;
        document.getElementById('paginasi-notif-halaman').innerHTML = '';
        return;
    }

    listContainer.innerHTML = data.data.map(item => {
        const isUnread = !item.is_dibaca;
        const ikon = getIkonSvg(item.jenis);
        const waktu = formatWaktuRelatif(item.created_at);
        const url = getNotifikasiUrl(item.jenis, item.id_referensi);

        return `
            <div class="app-notif-item ${isUnread ? 'app-notif-item--unread' : ''}"
                 data-id="${item.id_notifikasi}"
                 data-url="${escapeHtml(url)}"
                 role="listitem"
                 style="display:flex;gap:12px;padding:12px 20px;cursor:pointer;border-bottom:1px solid var(--surface-border);transition:background 0.15s;">
                <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${getIkonColor(item.jenis, true)};color:${getIkonColor(item.jenis, false)};">
                    ${ikon}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:500;color:#0f172a;margin-bottom:4px;">${escapeHtml(item.judul)}</div>
                    <div style="font-size:12px;color:#64748b;">${escapeHtml(item.isi || '')}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:6px;">${escapeHtml(waktu)}</div>
                </div>
                ${isUnread ? '<div style="width:8px;height:8px;border-radius:50%;background:#ef4444;flex-shrink:0;margin-top:6px;"></div>' : ''}
            </div>
        `;
    }).join('');

    // Event listener untuk klik item
    listContainer.querySelectorAll('.app-notif-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const url = this.dataset.url;
            tandaiBaca(id); // fire-and-forget
            window.location.href = url;
        });
    });

    // Render paginasi
    renderPaginasiHalaman(data);
}

function renderPaginasiHalaman(meta) {
    const container = document.getElementById('paginasi-notif-halaman');
    if (!container) return;

    const currentPage = meta.current_page;
    const lastPage = meta.last_page;

    if (lastPage <= 1) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 20px;">
            <div style="font-size:13px;color:#64748b;">
                Halaman ${currentPage} dari ${lastPage}
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <button class="paginasi-btn" ${currentPage <= 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
                    Sebelumnya
                </button>
                <button class="paginasi-btn" ${currentPage >= lastPage ? 'disabled' : ''} data-page="${currentPage + 1}">
                    Berikutnya
                </button>
            </div>
        </div>
    `;

    // Event listener untuk tombol paginasi
    container.querySelectorAll('.paginasi-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            const page = parseInt(this.dataset.page);
            STATE.currentPage = page;
            fetchHalamanPenuh(page, STATE.currentFilter);
        });
    });
}

// ═══════════════════════════════════════════════════════════════════════════
//  HELPERS — Super Admin specific mappings
// ═══════════════════════════════════════════════════════════════════════════

function getNotifikasiUrl(jenis, id_referensi) {
    const map = {
        'akun'        : '/super-admin/akun',
        'konfigurasi' : '/super-admin/konfigurasi-area',
        'master_data' : '/super-admin/master-data/perusahaan',
        'auth'        : '/super-admin/audit-log',
        'sistem'      : '/super-admin/audit-log',
        'absensi'     : '/super-admin/audit-log',
        'lembur'      : '/super-admin/audit-log',
        'izin'        : '/super-admin/audit-log',
        'planning'    : '/super-admin/audit-log',
    };
    return map[jenis] || '/super-admin/dashboard';
}

function getIkonSvg(jenis) {
    const svgMap = {
        'akun': `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm-4 7a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/>
        </svg>`,
        'konfigurasi': `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
        </svg>`,
        'master_data': `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
        </svg>`,
        'auth': `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>`,
        'sistem': `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>`,
    };

    return svgMap[jenis] || `<svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
    </svg>`;
}

function getIkonColor(jenis, isBackground) {
    const colorMap = {
        'akun'        : { bg: '#eff6ff', fg: '#2563eb' },
        'konfigurasi' : { bg: '#f5f3ff', fg: '#7c3aed' },
        'master_data' : { bg: '#f0fdf4', fg: '#16a34a' },
        'auth'        : { bg: '#eff6ff', fg: '#3b82f6' },
        'sistem'      : { bg: '#f8fafc', fg: '#64748b' },
    };
    const color = colorMap[jenis] || { bg: '#f8fafc', fg: '#64748b' };
    return isBackground ? color.bg : color.fg;
}

function formatWaktuRelatif(isoString) {
    if (!isoString) return '';

    const now = new Date();
    const date = new Date(isoString);
    const diffMs = now - date;
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMinutes < 1) return 'Baru saja';
    if (diffMinutes < 60) return `${diffMinutes} menit yang lalu`;
    if (diffHours < 24) return `${diffHours} jam yang lalu`;
    if (diffDays < 7) return `${diffDays} hari yang lalu`;

    // Format DD MMM YYYY (Bahasa Indonesia)
    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    const dd = date.getDate();
    const mmm = bulan[date.getMonth()];
    const yyyy = date.getFullYear();

    return `${dd} ${mmm} ${yyyy}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ═══════════════════════════════════════════════════════════════════════════
//  AUTO-INIT
// ═══════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', init);
