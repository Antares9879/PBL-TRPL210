/**
 * resources/js/super-admin/audit-log.js
 *
 * Handle halaman audit log dengan pagination, filter, dan detail modal
 */

import { apiFetch, toast } from './_utils';

// ── State Management ──────────────────────────────────────────────────────────
let currentPage = 1;
let perPage = 25;
let filters = {
    search: '',
    tanggal_dari: '',
    tanggal_sampai: '',
    aksi: '',
    role: '',
    jenis_data: '',
};

// ── DOM Elements ──────────────────────────────────────────────────────────────
const tbody = document.getElementById('tbody-audit');
const paginationWrap = document.getElementById('paginasi-audit');
const subtitleInfo = document.getElementById('subtitle-info');
const perPageSelect = document.getElementById('per-page');

// Filter inputs
const filterSearch = document.getElementById('filter-search');
const filterTanggalDari = document.getElementById('filter-tanggal-dari');
const filterTanggalSampai = document.getElementById('filter-tanggal-sampai');
const filterAksi = document.getElementById('filter-aksi');
const filterRole = document.getElementById('filter-role');
const filterJenis = document.getElementById('filter-jenis');

// Buttons
const btnApplyFilter = document.getElementById('btn-apply-filter');
const btnResetFilter = document.getElementById('btn-reset-filter');
const btnExport = document.getElementById('btn-export');

// Modal
const modalDetail = document.getElementById('modal-detail');
const modalDetailBody = document.getElementById('modal-detail-body');

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
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
    btnApplyFilter?.addEventListener('click', () => {
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

    // Export CSV
    btnExport?.addEventListener('click', () => {
        exportToCSV();
    });

    // Modal close
    modalDetail?.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal();
        });
    });

    // Close modal on overlay click
    modalDetail?.querySelector('.modal-overlay')?.addEventListener('click', () => {
        closeModal();
    });

    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalDetail?.classList.contains('modal--active')) {
            closeModal();
        }
    });
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

        const response = await apiFetch(`/api/super-admin/audit-log?${params.toString()}`);
        const data = await response.json();

        if (data.status) {
            renderTable(data.data);
            renderPagination(data.pagination);
            updateSubtitle(data.pagination);
        } else {
            toast(data.message || 'Gagal memuat audit log', 'error');
            renderEmptyState('Gagal memuat data');
        }
    } catch (error) {
        console.error('Error loading audit log:', error);
        toast('Terjadi kesalahan saat memuat data', 'error');
        renderEmptyState('Terjadi kesalahan');
    }
}

// ── Render Table ──────────────────────────────────────────────────────────────
function renderTable(logs) {
    if (!tbody) return;

    if (logs.length === 0) {
        renderEmptyState('Tidak ada data audit log');
        return;
    }

    tbody.innerHTML = logs.map(log => `
        <tr>
            <td>
                <div style="font-size: 0.875rem;">${log.waktu_aksi}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                    ${log.waktu_relative}
                </div>
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
                <span class="badge badge--${log.badge_class}">
                    ${escapeHtml(log.aksi_label)}
                </span>
            </td>
            <td>
                ${escapeHtml(log.jenis_label)}
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
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
}

// ── Render Empty State ────────────────────────────────────────────────────────
function renderEmptyState(message) {
    if (!tbody) return;
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
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
function renderPagination(pagination) {
    if (!paginationWrap) return;

    const { current_page, last_page, total } = pagination;

    if (last_page <= 1) {
        paginationWrap.innerHTML = '';
        return;
    }

    let pages = [];
    
    // Always show first page
    pages.push(1);
    
    // Calculate range around current page
    const rangeStart = Math.max(2, current_page - 1);
    const rangeEnd = Math.min(last_page - 1, current_page + 1);
    
    // Add ellipsis after first page if needed
    if (rangeStart > 2) {
        pages.push('...');
    }
    
    // Add pages around current
    for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
    }
    
    // Add ellipsis before last page if needed
    if (rangeEnd < last_page - 1) {
        pages.push('...');
    }
    
    // Always show last page if more than 1 page
    if (last_page > 1) {
        pages.push(last_page);
    }

    paginationWrap.innerHTML = `
        <div class="pagination">
            <button 
                class="pagination-btn ${current_page === 1 ? 'pagination-btn--disabled' : ''}"
                ${current_page === 1 ? 'disabled' : ''}
                onclick="window.goToPage(${current_page - 1})"
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
                        class="pagination-btn ${page === current_page ? 'pagination-btn--active' : ''}"
                        onclick="window.goToPage(${page})"
                    >
                        ${page}
                    </button>
                `;
            }).join('')}
            
            <button 
                class="pagination-btn ${current_page === last_page ? 'pagination-btn--disabled' : ''}"
                ${current_page === last_page ? 'disabled' : ''}
                onclick="window.goToPage(${current_page + 1})"
            >
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    `;
}

// ── Update Subtitle ───────────────────────────────────────────────────────────
function updateSubtitle(pagination) {
    if (!subtitleInfo) return;
    
    const { from, to, total } = pagination;
    
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
    const aksiSelect = filterAksi?.querySelector('option:checked');
    const aksiValue = filterAksi?.value || '';
    const jenisDataAttr = aksiSelect?.getAttribute('data-jenis');
    
    filters = {
        search: filterSearch?.value.trim() || '',
        tanggal_dari: filterTanggalDari?.value || '',
        tanggal_sampai: filterTanggalSampai?.value || '',
        aksi: aksiValue,
        role: filterRole?.value || '',
        jenis_data: jenisDataAttr || filterJenis?.value || '',
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
    if (filterRole) filterRole.value = '';
    if (filterJenis) filterJenis.value = '';
    
    filters = {
        search: '',
        tanggal_dari: '',
        tanggal_sampai: '',
        aksi: '',
        role: '',
        jenis_data: '',
    };
    
    currentPage = 1;
    loadAuditLog();
}

// ── Show Audit Detail ─────────────────────────────────────────────────────────
window.showAuditDetail = async function(id) {
    if (!modalDetail || !modalDetailBody) return;
    
    // Show modal with loading state
    modalDetail.classList.add('modal--active');
    modalDetailBody.innerHTML = `
        <div class="skeleton-wrap">
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--medium"></div>
            <div class="skeleton-line"></div>
        </div>
    `;
    
    try {
        const response = await apiFetch(`/api/super-admin/audit-log/${id}`);
        const data = await response.json();
        
        if (data.status) {
            renderDetailModal(data.data);
        } else {
            toast(data.message || 'Gagal memuat detail', 'error');
            closeModal();
        }
    } catch (error) {
        console.error('Error loading audit detail:', error);
        toast('Terjadi kesalahan saat memuat detail', 'error');
        closeModal();
    }
};

// ── Render Detail Modal ───────────────────────────────────────────────────────
function renderDetailModal(log) {
    if (!modalDetailBody) return;
    
    const hasChanges = log.data_sebelum || log.data_sesudah;
    
    modalDetailBody.innerHTML = `
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Waktu Aksi</div>
                <div class="detail-value">${log.waktu_aksi}</div>
                <div class="detail-meta">${log.waktu_relative}</div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Pengguna</div>
                <div class="detail-value">${escapeHtml(log.pengguna.nama)}</div>
                ${log.pengguna.email ? `<div class="detail-meta">${escapeHtml(log.pengguna.email)}</div>` : ''}
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Role</div>
                <div class="detail-value">
                    <span class="badge badge--${getRoleBadgeClass(log.role_pelaku)}">
                        ${escapeHtml(log.role_label)}
                    </span>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Aksi</div>
                <div class="detail-value">
                    <span class="badge badge--${log.badge_class}">
                        ${escapeHtml(log.aksi_label)}
                    </span>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Modul / Jenis Data</div>
                <div class="detail-value">${escapeHtml(log.jenis_label)}</div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">ID Referensi</div>
                <div class="detail-value">${log.id_referensi}</div>
            </div>
            
            ${log.catatan ? `
                <div class="detail-item detail-item--full">
                    <div class="detail-label">Catatan</div>
                    <div class="detail-value">${escapeHtml(log.catatan)}</div>
                </div>
            ` : ''}
            
            ${log.ip_address ? `
                <div class="detail-item">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-value">${escapeHtml(log.ip_address)}</div>
                </div>
            ` : ''}
        </div>
        
        ${hasChanges ? `
            <div style="margin-top: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">Perubahan Data</h4>
                <div class="changes-grid">
                    ${log.data_sebelum ? `
                        <div class="changes-column">
                            <div class="changes-header">Data Sebelum</div>
                            <pre class="changes-content">${JSON.stringify(log.data_sebelum, null, 2)}</pre>
                        </div>
                    ` : ''}
                    
                    ${log.data_sesudah ? `
                        <div class="changes-column">
                            <div class="changes-header">Data Sesudah</div>
                            <pre class="changes-content">${JSON.stringify(log.data_sesudah, null, 2)}</pre>
                        </div>
                    ` : ''}
                </div>
            </div>
        ` : ''}
    `;
}

// ── Close Modal ───────────────────────────────────────────────────────────────
function closeModal() {
    modalDetail?.classList.remove('modal--active');
}

// ── Export to CSV ─────────────────────────────────────────────────────────────
async function exportToCSV() {
    try {
        // Build query params (without pagination)
        const params = new URLSearchParams({
            per_page: 10000, // Get all data
            ...filters,
        });

        // Remove empty params
        for (const [key, value] of [...params.entries()]) {
            if (!value) params.delete(key);
        }

        const response = await apiFetch(`/api/super-admin/audit-log?${params.toString()}`);
        const data = await response.json();

        if (data.status && data.data.length > 0) {
            const csv = convertToCSV(data.data);
            downloadCSV(csv, `audit-log-${new Date().toISOString().split('T')[0]}.csv`);
            toast('Export berhasil', 'success');
        } else {
            toast('Tidak ada data untuk di-export', 'warning');
        }
    } catch (error) {
        console.error('Error exporting CSV:', error);
        toast('Gagal export data', 'error');
    }
}

// ── Convert to CSV ────────────────────────────────────────────────────────────
function convertToCSV(data) {
    const headers = ['Waktu', 'Pengguna', 'Role', 'Aksi', 'Modul', 'Catatan', 'IP Address'];
    const rows = data.map(log => [
        log.waktu_aksi,
        log.pengguna_nama,
        log.role_label,
        log.aksi_label,
        log.jenis_label,
        log.catatan,
        '', // IP address not in list view, would need to fetch details
    ]);
    
    const csvContent = [
        headers.join(','),
        ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n');
    
    return csvContent;
}

// ── Download CSV ──────────────────────────────────────────────────────────────
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
