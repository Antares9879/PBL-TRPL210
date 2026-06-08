/**
 * resources/js/admin-outsource/validasi-absensi.js
 * F10 — Validasi Absensi dengan Modal Konfirmasi & Bulk Validation
 *
 * Fitur:
 * - Single validation dengan modal detail lengkap
 * - Bulk approve dengan preview
 * - Bulk reject dengan 2 mode (sama/terpisah)
 * - Dropdown alasan standar untuk reject
 */

import {
    apiFetch, esc, fmtWaktu, fmtTanggal, fmtMenit,
    toast, openModal, closeModal,
    badgeKehadiran, badgeValidasi,
    renderPaginasi, injectModalStyles,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════
//  GLOBAL STATE
// ══════════════════════════════════════════════════════════════════════

const isRiwayat = window.location.pathname.includes('riwayat');

let currentPage       = 1;
let filterTanggalDari = getTodayDateString();
let filterTanggalSampai = getTodayDateString();
let filterKaryawan    = '';
let filterValidasi    = isRiwayat ? '' : 'menunggu';
let filterBulan       = new Date().getMonth() + 1;
let filterTahun       = new Date().getFullYear();
let debounceTimer     = null;

// Bulk selection state
let selectedAbsensiIds = new Set();
let selectedAbsensiData = new Map();

// Single validation state
let currentAbsensiId = null;
let currentAbsensiData = null;

// Dropdown alasan penolakan
const ALASAN_PENOLAKAN = [
    { value: 'lokasi_tidak_valid', text: 'Lokasi GPS tidak valid (>100m dari area)' },
    { value: 'waktu_tidak_sesuai', text: 'Waktu check-in terlalu jauh dari jadwal' },
    { value: 'data_tidak_lengkap', text: 'Data absensi tidak lengkap' },
    { value: 'foto_tidak_sesuai', text: 'Foto/bukti tidak sesuai ketentuan' },
    { value: 'duplikat', text: 'Absensi duplikat' },
    { value: 'lainnya', text: 'Lainnya (isi keterangan di bawah)' },
];

document.addEventListener('DOMContentLoaded', () => {
    injectModalStyles();

    if (isRiwayat) {
        initRiwayat();
    } else {
        initValidasi();
    }
});

// ══════════════════════════════════════════════════════════════════════
//  VALIDASI ABSENSI (F10)
// ══════════════════════════════════════════════════════════════════════

function initValidasi() {
    updateThead('validasi');
    injectToolbarValidasi();
    injectModalsValidasi();
    setupCheckboxHandlers();
    loadAbsensi();
}

async function loadAbsensi(page = 1) {
    currentPage = page;
    showSkeleton(10, 'tbody-validasi-absensi');

    const params = new URLSearchParams({ page });
    if (filterValidasi)  params.set('status_validasi', filterValidasi);
    if (filterTanggalDari)   params.set('tanggal_dari',   filterTanggalDari);
    if (filterTanggalSampai) params.set('tanggal_sampai', filterTanggalSampai);
    if (filterKaryawan)  params.set('search',            filterKaryawan);

    try {
        const res  = await apiFetch(`/api/admin/validasi-absensi?${params}`);
        const json = await res.json();
        if (!json.status) { 
            toast(json.message, 'error'); 
            showEmpty(10, 'tbody-validasi-absensi', 'Gagal memuat data.'); 
            return; 
        }

        // Clear selection saat load data baru
        clearSelection();

        renderValidasi(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-absensi', loadAbsensi);

    } catch (err) {
        console.error('[ValidasiAbsensi] error:', err);
        toast('Gagal terhubung ke server.', 'error');
    }
}

function renderValidasi(rows) {
    const tbody = document.getElementById('tbody-validasi-absensi');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada absensi ${filterValidasi === 'menunggu' ? 'yang menunggu validasi' : ''} ditemukan.</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(row => {
        const namaKaryawan = row.nama_karyawan ?? row.karyawan?.nama_lengkap ?? '—';
        const nomorKaryawan = row.nomor_karyawan ?? row.karyawan?.nomor_karyawan ?? '';
        const namaShift = row.nama_shift ?? row.shift?.nama_shift ?? '—';
        const lokasiValid = getLokasiValid(row);
        const isPending = row.status_validasi === 'menunggu';

        return `
        <tr data-absensi-id="${row.id_absensi}" class="absensi-row">
            <td style="text-align:center;">
                ${isPending 
                    ? `<input type="checkbox" class="absensi-checkbox" value="${row.id_absensi}" 
                        style="width:16px;height:16px;cursor:pointer;"
                        data-json='${JSON.stringify(row).replace(/'/g, "&apos;")}'>` 
                    : '<span style="color:#cbd5e1;">—</span>'}
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                        background:linear-gradient(135deg,#1a6e1a,#0a280a);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#87dc87;">
                        ${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}
                    </div>
                    <div>
                        <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(nomorKaryawan)}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_absensi)}</td>
            <td style="font-size:12px;color:#475569;">${esc(namaShift)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                ${fmtWaktu(row.waktu_check_in)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                ${fmtWaktu(row.waktu_check_out)}</td>
            <td>${renderLokasiBadge(lokasiValid)}</td>
            <td>
                ${(row.menit_telat ?? 0) > 0
                    ? `<span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:600;color:#d97706;">
                           +${row.menit_telat} mnt</span>`
                    : `<span style="color:#94a3b8;font-size:12px;">—</span>`
                }
            </td>
            <td>${badgeValidasi(row.status_validasi)}</td>
            <td>
                ${isPending
                    ? `<div style="display:flex;gap:5px;">
                            <button class="btn-approve btn-single-approve"
                                data-id="${row.id_absensi}">
                                ✓
                            </button>
                            <button class="btn-reject btn-single-reject"
                                data-id="${row.id_absensi}">
                                ✕
                            </button>
                       </div>`
                    : `<span style="font-size:12px;color:#94a3b8;">Sudah diproses</span>`
                }
            </td>
        </tr>
    `;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════════
//  CHECKBOX & BULK SELECTION
// ══════════════════════════════════════════════════════════════════════

function setupCheckboxHandlers() {
    // Select All
    document.addEventListener('change', (e) => {
        if (e.target.id === 'select-all-checkbox') {
            const checkboxes = document.querySelectorAll('.absensi-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
                toggleSelection(cb);
            });
        }
        
        if (e.target.classList.contains('absensi-checkbox')) {
            toggleSelection(e.target);
        }
    });

    // Bulk action buttons
    document.getElementById('btn-bulk-approve')?.addEventListener('click', handleBulkApprove);
    document.getElementById('btn-bulk-reject')?.addEventListener('click', handleBulkReject);

    // Single action buttons - delegasi ke parent
    document.addEventListener('click', async (e) => {
        const approveBtn = e.target.closest('.btn-single-approve');
        const rejectBtn = e.target.closest('.btn-single-reject');

        if (approveBtn) {
            const id = parseInt(approveBtn.dataset.id);
            await showSingleApproveModal(id);
        }

        if (rejectBtn) {
            const id = parseInt(rejectBtn.dataset.id);
            await showSingleRejectModal(id);
        }
    });
}

function toggleSelection(checkbox) {
    const id = parseInt(checkbox.value);
    
    if (checkbox.checked) {
        try {
            const data = JSON.parse(checkbox.dataset.json);
            selectedAbsensiIds.add(id);
            selectedAbsensiData.set(id, data);
        } catch (e) {
            console.error('Error parsing absensi data:', e);
        }
    } else {
        selectedAbsensiIds.delete(id);
        selectedAbsensiData.delete(id);
    }
    
    updateBulkActionBar();
    updateSelectAllCheckbox();
}

function clearSelection() {
    selectedAbsensiIds.clear();
    selectedAbsensiData.clear();
    
    document.querySelectorAll('.absensi-checkbox').forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('select-all-checkbox');
    if (selectAll) selectAll.checked = false;
    
    updateBulkActionBar();
}

function updateBulkActionBar() {
    const bar = document.getElementById('bulk-action-bar');
    const count = document.getElementById('selected-count');
    
    if (bar && count) {
        count.textContent = selectedAbsensiIds.size;
        bar.style.display = selectedAbsensiIds.size > 0 ? 'flex' : 'none';
    }
}

function updateSelectAllCheckbox() {
    const selectAll = document.getElementById('select-all-checkbox');
    const checkboxes = document.querySelectorAll('.absensi-checkbox');
    
    if (selectAll && checkboxes.length > 0) {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        selectAll.checked = allChecked;
    }
}

// ══════════════════════════════════════════════════════════════════════
//  SINGLE VALIDATION - APPROVE
// ══════════════════════════════════════════════════════════════════════

async function showSingleApproveModal(id) {
    const row = document.querySelector(`tr[data-absensi-id="${id}"]`);
    if (!row) return;
    
    const checkbox = row.querySelector('.absensi-checkbox');
    if (!checkbox) return;
    
    try {
        currentAbsensiData = JSON.parse(checkbox.dataset.json);
        currentAbsensiId = id;
        
        // Populate modal
        document.getElementById('approve-detail-nama').textContent = 
            currentAbsensiData.karyawan?.nama_lengkap ?? '—';
        document.getElementById('approve-detail-tanggal').textContent = 
            fmtTanggal(currentAbsensiData.tanggal_absensi);
        document.getElementById('approve-detail-shift').textContent = 
            `${currentAbsensiData.shift?.nama_shift ?? '—'} (${currentAbsensiData.shift?.jam_masuk ?? '—'} - ${currentAbsensiData.shift?.jam_pulang ?? '—'})`;
        document.getElementById('approve-detail-checkin').textContent = 
            fmtWaktu(currentAbsensiData.waktu_check_in) + getStatusTelat(currentAbsensiData);
        document.getElementById('approve-detail-checkout').textContent = 
            fmtWaktu(currentAbsensiData.waktu_check_out) || '—';
        document.getElementById('approve-detail-lokasi').textContent = 
            currentAbsensiData.lokasi_check_in || '—';
        document.getElementById('approve-detail-jarak').textContent = 
            currentAbsensiData.jarak_check_in || '—';
        
        openModal('modal-single-approve');
    } catch (e) {
        console.error('Error showing approve modal:', e);
        toast('Gagal memuat detail absensi.', 'error');
    }
}

async function handleSingleApprove() {
    if (!currentAbsensiId) return;
    
    try {
        const res = await apiFetch(`/api/admin/validasi-absensi/${currentAbsensiId}/approve`, {
            method: 'POST',
        });
        const json = await res.json();
        
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-single-approve');
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses approve.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════════════
//  SINGLE VALIDATION - REJECT
// ══════════════════════════════════════════════════════════════════════

async function showSingleRejectModal(id) {
    const row = document.querySelector(`tr[data-absensi-id="${id}"]`);
    if (!row) return;
    
    const checkbox = row.querySelector('.absensi-checkbox');
    if (!checkbox) return;
    
    try {
        currentAbsensiData = JSON.parse(checkbox.dataset.json);
        currentAbsensiId = id;
        
        // Populate modal dengan detail yang sama
        document.getElementById('reject-detail-nama').textContent = 
            currentAbsensiData.karyawan?.nama_lengkap ?? '—';
        document.getElementById('reject-detail-tanggal').textContent = 
            fmtTanggal(currentAbsensiData.tanggal_absensi);
        document.getElementById('reject-detail-shift').textContent = 
            `${currentAbsensiData.shift?.nama_shift ?? '—'} (${currentAbsensiData.shift?.jam_masuk ?? '—'} - ${currentAbsensiData.shift?.jam_pulang ?? '—'})`;
        document.getElementById('reject-detail-checkin').textContent = 
            fmtWaktu(currentAbsensiData.waktu_check_in) + getStatusTelat(currentAbsensiData);
        document.getElementById('reject-detail-checkout').textContent = 
            fmtWaktu(currentAbsensiData.waktu_check_out) || '—';
        document.getElementById('reject-detail-lokasi').textContent = 
            currentAbsensiData.lokasi_check_in || '—';
        document.getElementById('reject-detail-jarak').textContent = 
            currentAbsensiData.jarak_check_in || '—';
        
        // Reset form
        document.getElementById('reject-alasan').value = '';
        document.getElementById('reject-keterangan').value = '';
        document.getElementById('reject-keterangan').required = false;
        
        openModal('modal-single-reject');
    } catch (e) {
        console.error('Error showing reject modal:', e);
        toast('Gagal memuat detail absensi.', 'error');
    }
}

async function handleSingleReject() {
    if (!currentAbsensiId) return;
    
    const alasan = document.getElementById('reject-alasan').value;
    const keterangan = document.getElementById('reject-keterangan').value.trim();
    
    if (!alasan) {
        toast('Pilih alasan penolakan.', 'warning');
        return;
    }
    
    if (alasan === 'lainnya' && !keterangan) {
        toast('Keterangan wajib diisi untuk alasan "Lainnya".', 'warning');
        return;
    }
    
    try {
        const res = await apiFetch(`/api/admin/validasi-absensi/${currentAbsensiId}/reject`, {
            method: 'POST',
            body: JSON.stringify({
                aksi: 'reject',
                alasan_penolakan: ALASAN_PENOLAKAN.find(a => a.value === alasan)?.text || alasan,
                keterangan_tambahan: keterangan || null,
            }),
        });
        const json = await res.json();
        
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-single-reject');
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses reject.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════════════
//  BULK VALIDATION - APPROVE
// ══════════════════════════════════════════════════════════════════════

function handleBulkApprove() {
    if (selectedAbsensiIds.size === 0) {
        toast('Pilih minimal satu absensi.', 'warning');
        return;
    }
    
    // Populate preview list
    const list = Array.from(selectedAbsensiData.values())
        .map((data, idx) => {
            const nama = data.karyawan?.nama_lengkap ?? '—';
            const tanggal = fmtTanggal(data.tanggal_absensi);
            const checkin = fmtWaktu(data.waktu_check_in);
            return `${idx + 1}. ${esc(nama)} - ${tanggal} - ${checkin}`;
        }).join('\n');
    
    document.getElementById('bulk-approve-list').textContent = list;
    document.getElementById('bulk-approve-count').textContent = selectedAbsensiIds.size;
    
    openModal('modal-bulk-approve');
}

async function confirmBulkApprove() {
    if (selectedAbsensiIds.size === 0) return;
    
    const ids = Array.from(selectedAbsensiIds);
    
    try {
        const res = await apiFetch('/api/admin/validasi-absensi/bulk-approve', {
            method: 'POST',
            body: JSON.stringify({ 
                aksi: 'approve',
                absensi_ids: ids 
            }),
        });
        const json = await res.json();
        
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-bulk-approve');
            clearSelection();
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses bulk approve.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════════════
//  BULK VALIDATION - REJECT
// ══════════════════════════════════════════════════════════════════════

function handleBulkReject() {
    if (selectedAbsensiIds.size === 0) {
        toast('Pilih minimal satu absensi.', 'warning');
        return;
    }
    
    // Populate preview list
    const list = Array.from(selectedAbsensiData.values())
        .map((data, idx) => {
            const nama = data.karyawan?.nama_lengkap ?? '—';
            const tanggal = fmtTanggal(data.tanggal_absensi);
            const checkin = fmtWaktu(data.waktu_check_in);
            return `${idx + 1}. ${esc(nama)} - ${tanggal} - ${checkin}`;
        }).join('\n');
    
    document.getElementById('bulk-reject-list-preview').textContent = list;
    document.getElementById('bulk-reject-count-preview').textContent = selectedAbsensiIds.size;
    
    // Reset mode selection
    document.getElementById('bulk-mode-same').checked = true;
    
    openModal('modal-bulk-reject-mode');
}

function proceedBulkReject() {
    const mode = document.querySelector('input[name="bulk-reject-mode"]:checked')?.value;
    
    if (!mode) {
        toast('Pilih metode pengisian alasan.', 'warning');
        return;
    }
    
    closeModal('modal-bulk-reject-mode');
    
    if (mode === 'same') {
        showBulkRejectSameReason();
    } else {
        showBulkRejectIndividualReason();
    }
}

function showBulkRejectSameReason() {
    // Populate list
    const list = Array.from(selectedAbsensiData.values())
        .map(data => {
            const nama = data.karyawan?.nama_lengkap ?? '—';
            const tanggal = fmtTanggal(data.tanggal_absensi);
            return `• ${esc(nama)} (${tanggal})`;
        }).join('\n');
    
    document.getElementById('bulk-reject-same-list').textContent = list;
    
    // Reset form
    document.getElementById('bulk-reject-same-alasan').value = '';
    document.getElementById('bulk-reject-same-keterangan').value = '';
    
    openModal('modal-bulk-reject-same');
}

async function confirmBulkRejectSame() {
    const alasan = document.getElementById('bulk-reject-same-alasan').value;
    const keterangan = document.getElementById('bulk-reject-same-keterangan').value.trim();
    
    if (!alasan) {
        toast('Pilih alasan penolakan.', 'warning');
        return;
    }
    
    if (alasan === 'lainnya' && !keterangan) {
        toast('Keterangan wajib diisi untuk alasan "Lainnya".', 'warning');
        return;
    }
    
    const ids = Array.from(selectedAbsensiIds);
    
    try {
        const res = await apiFetch('/api/admin/validasi-absensi/bulk-reject', {
            method: 'POST',
            body: JSON.stringify({
                aksi: 'reject',
                mode: 'same_reason',
                absensi_ids: ids,
                alasan_penolakan: ALASAN_PENOLAKAN.find(a => a.value === alasan)?.text || alasan,
                keterangan_tambahan: keterangan || null,
            }),
        });
        const json = await res.json();
        
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-bulk-reject-same');
            clearSelection();
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses bulk reject.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════════════
//  BULK VALIDATION - REJECT INDIVIDUAL
// ══════════════════════════════════════════════════════════════════════

function showBulkRejectIndividualReason() {
    const container = document.getElementById('bulk-reject-individual-items');
    if (!container) return;
    
    // Generate accordion items
    container.innerHTML = Array.from(selectedAbsensiData.values())
        .map((data, idx) => {
            const nama = data.karyawan?.nama_lengkap ?? '—';
            const tanggal = fmtTanggal(data.tanggal_absensi);
            const checkin = fmtWaktu(data.waktu_check_in);
            
            return `
            <div class="accordion-item" data-absensi-id="${data.id_absensi}">
                <div class="accordion-header" onclick="toggleAccordion(${data.id_absensi})">
                    <span style="font-weight:500;color:#0f172a;">
                        ${idx + 1}. ${esc(nama)} - ${tanggal} - ${checkin}
                    </span>
                    <span class="accordion-icon" id="icon-${data.id_absensi}">▼</span>
                </div>
                <div class="accordion-body" id="body-${data.id_absensi}">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="display:block;font-size:12px;font-weight:500;color:#475569;margin-bottom:6px;">
                            Alasan Penolakan *
                        </label>
                        <select class="individual-alasan" data-id="${data.id_absensi}"
                            style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                            <option value="">Pilih alasan...</option>
                            ${ALASAN_PENOLAKAN.map(a => 
                                `<option value="${a.value}">${esc(a.text)}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:block;font-size:12px;font-weight:500;color:#475569;margin-bottom:6px;">
                            Keterangan Tambahan
                        </label>
                        <textarea class="individual-keterangan" data-id="${data.id_absensi}"
                            rows="2" placeholder="Detail tambahan..."
                            style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;
                            font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                    </div>
                </div>
            </div>
            `;
        }).join('');
    
    // Setup event handlers untuk validasi "lainnya"
    container.querySelectorAll('.individual-alasan').forEach(select => {
        select.addEventListener('change', (e) => {
            const id = e.target.dataset.id;
            const textarea = container.querySelector(`.individual-keterangan[data-id="${id}"]`);
            if (textarea) {
                textarea.required = e.target.value === 'lainnya';
            }
        });
    });
    
    openModal('modal-bulk-reject-individual');
}

async function confirmBulkRejectIndividual() {
    const container = document.getElementById('bulk-reject-individual-items');
    if (!container) return;
    
    const rejections = [];
    let hasError = false;
    
    selectedAbsensiIds.forEach(id => {
        const alasanSelect = container.querySelector(`.individual-alasan[data-id="${id}"]`);
        const keteranganTextarea = container.querySelector(`.individual-keterangan[data-id="${id}"]`);
        
        const alasan = alasanSelect?.value || '';
        const keterangan = keteranganTextarea?.value.trim() || '';
        
        if (!alasan) {
            toast(`Alasan untuk absensi ID ${id} belum diisi.`, 'warning');
            hasError = true;
            return;
        }
        
        if (alasan === 'lainnya' && !keterangan) {
            toast(`Keterangan untuk absensi ID ${id} wajib diisi.`, 'warning');
            hasError = true;
            return;
        }
        
        rejections.push({
            id: id,
            alasan_penolakan: ALASAN_PENOLAKAN.find(a => a.value === alasan)?.text || alasan,
            keterangan_tambahan: keterangan || null,
        });
    });
    
    if (hasError) return;
    
    try {
        const res = await apiFetch('/api/admin/validasi-absensi/bulk-reject', {
            method: 'POST',
            body: JSON.stringify({
                aksi: 'reject',
                mode: 'individual_reason',
                rejections: rejections,
            }),
        });
        const json = await res.json();
        
        if (json.status) {
            toast(json.message, 'success');
            closeModal('modal-bulk-reject-individual');
            clearSelection();
            loadAbsensi(currentPage);
        } else {
            toast(json.message, 'error');
        }
    } catch {
        toast('Gagal memproses bulk reject.', 'error');
    }
}

// Helper untuk toggle accordion
window.toggleAccordion = function(id) {
    const body = document.getElementById(`body-${id}`);
    const icon = document.getElementById(`icon-${id}`);
    
    if (body && icon) {
        const isOpen = body.style.display === 'block';
        body.style.display = isOpen ? 'none' : 'block';
        icon.textContent = isOpen ? '▼' : '▲';
    }
};

// ══════════════════════════════════════════════════════════════════════
//  INJECT MODALS
// ══════════════════════════════════════════════════════════════════════

function injectModalsValidasi() {
    if (document.getElementById('modal-single-approve')) return; // Already injected
    
    const modalsHTML = `
    <!-- Modal Single Approve -->
    <div id="modal-single-approve" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:520px;">
            <div class="modal-header">
                <h3 class="modal-title">✅ Konfirmasi Approve Absensi</h3>
                <button data-close-modal="modal-single-approve" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div style="background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:16px;">
                    <table style="width:100%;font-size:13px;">
                        <tr><td style="color:#64748b;padding:4px 0;width:140px;">Nama Karyawan</td>
                            <td style="font-weight:500;color:#0f172a;" id="approve-detail-nama">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Tanggal</td>
                            <td style="color:#0f172a;" id="approve-detail-tanggal">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Shift</td>
                            <td style="color:#0f172a;" id="approve-detail-shift">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Check-in</td>
                            <td style="color:#0f172a;font-weight:500;" id="approve-detail-checkin">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Check-out</td>
                            <td style="color:#0f172a;" id="approve-detail-checkout">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Lokasi Check-in</td>
                            <td style="color:#0f172a;font-size:11px;" id="approve-detail-lokasi">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Jarak dari Area</td>
                            <td style="color:#0f172a;" id="approve-detail-jarak">—</td></tr>
                    </table>
                </div>
                <p style="font-size:13px;color:#64748b;margin:16px 0;">
                    ⚠️ Apakah Anda yakin akan menyetujui absensi ini?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" data-close-modal="modal-single-approve" class="btn-cancel">Batal</button>
                <button type="button" onclick="handleSingleApprove()" class="btn-approve">✅ Ya, Approve</button>
            </div>
        </div>
    </div>

    <!-- Modal Single Reject -->
    <div id="modal-single-reject" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:520px;">
            <div class="modal-header">
                <h3 class="modal-title">❌ Konfirmasi Reject Absensi</h3>
                <button data-close-modal="modal-single-reject" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div style="background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:16px;">
                    <table style="width:100%;font-size:13px;">
                        <tr><td style="color:#64748b;padding:4px 0;width:140px;">Nama Karyawan</td>
                            <td style="font-weight:500;color:#0f172a;" id="reject-detail-nama">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Tanggal</td>
                            <td style="color:#0f172a;" id="reject-detail-tanggal">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Shift</td>
                            <td style="color:#0f172a;" id="reject-detail-shift">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Check-in</td>
                            <td style="color:#0f172a;font-weight:500;" id="reject-detail-checkin">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Check-out</td>
                            <td style="color:#0f172a;" id="reject-detail-checkout">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Lokasi Check-in</td>
                            <td style="color:#0f172a;font-size:11px;" id="reject-detail-lokasi">—</td></tr>
                        <tr><td style="color:#64748b;padding:4px 0;">Jarak dari Area</td>
                            <td style="color:#0f172a;" id="reject-detail-jarak">—</td></tr>
                    </table>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label class="form-label">Alasan Penolakan *</label>
                    <select id="reject-alasan" class="form-select" style="width:100%;padding:8px;
                        border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                        <option value="">Pilih alasan penolakan</option>
                        ${ALASAN_PENOLAKAN.map(a => `<option value="${a.value}">${esc(a.text)}</option>`).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan Tambahan</label>
                    <textarea id="reject-keterangan" rows="3" class="form-textarea"
                        placeholder="Detail tambahan (wajib jika pilih 'Lainnya')"
                        style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;
                        font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-close-modal="modal-single-reject" class="btn-cancel">Batal</button>
                <button type="button" onclick="handleSingleReject()" class="btn-reject">❌ Ya, Reject</button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Approve -->
    <div id="modal-bulk-approve" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:480px;">
            <div class="modal-header">
                <h3 class="modal-title">✅ Konfirmasi Approve Multiple Absensi</h3>
                <button data-close-modal="modal-bulk-approve" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#0f172a;margin-bottom:12px;">
                    Anda akan menyetujui <strong id="bulk-approve-count">0</strong> absensi:
                </p>
                <div style="background:#f8fafc;padding:12px;border-radius:8px;max-height:200px;overflow-y:auto;">
                    <pre id="bulk-approve-list" style="font-size:12px;color:#475569;margin:0;white-space:pre-wrap;"></pre>
                </div>
                <p style="font-size:13px;color:#64748b;margin:16px 0 0;">
                    ⚠️ Semua absensi di atas akan disetujui. Apakah Anda yakin?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" data-close-modal="modal-bulk-approve" class="btn-cancel">Batal</button>
                <button type="button" onclick="confirmBulkApprove()" class="btn-approve">✅ Ya, Approve Semua</button>
            </div>
        </div>
    </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalsHTML);
    
    // Inject sisanya (bulk reject modals) - split karena panjang
    injectBulkRejectModals();
    
    // Setup event handlers
    setupModalHandlers();
}

function injectBulkRejectModals() {
    const modalsHTML = `
    <!-- Modal Bulk Reject - Mode Selection -->
    <div id="modal-bulk-reject-mode" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:480px;">
            <div class="modal-header">
                <h3 class="modal-title">❌ Konfirmasi Reject Multiple Absensi</h3>
                <button data-close-modal="modal-bulk-reject-mode" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#0f172a;margin-bottom:12px;">
                    Anda akan menolak <strong id="bulk-reject-count-preview">0</strong> absensi:
                </p>
                <div style="background:#f8fafc;padding:12px;border-radius:8px;max-height:150px;overflow-y:auto;margin-bottom:16px;">
                    <pre id="bulk-reject-list-preview" style="font-size:12px;color:#475569;margin:0;white-space:pre-wrap;"></pre>
                </div>
                <p style="font-size:13px;font-weight:500;color:#0f172a;margin-bottom:12px;">
                    Pilih metode pengisian alasan:
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;padding:10px;border:1px solid #e2e8f0;
                        border-radius:8px;cursor:pointer;font-size:13px;">
                        <input type="radio" name="bulk-reject-mode" value="same" id="bulk-mode-same" checked
                            style="margin-right:10px;width:16px;height:16px;">
                        <span>Gunakan alasan yang sama untuk semua</span>
                    </label>
                    <label style="display:flex;align-items:center;padding:10px;border:1px solid #e2e8f0;
                        border-radius:8px;cursor:pointer;font-size:13px;">
                        <input type="radio" name="bulk-reject-mode" value="individual" id="bulk-mode-individual"
                            style="margin-right:10px;width:16px;height:16px;">
                        <span>Isi alasan terpisah untuk setiap absensi</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-close-modal="modal-bulk-reject-mode" class="btn-cancel">Batal</button>
                <button type="button" onclick="proceedBulkReject()" class="btn-primary">Lanjutkan →</button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Reject - Same Reason -->
    <div id="modal-bulk-reject-same" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:520px;">
            <div class="modal-header">
                <h3 class="modal-title">❌ Reject - Alasan Sama</h3>
                <button data-close-modal="modal-bulk-reject-same" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:12px;">
                    <label class="form-label">Alasan Penolakan untuk Semua *</label>
                    <select id="bulk-reject-same-alasan" class="form-select" style="width:100%;padding:8px;
                        border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                        <option value="">Pilih alasan penolakan</option>
                        ${ALASAN_PENOLAKAN.map(a => `<option value="${a.value}">${esc(a.text)}</option>`).join('')}
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Keterangan Tambahan</label>
                    <textarea id="bulk-reject-same-keterangan" rows="3" class="form-textarea"
                        placeholder="Detail tambahan..."
                        style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:6px;
                        font-size:13px;font-family:inherit;resize:vertical;"></textarea>
                </div>
                <div style="background:#f8fafc;padding:12px;border-radius:8px;">
                    <p style="font-size:12px;font-weight:500;color:#475569;margin:0 0 8px;">Akan diterapkan ke:</p>
                    <pre id="bulk-reject-same-list" style="font-size:12px;color:#64748b;margin:0;white-space:pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="openModal('modal-bulk-reject-mode');closeModal('modal-bulk-reject-same');" 
                    class="btn-cancel">← Kembali</button>
                <button type="button" onclick="confirmBulkRejectSame()" class="btn-reject">❌ Reject Semua</button>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Reject - Individual Reason -->
    <div id="modal-bulk-reject-individual" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width:620px;max-height:80vh;">
            <div class="modal-header">
                <h3 class="modal-title">❌ Reject - Alasan Terpisah</h3>
                <button data-close-modal="modal-bulk-reject-individual" class="modal-close">×</button>
            </div>
            <div class="modal-body" style="max-height:calc(80vh - 140px);overflow-y:auto;">
                <div id="bulk-reject-individual-items"></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="openModal('modal-bulk-reject-mode');closeModal('modal-bulk-reject-individual');" 
                    class="btn-cancel">← Kembali</button>
                <button type="button" onclick="confirmBulkRejectIndividual()" class="btn-reject">❌ Reject Semua</button>
            </div>
        </div>
    </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalsHTML);
}

function setupModalHandlers() {
    // Close modal buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
    
    // Validasi dinamis untuk single reject
    const rejectAlasan = document.getElementById('reject-alasan');
    const rejectKeterangan = document.getElementById('reject-keterangan');
    if (rejectAlasan && rejectKeterangan) {
        rejectAlasan.addEventListener('change', () => {
            rejectKeterangan.required = rejectAlasan.value === 'lainnya';
        });
    }
    
    // Validasi dinamis untuk bulk reject same
    const bulkSameAlasan = document.getElementById('bulk-reject-same-alasan');
    const bulkSameKeterangan = document.getElementById('bulk-reject-same-keterangan');
    if (bulkSameAlasan && bulkSameKeterangan) {
        bulkSameAlasan.addEventListener('change', () => {
            bulkSameKeterangan.required = bulkSameAlasan.value === 'lainnya';
        });
    }
}

// ══════════════════════════════════════════════════════════════════════
//  TOOLBAR & FILTERS
// ══════════════════════════════════════════════════════════════════════

function injectToolbarValidasi() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-validasi-status')) return;

    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan-absensi" class="ao-search" type="text"
            placeholder="Cari nama karyawan..." style="width:200px;">
        <input id="filter-tanggal-dari" type="date" class="ao-select"
            value="${filterTanggalDari}" aria-label="Tanggal mulai" style="padding:7px 12px;">
        <input id="filter-tanggal-sampai" type="date" class="ao-select"
            value="${filterTanggalSampai}" aria-label="Tanggal akhir" style="padding:7px 12px;">
        <select id="filter-validasi-status" class="ao-select">
            <option value="">Semua Status</option>
            <option value="menunggu" selected>Menunggu Validasi</option>
            <option value="disetujui">Sudah Disetujui</option>
            <option value="ditolak">Ditolak</option>
        </select>
        <button id="btn-reset-filter-absensi" style="padding:7px 12px;border:1px solid #e2e8f0;
            border-radius:8px;background:#fff;font-size:12px;color:#64748b;cursor:pointer;">
            Reset
        </button>
    `;
    header.after(wrap);

    // Events
    wrap.querySelector('#search-karyawan-absensi')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { filterKaryawan = e.target.value.trim(); loadAbsensi(1); }, 400);
    });
    wrap.querySelector('#filter-tanggal-dari')?.addEventListener('change', e => {
        filterTanggalDari = e.target.value;
        if (filterTanggalDari && filterTanggalSampai && filterTanggalDari > filterTanggalSampai) {
            filterTanggalSampai = filterTanggalDari;
            wrap.querySelector('#filter-tanggal-sampai').value = filterTanggalSampai;
        }
        loadAbsensi(1);
    });
    wrap.querySelector('#filter-tanggal-sampai')?.addEventListener('change', e => {
        filterTanggalSampai = e.target.value;
        if (filterTanggalDari && filterTanggalSampai && filterTanggalSampai < filterTanggalDari) {
            filterTanggalDari = filterTanggalSampai;
            wrap.querySelector('#filter-tanggal-dari').value = filterTanggalDari;
        }
        loadAbsensi(1);
    });
    wrap.querySelector('#filter-validasi-status')?.addEventListener('change', e => {
        filterValidasi = e.target.value; loadAbsensi(1);
    });
    wrap.querySelector('#btn-reset-filter-absensi')?.addEventListener('click', () => {
        const today = getTodayDateString();
        filterTanggalDari = today;
        filterTanggalSampai = today;
        filterKaryawan = '';
        filterValidasi = 'menunggu';
        wrap.querySelector('#filter-tanggal-dari').value = today;
        wrap.querySelector('#filter-tanggal-sampai').value = today;
        wrap.querySelector('#search-karyawan-absensi').value = '';
        wrap.querySelector('#filter-validasi-status').value = 'menunggu';
        loadAbsensi(1);
    });
}

// ══════════════════════════════════════════════════════════════════════
//  RIWAYAT ABSENSI (F11) - Simplified
// ══════════════════════════════════════════════════════════════════════

function initRiwayat() {
    updateThead('riwayat');
    injectToolbarRiwayat();
    loadRiwayat();
}

async function loadRiwayat(page = 1) {
    currentPage = page;
    showSkeleton(9, 'tbody-riwayat-absensi');

    const bulanPad = String(filterBulan).padStart(2, '0');
    const tanggalDari = `${filterTahun}-${bulanPad}-01`;
    const tanggalSampai = `${filterTahun}-${bulanPad}-${String(new Date(filterTahun, filterBulan, 0).getDate()).padStart(2, '0')}`;
    const params = new URLSearchParams({ page, tanggal_dari: tanggalDari, tanggal_sampai: tanggalSampai });
    if (filterKaryawan) params.set('search', filterKaryawan);

    try {
        const res = await apiFetch(`/api/admin/validasi-absensi?${params}`);
        const json = await res.json();
        if (!json.status) { toast(json.message, 'error'); showEmpty(9, 'tbody-riwayat-absensi', 'Tidak ada data.'); return; }
        renderRiwayat(json.data?.data ?? json.data ?? []);
        if (json.data?.last_page) renderPaginasi(json.data, 'paginasi-riwayat', loadRiwayat);
    } catch (err) {
        console.error('[Riwayat] error:', err);
    }
}

function renderRiwayat(rows) {
    const tbody = document.getElementById('tbody-riwayat-absensi');
    if (!tbody) return;
    if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">
            Tidak ada data absensi pada periode ini.</td></tr>`;
        return;
    }
    tbody.innerHTML = rows.map(row => {
        const namaKaryawan = row.karyawan?.nama_lengkap ?? '-';
        const nomorKaryawan = row.karyawan?.nomor_karyawan ?? '';
        const namaShift = row.shift?.nama_shift ?? '-';
        return `<tr>
            <td><div style="display:flex;align-items:center;gap:8px;">
                <div style="width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,#1a6e1a,#0a280a);
                    display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;
                    font-size:11px;font-weight:700;color:#87dc87;">${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}</div>
                <div><div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                <div style="font-size:11px;color:#94a3b8;">${esc(nomorKaryawan)}</div></div></div></td>
            <td style="font-size:12px;color:#475569;">${fmtTanggal(row.tanggal_absensi)}</td>
            <td style="font-size:12px;color:#475569;">${esc(namaShift)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">${fmtWaktu(row.waktu_check_in)}</td>
            <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">${fmtWaktu(row.waktu_check_out)}</td>
            <td style="font-size:12px;color:#475569;">${fmtMenit(row.menit_kerja_normal)}</td>
            <td>${(row.menit_telat ?? 0) > 0 ? `<span style="font-size:12px;font-weight:600;color:#d97706;">+${row.menit_telat} mnt</span>` : `<span style="color:#94a3b8;font-size:12px;">—</span>`}</td>
            <td>${badgeKehadiran(row.status_kehadiran)}</td>
            <td>${badgeValidasi(row.status_validasi)}</td>
        </tr>`;
    }).join('');
}

function injectToolbarRiwayat() {
    const header = document.querySelector('.page-header');
    if (!header || document.getElementById('filter-bulan-riwayat')) return;
    const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const bulanOpts = namaBulan.map((b, i) => `<option value="${i+1}" ${i+1 === filterBulan ? 'selected' : ''}>${b}</option>`).join('');
    const tahunOpts = [0,1,2].map(n => {
        const t = new Date().getFullYear() - n;
        return `<option value="${t}" ${t === filterTahun ? 'selected' : ''}>${t}</option>`;
    }).join('');
    const wrap = document.createElement('div');
    wrap.className = 'ao-toolbar';
    wrap.innerHTML = `
        <input id="search-karyawan-riwayat" class="ao-search" type="text" placeholder="Cari nama karyawan..." style="width:200px;">
        <select id="filter-bulan-riwayat" class="ao-select">${bulanOpts}</select>
        <select id="filter-tahun-riwayat" class="ao-select">${tahunOpts}</select>
    `;
    header.after(wrap);
    wrap.querySelector('#search-karyawan-riwayat')?.addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { filterKaryawan = e.target.value.trim(); loadRiwayat(1); }, 400);
    });
    wrap.querySelector('#filter-bulan-riwayat')?.addEventListener('change', e => { filterBulan = parseInt(e.target.value); loadRiwayat(1); });
    wrap.querySelector('#filter-tahun-riwayat')?.addEventListener('change', e => { filterTahun = parseInt(e.target.value); loadRiwayat(1); });
}

// ══════════════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════

function updateThead(mode) {
    const thead = document.querySelector('.data-table thead tr');
    if (!thead) return;
    if (mode === 'riwayat') {
        thead.innerHTML = `
            <th>Karyawan</th><th>Tanggal</th><th>Shift</th><th>Check-In</th><th>Check-Out</th>
            <th>Menit Normal</th><th>Menit Telat</th><th>Status Kehadiran</th><th>Status Validasi</th>`;
    } else {
        // Sudah ada di HTML dengan checkbox column
    }
}

function showSkeleton(cols, tbodyId) {
    const tbody = document.getElementById(tbodyId) ?? document.querySelector('.data-table tbody');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(
        `<tr>${Array(cols).fill('<td><div class="skel" style="height:10px;border-radius:4px;width:80%;"></div></td>').join('')}</tr>`
    ).join('');
}

function showEmpty(cols, tbodyId, msg) {
    const tbody = document.getElementById(tbodyId) ?? document.querySelector('.data-table tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">${msg}</td></tr>`;
}

function getTodayDateString() {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
}

function getLokasiValid(row) {
    const inValid = row.is_lokasi_valid_in;
    const outValid = row.is_lokasi_valid_out;
    if (outValid === null || outValid === undefined) {
        if (inValid === null || inValid === undefined) return null;
        return Boolean(inValid);
    }
    return Boolean(inValid) && Boolean(outValid);
}

function renderLokasiBadge(valid) {
    if (valid === null || valid === undefined) return `<span class="badge badge--neutral">-</span>`;
    return valid ? `<span class="badge badge--success">Valid</span>` : `<span class="badge badge--danger">Tidak Valid</span>`;
}

function getStatusTelat(absensi) {
    const menit = absensi.menit_telat ?? 0;
    if (menit > 0) return `<span style="color:#d97706;font-size:11px;">(Terlambat ${menit} menit)</span>`;
    return `<span style="color:#10b981;font-size:11px;">(Tepat Waktu)</span>`;
}

// Expose ke window untuk onclick handlers
window.handleSingleApprove = handleSingleApprove;
window.handleSingleReject = handleSingleReject;
window.confirmBulkApprove = confirmBulkApprove;
window.proceedBulkReject = proceedBulkReject;
window.confirmBulkRejectSame = confirmBulkRejectSame;
window.confirmBulkRejectIndividual = confirmBulkRejectIndividual;

// ══════════════════════════════════════════════════════════════════════
//  INJECT ADDITIONAL STYLES
// ══════════════════════════════════════════════════════════════════════

(function injectAccordionStyles() {
    if (document.getElementById('validasi-absensi-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'validasi-absensi-styles';
    style.textContent = `
        .accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .accordion-header {
            padding: 12px 16px;
            background: #f8fafc;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        
        .accordion-header:hover {
            background: #f1f5f9;
        }
        
        .accordion-icon {
            font-size: 12px;
            color: #64748b;
            transition: transform 0.2s;
        }
        
        .accordion-body {
            padding: 16px;
            display: none;
            background: #fff;
        }
        
        #bulk-action-bar {
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-primary {
            padding: 9px 20px;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
    `;
    document.head.appendChild(style);
})();
