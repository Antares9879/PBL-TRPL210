/**
 * resources/js/hr/audit.js
 * 
 * Handle halaman Audit Log HR (F16)
 * Menampilkan riwayat aktivitas approve/reject dari Admin Outsource dan User Departemen
 */

// ── State Management ──────────────────────────────────────────────────────────
let currentPage = 1;
let perPage = 25;
let filters = {
    search: '',
    tanggal_dari: '',
    tanggal_sampai: '',
    aksi: '',
    jenis_data: '',
    role_pelaku: '',
};
let autoRefreshInterval = null;
let isModalOpen = false;

// ── DOM Elements ──────────────────────────────────────────────────────────────
const tbody = document.getElementById('tbody-audit-log');
const paginationWrap = document.getElementById('paginasi-audit');
const subtitleInfo = document.getElementById('subtitle-info-audit');
const perPageSelect = document.getElementById('per-page-audit');

// Filter inputs
const filterSearch = document.getElementById('filter-search-audit');
const filterTanggalDari = document.getElementById('filter-tanggal-dari-audit');
const filterTanggalSampai = document.getElementById('filter-tanggal-sampai-audit');
const filterAksi = document.getElementById('filter-aksi-audit');
const filterJenisData = document.getElementById('filter-jenis-data-audit');
const filterRolePelaku = document.getElementById('filter-role-pelaku-audit');

// Buttons
const btnTerapkanFilter = document.getElementById('btn-terapkan-filter-audit');
const btnResetFilter = document.getElementById('btn-reset-filter-audit');
const autoRefreshCheckbox = document.getElementById('auto-refresh-audit');
const refreshIndicator = document.getElementById('refresh-indicator');

// Tabs
const tabsAksi = document.querySelectorAll('#tabs-aksi-audit .hr-tab');

// Modal
const modalDetail = document.getElementById('modal-detail-audit');
const modalDetailBody = document.getElementById('modal-audit-body');
const btnTutupModal = document.getElementById('btn-tutup-modal-audit');

// Panel ringkasan
const panelRingkasan = document.getElementById('panel-ringkasan-audit');
const btnToggleRingkasan = document.getElementById('btn-toggle-ringkasan');
const btnTutupRingkasan = document.getElementById('btn-tutup-ringkasan');
const btnLoadRingkasan = document.getElementById('btn-load-ringkasan');
const filterBulanRingkasan = document.getElementById('filter-bulan-ringkasan');
const filterTahunRingkasan = document.getElementById('filter-tahun-ringkasan');

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initRingkasanFilters();
    loadAuditLog();
    attachEventListeners();
});

// ── Event Listeners ───────────────────────────────────────────────────────────
function attachEventListeners() {
    // Per page change
    perPageSelect?.addEventListener('change', (e) => {
        perPage = parseInt(e.target.value);
        currentPage = 1;
        loadAuditLog();
    });

    // Apply filter
    btnTerapkanFilter?.addEventListener('click', () => {
        applyFilters();
    });

    // Reset filter
    btnResetFilter?.addEventListener('click', () => {
        resetFilters();
    });

    // Enter key on search
    filterSearch?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    // Tabs aksi
    tabsAksi.forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active state
            tabsAksi.forEach(t => t.classList.remove('hr-tab--active'));
            tab.classList.add('hr-tab--active');
            
            // Apply filter
            filters.aksi = tab.dataset.aksi || '';
            currentPage = 1;
            loadAuditLog();
        });
    });

    // Auto refresh
    autoRefreshCheckbox?.addEventListener('change', (e) => {
        if (e.target.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });

    // Modal close
    btnTutupModal?.addEventListener('click', () => {
        closeModal();
    });

    // Close modal on overlay click
    modalDetail?.addEventListener('click', (e) => {
        if (e.target === modalDetail) {
            closeModal();
        }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isModalOpen) {
            closeModal();
        }
    });

    // Ringkasan panel
    btnToggleRingkasan?.addEventListener('click', () => {
        toggleRingkasanPanel();
    });

    btnTutupRingkasan?.addEventListener('click', () => {
        panelRingkasan.style.display = 'none';
    });

    btnLoadRingkasan?.addEventListener('click', () => {
        loadRingkasan();
    });
}

// ── Init Ringkasan Filters ────────────────────────────────────────────────────
function initRingkasanFilters() {
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();

    // Set bulan default
    if (filterBulanRingkasan) {
        filterBulanRingkasan.value = currentMonth;
    }

    // Populate tahun (5 tahun terakhir)
    if (filterTahunRingkasan) {
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (i === 0) option.selected = true;
            filterTahunRingkasan.appendChild(option);
        }
    }
}

// ── Toggle Ringkasan Panel ────────────────────────────────────────────────────
function toggleRingkasanPanel() {
    if (panelRingkasan.style.display === 'none' || !panelRingkasan.style.display) {
        panelRingkasan.style.display = 'block';
        loadRingkasan();
    } else {
        panelRingkasan.style.display = 'none';
    }
}

// ── Load Ringkasan ────────────────────────────────────────────────────────────
async function loadRingkasan() {
    const bulan = filterBulanRingkasan?.value || new Date().getMonth() + 1;
    const tahun = filterTahunRingkasan?.value || new Date().getFullYear();

    try {
        const response = await fetch(`/api/hr/audit/ringkasan?bulan=${bulan}&tahun=${tahun}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();

        if (result.status) {
            renderRingkasan(result.data);
        } else {
            showToast(result.message || 'Gagal memuat ringkasan', 'error');
        }
    } catch (error) {
        console.error('Error loading ringkasan:', error);
        showToast('Terjadi kesalahan saat memuat ringkasan', 'error');
    }
}

// ── Render Ringkasan ──────────────────────────────────────────────────────────
function renderRingkasan(data) {
    // Total cards
    document.getElementById('card-total-approve').querySelector('div:last-child').textContent = data.total.approve;
    document.getElementById('card-total-reject').querySelector('div:last-child').textContent = data.total.reject;
    document.getElementById('card-total-semua').querySelector('div:last-child').textContent = data.total.semua;

    // Per jenis data
    const jenisMap = {
        'absensi': { label: 'Absensi', color: '#3B82F6' },
        'lembur': { label: 'Lembur', color: '#F59E0B' },
        'izin': { label: 'Izin', color: '#8B5CF6' },
    };

    Object.keys(jenisMap).forEach(jenis => {
        const stat = data.per_jenis_data[jenis] || { approve: 0, reject: 0, total: 0 };
        const cardEl = document.getElementById(`card-stat-${jenis}`);
        
        if (cardEl) {
            cardEl.innerHTML = `
                <div class="hr-ringkasan-card" style="border-left-color: ${jenisMap[jenis].color}; background: ${jenisMap[jenis].color}15;">
                    <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                        ${jenisMap[jenis].label}
                    </div>
                    <div style="font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                        ${stat.total}
                    </div>
                    <div style="font-size: 12px; color: #64748b;">
                        <span style="color: #10B981; font-weight: 500;">${stat.approve} Approve</span> / 
                        <span style="color: #EF4444; font-weight: 500;">${stat.reject} Reject</span>
                    </div>
                </div>
            `;
        }
    });

    // Per role
    const roleMap = {
        'admin_outsource': 'Admin Outsource',
        'user_departemen': 'User Departemen',
    };

    const tbodyRole = document.getElementById('tbody-ringkasan-role');
    if (tbodyRole) {
        tbodyRole.innerHTML = Object.keys(roleMap).map(role => {
            const stat = data.per_role[role] || { approve: 0, reject: 0, total: 0 };
            return `
                <tr>
                    <td><strong>${roleMap[role]}</strong></td>
                    <td><span class="hr-badge-approve">${stat.approve}</span></td>
                    <td><span class="hr-badge-reject">${stat.reject}</span></td>
                    <td><strong>${stat.total}</strong></td>
                </tr>
            `;
        }).join('');
    }
}

// ── Load Audit Log ────────────────────────────────────────────────────────────
async function loadAuditLog() {
    try {
        // Build query params
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
            ...filters,
        });

        // Remove empty params
        for (const [key, value] of [...params.entries()]) {
            if (!value) params.delete(key);
        }

        const response = await fetch(`/api/hr/audit?${params.toString()}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();

        if (result.status) {
            renderTable(result.data.data);
            renderPagination(result.data);
            updateSubtitle(result.data);
        } else {
            showToast(result.message || 'Gagal memuat audit log', 'error');
            renderEmptyState('Gagal memuat data');
        }
    } catch (error) {
        console.error('Error loading audit log:', error);
        showToast('Terjadi kesalahan saat memuat data', 'error');
        renderEmptyState('Terjadi kesalahan');
    }
}

// ── Render Table ──────────────────────────────────────────────────────────────
function renderTable(logs) {
    if (!tbody) return;

    if (logs.length === 0) {
        renderEmptyState('Tidak ada aktivitas yang cocok dengan filter yang diterapkan');
        return;
    }

    tbody.innerHTML = logs.map(log => {
        const rowClass = log.aksi === 'approve' ? 'hr-baris-approve' : 
                        log.aksi === 'reject' ? 'hr-baris-reject' : '';
        
        return `
            <tr class="${rowClass}">
                <td>
                    <div style="font-size: 0.875rem; font-weight: 500;">${escapeHtml(log.waktu_aksi)}</div>
                    <span class="hr-waktu-relatif">${escapeHtml(log.waktu_relative)}</span>
                </td>
                <td>
                    <div style="font-weight: 500;">${escapeHtml(log.pengguna_nama)}</div>
                </td>
                <td>
                    <span class="badge badge--${getRoleBadgeClass(log.role_pelaku)}">
                        ${escapeHtml(log.role_label)}
                    </span>
                </td>
                <td>
                    <span class="hr-badge-${log.aksi}">
                        ${escapeHtml(log.aksi_label)}
                    </span>
                </td>
                <td>
                    <span class="badge badge--neutral">
                        ${escapeHtml(log.jenis_label)}
                    </span>
                </td>
                <td>
                    <code style="font-size: 11px; color: #64748b;">${log.id_referensi}</code>
                </td>
                <td>
                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(log.catatan)}
                    </div>
                </td>
                <td>
                    <button 
                        type="button" 
                        class="btn btn--sm btn--neutral"
                        onclick="window.showAuditDetail(${log.id})"
                        title="Lihat detail"
                    >
                        🔍
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// ── Render Empty State ────────────────────────────────────────────────────────
function renderEmptyState(message) {
    if (!tbody) return;
    tbody.innerHTML = `
        <tr>
            <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" 
                     style="width: 48px; height: 48px; margin: 0 auto 1rem; opacity: 0.5;">
                    <path stroke-linecap="round" stroke-linejoin="round" 
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div style="font-size: 1rem; font-weight: 500;">${message}</div>
            </td>
        </tr>
    `;
}

// ── Render Pagination ─────────────────────────────────────────────────────────
function renderPagination(data) {
    if (!paginationWrap) return;

    const currentPage = data.current_page;
    const lastPage = data.last_page;

    if (lastPage <= 1) {
        paginationWrap.innerHTML = '';
        return;
    }

    let pages = [];
    
    // Always show first page
    pages.push(1);
    
    // Calculate range around current page
    const rangeStart = Math.max(2, currentPage - 1);
    const rangeEnd = Math.min(lastPage - 1, currentPage + 1);
    
    // Add ellipsis after first page if needed
    if (rangeStart > 2) {
        pages.push('...');
    }
    
    // Add pages around current
    for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
    }
    
    // Add ellipsis before last page if needed
    if (rangeEnd < lastPage - 1) {
        pages.push('...');
    }
    
    // Always show last page if more than 1 page
    if (lastPage > 1) {
        pages.push(lastPage);
    }

    paginationWrap.innerHTML = `
        <div class="pagination">
            <button 
                class="pagination-btn ${currentPage === 1 ? 'pagination-btn--disabled' : ''}"
                ${currentPage === 1 ? 'disabled' : ''}
                onclick="window.goToPage(${currentPage - 1})"
            >
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            
            ${pages.map(page => {
                if (page === '...') {
                    return '<span class="pagination-ellipsis">...</span>';
                }
                return `
                    <button 
                        class="pagination-btn ${page === currentPage ? 'pagination-btn--active' : ''}"
                        onclick="window.goToPage(${page})"
                    >
                        ${page}
                    </button>
                `;
            }).join('')}
            
            <button 
                class="pagination-btn ${currentPage === lastPage ? 'pagination-btn--disabled' : ''}"
                ${currentPage === lastPage ? 'disabled' : ''}
                onclick="window.goToPage(${currentPage + 1})"
            >
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    `;
}

// ── Update Subtitle ───────────────────────────────────────────────────────────
function updateSubtitle(data) {
    if (!subtitleInfo) return;
    
    const from = data.from || 0;
    const to = data.to || 0;
    const total = data.total || 0;
    
    if (total === 0) {
        subtitleInfo.textContent = 'Tidak ada data';
    } else {
        subtitleInfo.textContent = `Menampilkan ${from} - ${to} dari ${total} entri`;
    }
}

// ── Go To Page ────────────────────────────────────────────────────────────────
window.goToPage = function(page) {
    currentPage = page;
    loadAuditLog();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ── Apply Filters ─────────────────────────────────────────────────────────────
function applyFilters() {
    filters = {
        search: filterSearch?.value.trim() || '',
        tanggal_dari: filterTanggalDari?.value || '',
        tanggal_sampai: filterTanggalSampai?.value || '',
        aksi: filterAksi?.value || '',
        jenis_data: filterJenisData?.value || '',
        role_pelaku: filterRolePelaku?.value || '',
    };
    
    currentPage = 1;
    loadAuditLog();
}

// ── Reset Filters ─────────────────────────────────────────────────────────────
function resetFilters() {
    if (filterSearch) filterSearch.value = '';
    if (filterTanggalDari) filterTanggalDari.value = '';
    if (filterTanggalSampai) filterTanggalSampai.value = '';
    if (filterAksi) filterAksi.value = '';
    if (filterJenisData) filterJenisData.value = '';
    if (filterRolePelaku) filterRolePelaku.value = '';
    
    filters = {
        search: '',
        tanggal_dari: '',
        tanggal_sampai: '',
        aksi: '',
        jenis_data: '',
        role_pelaku: '',
    };
    
    // Reset tabs
    tabsAksi.forEach(t => t.classList.remove('hr-tab--active'));
    tabsAksi[0]?.classList.add('hr-tab--active');
    
    currentPage = 1;
    loadAuditLog();
}

// ── Auto Refresh ──────────────────────────────────────────────────────────────
function startAutoRefresh() {
    if (refreshIndicator) {
        refreshIndicator.style.display = 'inline-flex';
    }
    
    autoRefreshInterval = setInterval(() => {
        if (!isModalOpen) {
            loadAuditLog();
        }
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (refreshIndicator) {
        refreshIndicator.style.display = 'none';
    }
    
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// ── Show Audit Detail ─────────────────────────────────────────────────────────
window.showAuditDetail = async function(id) {
    if (!modalDetail || !modalDetailBody) return;
    
    // Show modal with loading state
    modalDetail.style.display = 'flex';
    isModalOpen = true;
    modalDetailBody.innerHTML = `
        <div class="skeleton-wrap">
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--medium"></div>
            <div class="skeleton-line"></div>
        </div>
    `;
    
    try {
        const response = await fetch(`/api/hr/audit/${id}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();
        
        if (result.status) {
            renderDetailModal(result.data);
        } else {
            showToast(result.message || 'Gagal memuat detail', 'error');
            closeModal();
        }
    } catch (error) {
        console.error('Error loading audit detail:', error);
        showToast('Terjadi kesalahan saat memuat detail', 'error');
        closeModal();
    }
};

// ── Render Detail Modal ───────────────────────────────────────────────────────
function renderDetailModal(log) {
    if (!modalDetailBody) return;
    
    const hasChanges = log.data_sebelum || log.data_sesudah;
    
    modalDetailBody.innerHTML = `
        <div style="background: var(--hr-hijau-bg); border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="hr-badge-${log.aksi}" style="font-size: 13px; padding: 6px 14px;">
                    ${escapeHtml(log.aksi_label)}
                </span>
                <span class="badge badge--neutral">
                    ${escapeHtml(log.jenis_label)}
                </span>
            </div>
            <div style="font-size: 14px; color: #64748b;">
                ${escapeHtml(log.waktu_aksi_lengkap || log.waktu_aksi)} — ${escapeHtml(log.waktu_relative)}
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
            <div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Pelaku
                </div>
                <div style="font-weight: 500; margin-bottom: 4px;">${escapeHtml(log.pengguna_nama)}</div>
                ${log.pengguna_email ? `<div style="font-size: 12px; color: #64748b;">${escapeHtml(log.pengguna_email)}</div>` : ''}
            </div>
            
            <div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Role
                </div>
                <span class="badge badge--${getRoleBadgeClass(log.role_pelaku)}">
                    ${escapeHtml(log.role_label)}
                </span>
            </div>
            
            <div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    ID Referensi
                </div>
                <code style="font-size: 13px; color: #0f172a;">${log.id_referensi}</code>
            </div>
            
            ${log.ip_address ? `
                <div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                        IP Address
                    </div>
                    <code style="font-size: 13px; color: #0f172a;">${escapeHtml(log.ip_address)}</code>
                </div>
            ` : ''}
        </div>

        ${log.catatan && log.catatan !== '—' ? `
            <div style="margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Catatan
                </div>
                <div style="background: #f8fafc; border-radius: 6px; padding: 12px; font-size: 13px; color: #374151;">
                    ${escapeHtml(log.catatan)}
                </div>
            </div>
        ` : ''}
        
        ${hasChanges ? `
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <h4 style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: #0f172a;">Perubahan Data</h4>
                ${renderDiffTable(log.data_sebelum, log.data_sesudah)}
            </div>
        ` : `
            <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 13px;">
                Tidak ada data perubahan
            </div>
        `}
    `;
}

// ── Render Diff Table ─────────────────────────────────────────────────────────
function renderDiffTable(dataBefore, dataAfter) {
    // Jika tidak ada data sama sekali
    if (!dataBefore && !dataAfter) {
        return '<div style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data perubahan</div>';
    }

    // Flatten nested objects untuk perbandingan
    const flatBefore = flattenObject(dataBefore || {});
    const flatAfter = flattenObject(dataAfter || {});

    // Gabungkan semua keys
    const allKeys = new Set([...Object.keys(flatBefore), ...Object.keys(flatAfter)]);
    
    // Filter keys yang tidak relevan atau terlalu teknis
    const filteredKeys = Array.from(allKeys).filter(key => {
        // Skip keys yang terlalu teknis
        const skipKeys = ['created_at', 'updated_at', 'deleted_at', 'id_pengguna', 'id_karyawan'];
        return !skipKeys.includes(key);
    });

    if (filteredKeys.length === 0) {
        return '<div style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada perubahan yang signifikan</div>';
    }

    // Filter hanya keys yang berubah
    const changedKeys = filteredKeys.filter(key => {
        const valueBefore = flatBefore[key];
        const valueAfter = flatAfter[key];
        const normalizedBefore = normalizeValue(valueBefore);
        const normalizedAfter = normalizeValue(valueAfter);
        return normalizedBefore !== normalizedAfter;
    });

    // Jika tidak ada perubahan
    if (changedKeys.length === 0) {
        return `
            <div style="text-align: center; padding: 40px; background: #F0FDF4; border-radius: 8px; border: 1px solid #BBF7D0;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" 
                     style="width: 48px; height: 48px; margin: 0 auto 12px; color: #10B981;">
                    <path stroke-linecap="round" stroke-linejoin="round" 
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="font-size: 14px; font-weight: 500; color: #14532D; margin-bottom: 4px;">
                    Tidak ada perubahan data dari sebelumnya
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    Semua field memiliki nilai yang sama
                </div>
            </div>
        `;
    }

    // Buat tabel perbandingan hanya untuk data yang berubah
    let tableHTML = `
        <div style="overflow-x: auto;">
            <table class="hr-diff-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Field</th>
                        <th style="width: 35%;">Data Sebelumnya</th>
                        <th style="width: 35%;">Data Sesudah</th>
                    </tr>
                </thead>
                <tbody>
    `;

    changedKeys.forEach(key => {
        const valueBefore = flatBefore[key];
        const valueAfter = flatAfter[key];
        
        // Format field name menjadi lebih readable
        const fieldName = formatFieldName(key);
        
        // Jika data sesudah kosong/null, tampilkan data sebelumnya
        const effectiveAfter = (valueAfter === null || valueAfter === undefined) ? valueBefore : valueAfter;
        
        // Format values
        const displayBefore = formatValue(valueBefore);
        const displayAfter = formatValue(effectiveAfter);
        
        // Tentukan class untuk highlight perubahan
        const cellBeforeClass = valueBefore !== undefined && valueBefore !== null ? 'hr-diff-cell-old' : '';
        const cellAfterClass = valueAfter !== undefined && valueAfter !== null ? 'hr-diff-cell-new' : '';
        
        tableHTML += `
            <tr class="hr-diff-row-changed">
                <td class="hr-diff-field">
                    <strong>${escapeHtml(fieldName)}</strong>
                </td>
                <td class="${cellBeforeClass}">
                    ${displayBefore}
                </td>
                <td class="${cellAfterClass}">
                    ${displayAfter}
                </td>
            </tr>
        `;
    });

    tableHTML += `
                </tbody>
            </table>
        </div>
    `;

    return tableHTML;
}

// ── Flatten Object ────────────────────────────────────────────────────────────
function flattenObject(obj, prefix = '') {
    const flattened = {};
    
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            const value = obj[key];
            const newKey = prefix ? `${prefix}.${key}` : key;
            
            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                // Rekursif untuk nested objects
                Object.assign(flattened, flattenObject(value, newKey));
            } else {
                flattened[newKey] = value;
            }
        }
    }
    
    return flattened;
}

// ── Format Field Name ─────────────────────────────────────────────────────────
function formatFieldName(key) {
    // Mapping field names ke bahasa Indonesia yang lebih user-friendly
    const fieldMap = {
        'status': 'Status',
        'status_kehadiran': 'Status Kehadiran',
        'status_validasi': 'Status Validasi',
        'alasan_lembur': 'Alasan Lembur',
        'durasi_lembur_menit': 'Durasi Lembur (Menit)',
        'durasi_lembur_resmi': 'Durasi Lembur Resmi (Menit)',
        'waktu_mulai': 'Waktu Mulai',
        'waktu_selesai': 'Waktu Selesai',
        'tanggal_lembur': 'Tanggal Lembur',
        'tanggal_izin': 'Tanggal Izin',
        'tanggal_mulai': 'Tanggal Mulai',
        'tanggal_selesai': 'Tanggal Selesai',
        'jenis_izin': 'Jenis Izin',
        'alasan': 'Alasan',
        'catatan_validasi': 'Catatan Validasi',
        'divalidasi_oleh': 'Divalidasi Oleh',
        'waktu_validasi': 'Waktu Validasi',
        'nama_lengkap': 'Nama Lengkap',
        'email': 'Email',
        'role': 'Role',
        'is_active': 'Status Aktif',
        'latitude': 'Latitude',
        'longitude': 'Longitude',
        'waktu_check_in': 'Waktu Check In',
        'waktu_check_out': 'Waktu Check Out',
        'menit_telat': 'Menit Terlambat',
        'menit_kerja_normal': 'Menit Kerja Normal',
        'menit_lembur_pending': 'Menit Lembur Pending',
    };
    
    // Cek apakah ada mapping
    if (fieldMap[key]) {
        return fieldMap[key];
    }
    
    // Jika nested (ada titik), ambil bagian terakhir
    const parts = key.split('.');
    const lastPart = parts[parts.length - 1];
    
    // Convert snake_case ke Title Case
    return lastPart
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// ── Normalize Value ───────────────────────────────────────────────────────────
function normalizeValue(value) {
    // Null dan undefined dianggap sama
    if (value === null || value === undefined) {
        return null;
    }
    
    // Convert ke string untuk perbandingan
    return String(value).trim();
}

// ── Format Value ──────────────────────────────────────────────────────────────
function formatValue(value) {
    // Null atau undefined
    if (value === null || value === undefined) {
        return '<span style="color: #94a3b8; font-style: italic;">(Kosong)</span>';
    }
    
    // Boolean
    if (typeof value === 'boolean') {
        return value 
            ? '<span style="color: #10B981; font-weight: 500;">✓ Ya</span>' 
            : '<span style="color: #EF4444; font-weight: 500;">✗ Tidak</span>';
    }
    
    // Number
    if (typeof value === 'number') {
        return `<span style="font-weight: 500;">${value}</span>`;
    }
    
    // Array
    if (Array.isArray(value)) {
        if (value.length === 0) {
            return '<span style="color: #94a3b8; font-style: italic;">(Kosong)</span>';
        }
        return `<span style="color: #64748b;">${value.join(', ')}</span>`;
    }
    
    // String - cek apakah status
    const strValue = String(value);
    
    // Format status dengan badge
    if (strValue === 'menunggu' || strValue === 'pending') {
        return '<span class="hr-badge-pending">Menunggu</span>';
    }
    if (strValue === 'disetujui' || strValue === 'approved') {
        return '<span class="hr-badge-approve">Disetujui</span>';
    }
    if (strValue === 'ditolak' || strValue === 'rejected') {
        return '<span class="hr-badge-reject">Ditolak</span>';
    }
    if (strValue === 'hadir') {
        return '<span class="hr-badge-hadir">Hadir</span>';
    }
    if (strValue === 'izin') {
        return '<span class="hr-badge-izin">Izin</span>';
    }
    if (strValue === 'alpa') {
        return '<span class="hr-badge-alpa">Alpa</span>';
    }
    
    // Cek apakah tanggal/waktu ISO format (2026-05-19T13:43:43.000000Z)
    const isoDateMatch = strValue.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/);
    if (isoDateMatch) {
        const [, year, month, day, hour, minute, second] = isoDateMatch;
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthName = monthNames[parseInt(month) - 1];
        const formattedDate = `${day} ${monthName} ${year}, ${hour}:${minute}`;
        return `<span style="color: #3B82F6; font-weight: 500;">${formattedDate}</span>`;
    }
    
    // Cek apakah tanggal format YYYY-MM-DD
    const dateMatch = strValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (dateMatch) {
        const [, year, month, day] = dateMatch;
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const monthName = monthNames[parseInt(month) - 1];
        const formattedDate = `${day} ${monthName} ${year}`;
        return `<span style="color: #3B82F6; font-weight: 500;">${formattedDate}</span>`;
    }
    
    // Cek apakah waktu format HH:MM:SS atau HH:MM
    const timeMatch = strValue.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (timeMatch) {
        const [, hour, minute, second] = timeMatch;
        const formattedTime = second ? `${hour}:${minute}:${second}` : `${hour}:${minute}`;
        return `<span style="color: #3B82F6; font-weight: 500;">${formattedTime}</span>`;
    }
    
    // String biasa
    if (strValue.length > 100) {
        return `<div style="max-height: 100px; overflow-y: auto; font-size: 12px; color: #374151;">${escapeHtml(strValue)}</div>`;
    }
    
    return `<span style="color: #374151;">${escapeHtml(strValue)}</span>`;
}

// ── Close Modal ───────────────────────────────────────────────────────────────
function closeModal() {
    if (modalDetail) {
        modalDetail.style.display = 'none';
    }
    isModalOpen = false;
}

// ── Helper Functions ──────────────────────────────────────────────────────────
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getRoleBadgeClass(role) {
    const roleMap = {
        'super_admin': 'danger',
        'admin_outsource': 'warning',
        'user_departemen': 'info',
        'hr': 'success',
        'karyawan': 'info',
        'sistem': 'neutral',
    };
    return roleMap[role] || 'neutral';
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `hr-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
