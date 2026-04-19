/**
 * resources/js/karyawan/izin.js
 *
 * Halaman Pengajuan Izin (F04, F05).
 * Fitur:
 *   - Dropdown jenis izin dari GET /api/karyawan/jenis-izin
 *   - Input range tanggal: tanggal mulai + tanggal selesai (bisa sama untuk 1 hari)
 *   - Preview jumlah hari izin secara real-time
 *   - Notifikasi wajib dokumen saat jenis memerlukan upload
 *   - Form submit via JSON ke POST /api/karyawan/izin
 *   - Setelah submit berhasil → pindah otomatis ke tab Riwayat
 *   - Tab riwayat: daftar pengajuan izin + paginasi
 *   - Modal upload dokumen via FormData ke POST /api/karyawan/izin/{id}/dokumen
 *   - Drag-and-drop dan klik file drop zone
 *   - Preview nama file sebelum upload
 *
 * FIX: Bug modal tombol batal/tutup/upload tidak berfungsi karena
 *      input[type="file"] dengan position:absolute menutupi seluruh drop zone.
 *      Solusi: pointer-events dinonaktifkan di level drop zone, file input
 *      hanya dipicu secara programatik via klik pada drop zone label.
 *
 * Endpoints:
 *   GET  /api/karyawan/jenis-izin            → lookup dropdown
 *   GET  /api/karyawan/izin?status=X&page=Y  → riwayat izin
 *   POST /api/karyawan/izin                  → submit pengajuan
 *   POST /api/karyawan/izin/{id}/dokumen     → upload dokumen (FormData)
 */

'use strict';

import {
    apiFetch,
    toast,
    formatDate,
    getBadgeHtml,
    renderPagination,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. STATE
// ══════════════════════════════════════════════════════════════════════════════

const state = {
    activeTab:    'form',   // 'form' | 'riwayat'
    riwayatPage:  1,
    filterStatus: '',
    isSubmitting: false,
    jenisIzinList: [],      // cache dropdown jenis izin
    uploadTargetIzinId: null,
    uploadFile: null,
    isUploading: false,
};

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    loadJenisIzin();
    bindTabs();
    bindForm();
    bindRiwayat();
    bindModal();
    loadRiwayatIzin(1);
    countPending();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. TAB MANAGEMENT
// ══════════════════════════════════════════════════════════════════════════════

function bindTabs() {
    document.getElementById('tab-form-izin')?.addEventListener('click',    () => switchTab('form'));
    document.getElementById('tab-riwayat-izin')?.addEventListener('click', () => switchTab('riwayat'));
}

function switchTab(tab) {
    state.activeTab = tab;
    const panels = { form: 'panel-form-izin', riwayat: 'panel-riwayat-izin' };
    const tabs   = { form: 'tab-form-izin',   riwayat: 'tab-riwayat-izin' };

    Object.entries(panels).forEach(([key, panelId]) => {
        const panelEl = document.getElementById(panelId);
        const tabEl   = document.getElementById(tabs[key]);
        if (panelEl) panelEl.style.display = key === tab ? '' : 'none';
        if (tabEl) {
            tabEl.classList.toggle('k-tab--active', key === tab);
            tabEl.setAttribute('aria-selected', String(key === tab));
        }
    });
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. DROPDOWN JENIS IZIN
// ══════════════════════════════════════════════════════════════════════════════

async function loadJenisIzin() {
    try {
        const res = await apiFetch('/api/karyawan/jenis-izin');
        if (!res.status) return;

        state.jenisIzinList = res.data ?? [];
        const select = document.getElementById('izin-jenis');
        if (!select) return;

        state.jenisIzinList.forEach((j) => {
            const opt = document.createElement('option');
            opt.value       = j.id_jenis_izin;
            opt.textContent = j.nama_jenis + (j.wajib_dokumen ? ' (dokumen wajib)' : '');
            opt.dataset.wajibDokumen = j.wajib_dokumen ? '1' : '0';
            opt.dataset.keterangan   = j.keterangan ?? '';
            select.appendChild(opt);
        });

    } catch {
        toast('Gagal memuat jenis izin.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. FORM PENGAJUAN
// ══════════════════════════════════════════════════════════════════════════════

function bindForm() {
    const jenisSelect    = document.getElementById('izin-jenis');
    const tanggalMulai   = document.getElementById('izin-tanggal-mulai');
    const tanggalSelesai = document.getElementById('izin-tanggal-selesai');
    const keteranganEl   = document.getElementById('izin-keterangan');
    const resetBtn       = document.getElementById('btn-reset-izin');
    const form           = document.getElementById('form-izin');

    jenisSelect?.addEventListener('change', onJenisChange);

    // Saat tanggal mulai berubah: update min tanggal selesai + hitung hari
    tanggalMulai?.addEventListener('change', () => {
        const val = tanggalMulai.value;
        if (tanggalSelesai) {
            tanggalSelesai.min = val;
            // Jika tanggal selesai sudah diisi tapi lebih awal dari mulai → reset
            if (tanggalSelesai.value && tanggalSelesai.value < val) {
                tanggalSelesai.value = val;
            }
        }
        updateJumlahHariPreview();
    });

    // Saat tanggal selesai berubah: hitung hari
    tanggalSelesai?.addEventListener('change', updateJumlahHariPreview);

    keteranganEl?.addEventListener('input', () => {
        const counter = document.getElementById('keterangan-count');
        if (counter) counter.textContent = keteranganEl.value.length;
    });

    resetBtn?.addEventListener('click', resetForm);
    form?.addEventListener('submit', handleFormSubmit);
}

/**
 * Update preview jumlah hari secara real-time.
 */
function updateJumlahHariPreview() {
    const mulaiEl   = document.getElementById('izin-tanggal-mulai');
    const selesaiEl = document.getElementById('izin-tanggal-selesai');
    const previewEl = document.getElementById('izin-jumlah-hari-preview');
    const angkaEl   = document.getElementById('izin-jumlah-hari-angka');

    if (!mulaiEl?.value || !previewEl || !angkaEl) return;

    const mulai   = new Date(mulaiEl.value + 'T00:00:00');
    // Jika tanggal selesai kosong, anggap 1 hari
    const selesai = selesaiEl?.value
        ? new Date(selesaiEl.value + 'T00:00:00')
        : mulai;

    const hari = Math.round((selesai - mulai) / (1000 * 60 * 60 * 24)) + 1;

    angkaEl.textContent = hari > 1 ? `${hari} hari` : '1 hari';
    previewEl.style.display = mulaiEl.value ? 'flex' : 'none';
}

function onJenisChange(e) {
    const selected = e.target.options[e.target.selectedIndex];
    const wajib    = selected?.dataset.wajibDokumen === '1';
    const infoEl   = document.getElementById('izin-wajib-dokumen-info');
    if (infoEl) infoEl.style.display = wajib ? '' : 'none';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    if (state.isSubmitting) return;

    hideFormAlert();
    clearFieldErrors();

    const form = e.target;
    const tanggalMulai   = form.tanggal_izin?.value;
    const tanggalSelesai = form.tanggal_selesai_izin?.value || null;

    const data = {
        id_jenis_izin:        parseInt(form.id_jenis_izin?.value, 10) || null,
        tanggal_izin:         tanggalMulai,
        tanggal_selesai_izin: tanggalSelesai,
        keterangan:           form.keterangan?.value?.trim() || null,
    };

    if (!validateIzinForm(data)) return;

    state.isSubmitting = true;
    setSubmitLoading(true);

    try {
        const res = await apiFetch('/api/karyawan/izin', {
            method: 'POST',
            body: data,
        });

        if (!res.status) {
            if (res.data && typeof res.data === 'object') renderFieldErrors(res.data);
            showFormAlert(res.message ?? 'Gagal mengajukan izin.', 'error');
            return;
        }

        toast(res.message ?? 'Pengajuan izin berhasil dikirim.', 'success', 5000);

        // Periksa apakah jenis izin wajib dokumen
        const izinId = res.data?.id_izin;
        const selected = document.getElementById('izin-jenis')?.options[
            document.getElementById('izin-jenis')?.selectedIndex
        ];
        const wajib = selected?.dataset.wajibDokumen === '1';

        resetForm();

        // Pindah ke tab riwayat
        switchTab('riwayat');
        await loadRiwayatIzin(1);
        countPending();

        // Jika wajib dokumen, langsung buka modal upload untuk izin baru
        if (wajib && izinId) {
            setTimeout(() => openUploadModal(izinId, res.data), 800);
        }

    } catch (err) {
        showFormAlert(err.message, 'error');
    } finally {
        state.isSubmitting = false;
        setSubmitLoading(false);
    }
}

function validateIzinForm(data) {
    let valid = true;

    if (!data.id_jenis_izin) {
        showFieldError('err-izin-jenis', 'Jenis izin wajib dipilih.');
        valid = false;
    }
    if (!data.tanggal_izin) {
        showFieldError('err-izin-tanggal-mulai', 'Tanggal mulai izin wajib diisi.');
        valid = false;
    }
    // Validasi: tanggal selesai tidak boleh sebelum tanggal mulai
    if (data.tanggal_selesai_izin && data.tanggal_izin &&
        data.tanggal_selesai_izin < data.tanggal_izin) {
        showFieldError('err-izin-tanggal-selesai', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        valid = false;
    }

    return valid;
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. RIWAYAT IZIN
// ══════════════════════════════════════════════════════════════════════════════

function bindRiwayat() {
    document.getElementById('filter-status-izin')?.addEventListener('change', (e) => {
        state.filterStatus = e.target.value;
        loadRiwayatIzin(1);
    });
}

async function loadRiwayatIzin(page) {
    state.riwayatPage = page;
    const container  = document.getElementById('riwayat-izin-list');
    const pagEl      = document.getElementById('paginasi-izin');
    if (!container) return;

    container.innerHTML = _skeletonIzin(3);

    let url = `/api/karyawan/izin?page=${page}`;
    if (state.filterStatus) url += `&status=${state.filterStatus}`;

    try {
        const res = await apiFetch(url);

        if (!res.status) {
            container.innerHTML = _emptyIzin('Gagal memuat riwayat izin.');
            return;
        }

        const list = res.data?.data ?? [];
        const meta = res.data;

        if (list.length === 0) {
            container.innerHTML = _emptyIzin('Tidak ada pengajuan izin.');
            if (pagEl) pagEl.innerHTML = '';
            return;
        }

        container.innerHTML = list.map((i) => _renderIzinItem(i)).join('');

        // Bind tombol upload dokumen setelah render
        container.querySelectorAll('[data-upload-izin-id]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id      = parseInt(btn.dataset.uploadIzinId, 10);
                const izinObj = list.find((i) => i.id_izin === id);
                openUploadModal(id, izinObj);
            });
        });

        if (pagEl) {
            renderPagination(pagEl, meta, loadRiwayatIzin);
        }

    } catch (err) {
        container.innerHTML = _emptyIzin(err.message);
    }
}

async function countPending() {
    try {
        const res = await apiFetch('/api/karyawan/izin?status=menunggu');
        if (!res.status) return;
        const total = res.data?.total ?? 0;
        const badge = document.getElementById('badge-izin-pending');
        if (badge) {
            badge.textContent   = total;
            badge.style.display = total > 0 ? '' : 'none';
        }
    } catch { /* silent */ }
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. MODAL UPLOAD DOKUMEN (F05)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * FIX: Root cause bug tombol batal/tutup/upload tidak berfungsi:
 *
 * Di blade sebelumnya, input[type="file"] punya style:
 *   position:absolute; inset:0; opacity:0; width:100%; height:100%
 * Ini membuat input menutup SELURUH AREA MODAL, sehingga klik tombol
 * apapun (batal, tutup, upload) justru memicu file picker.
 *
 * FIX: Input file TIDAK lagi di-overlay sebagai full-area click trap.
 * Sebagai gantinya, klik pada drop zone dipicu programatik via JS
 * saat user klik label/area yang visible. Input file tetap ada tapi
 * hanya bisa diakses via fileInput.click() yang dipanggil JS.
 * Tombol modal lain tidak terhalang sama sekali.
 */
function bindModal() {
    const modal      = document.getElementById('modal-upload-dokumen');
    const closeBtn   = document.getElementById('btn-close-modal-dokumen');
    const cancelBtn  = document.getElementById('btn-cancel-upload');
    const confirmBtn = document.getElementById('btn-confirm-upload');
    const dropZone   = document.getElementById('file-drop-zone');
    const fileInput  = document.getElementById('input-dokumen-file');

    // ── Tutup modal (event listener ini sekarang bisa di-klik karena input tidak menghalangi) ──
    const closeModal = () => {
        modal?.classList.remove('k-modal--open');
        // Reset state upload saat modal ditutup
        state.uploadFile = null;
        state.uploadTargetIzinId = null;
    };

    closeBtn?.addEventListener('click',  closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    // Tutup saat klik overlay (bukan box modal-nya)
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // ESC untuk tutup modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal?.classList.contains('k-modal--open')) {
            closeModal();
        }
    });

    // ── Klik pada drop zone → trigger file input secara programatik ──
    // Input TIDAK lagi di-overlay; kita pakai klik programatik agar
    // tombol lain di luar drop zone tetap bisa diklik normal.
    dropZone?.addEventListener('click', (e) => {
        // Pastikan klik bukan dari tombol remove-file di dalam drop zone
        if (e.target.closest('#btn-remove-file')) return;
        fileInput?.click();
    });

    // Keyboard support untuk drop zone (Enter/Space memicu file picker)
    dropZone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput?.click();
        }
    });

    // Drag and drop
    dropZone?.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('k-file-drop--hover');
    });
    dropZone?.addEventListener('dragleave', () => {
        dropZone.classList.remove('k-file-drop--hover');
    });
    dropZone?.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('k-file-drop--hover');
        const file = e.dataTransfer.files?.[0];
        if (file) setUploadFile(file);
    });

    // File input change (dipicu oleh fileInput.click())
    fileInput?.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (file) setUploadFile(file);
        // Reset value agar onChange terpicu lagi jika user pilih file sama
        e.target.value = '';
    });

    // Confirm upload
    confirmBtn?.addEventListener('click', handleUploadConfirm);
}

function openUploadModal(izinId, izinData) {
    state.uploadTargetIzinId = izinId;
    state.uploadFile = null;

    const modal   = document.getElementById('modal-upload-dokumen');
    const infoEl  = document.getElementById('modal-izin-info');
    const preview = document.getElementById('upload-file-preview');
    const confirm = document.getElementById('btn-confirm-upload');
    const input   = document.getElementById('input-dokumen-file');

    if (infoEl) {
        const jenis        = izinData?.jenis_izin?.nama_jenis ?? '—';
        const tanggalMulai = formatDate(izinData?.tanggal_izin);
        const tanggalAkhir = izinData?.tanggal_selesai_izin && izinData.tanggal_selesai_izin !== izinData.tanggal_izin
            ? ' – ' + formatDate(izinData.tanggal_selesai_izin)
            : '';
        infoEl.textContent = `${jenis} — ${tanggalMulai}${tanggalAkhir}`;
    }

    if (preview) preview.innerHTML = '';
    if (confirm) confirm.disabled = true;
    if (input)   input.value = '';

    hideUploadAlert();
    modal?.classList.add('k-modal--open');
}

function setUploadFile(file) {
    const ALLOWED = ['pdf', 'jpg', 'jpeg', 'png'];
    const ext     = file.name.split('.').pop().toLowerCase();
    const maxKb   = 2048; // 2 MB

    const preview = document.getElementById('upload-file-preview');
    const confirm = document.getElementById('btn-confirm-upload');

    if (!ALLOWED.includes(ext)) {
        showUploadAlert('Format file tidak didukung. Gunakan PDF, JPG, atau PNG.', 'error');
        state.uploadFile = null;
        if (confirm) confirm.disabled = true;
        if (preview) preview.innerHTML = '';
        return;
    }

    if (file.size > maxKb * 1024) {
        showUploadAlert(`Ukuran file terlalu besar (maks. 2 MB). File Anda: ${(file.size / 1024 / 1024).toFixed(1)} MB.`, 'error');
        state.uploadFile = null;
        if (confirm) confirm.disabled = true;
        if (preview) preview.innerHTML = '';
        return;
    }

    hideUploadAlert();
    state.uploadFile = file;
    if (confirm) confirm.disabled = false;

    const sizeKb = (file.size / 1024).toFixed(0);
    if (preview) {
        preview.innerHTML = `
            <div class="k-file-item">
                <span class="k-file-item-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7 21h10a2 2 0 0 0 2-2V9.414a1 1 0 0 0-.293-.707l-5.414-5.414A1 1 0 0 0 13.586 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                    </svg>
                </span>
                <span class="k-file-item-name">${_escapeHtml(file.name)}</span>
                <span class="k-file-item-size">${sizeKb} KB</span>
                <button type="button" class="k-file-item-remove" id="btn-remove-file"
                        aria-label="Hapus file yang dipilih">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>`;

        // Bind tombol hapus file — stopPropagation agar klik tidak memicu drop zone
        document.getElementById('btn-remove-file')?.addEventListener('click', (e) => {
            e.stopPropagation();
            state.uploadFile = null;
            preview.innerHTML = '';
            if (confirm) confirm.disabled = true;
            const input = document.getElementById('input-dokumen-file');
            if (input) input.value = '';
        });
    }
}

async function handleUploadConfirm() {
    if (!state.uploadFile || !state.uploadTargetIzinId || state.isUploading) return;

    state.isUploading = true;
    setUploadLoading(true);

    const formData = new FormData();
    formData.append('dokumen', state.uploadFile);

    try {
        const res = await apiFetch(`/api/karyawan/izin/${state.uploadTargetIzinId}/dokumen`, {
            method: 'POST',
            body:   formData,
        });

        if (!res.status) {
            showUploadAlert(res.message ?? 'Gagal mengunggah dokumen.', 'error');
            return;
        }

        toast('Dokumen berhasil diunggah.', 'success');
        document.getElementById('modal-upload-dokumen')?.classList.remove('k-modal--open');

        state.uploadFile = null;
        state.uploadTargetIzinId = null;

        // Reload riwayat untuk tampilkan status dokumen terbaru
        loadRiwayatIzin(state.riwayatPage);

    } catch (err) {
        showUploadAlert(err.message, 'error');
    } finally {
        state.isUploading = false;
        setUploadLoading(false);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. RENDER HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function _renderIzinItem(i) {
    const statusIcon = {
        menunggu:  'k-pengajuan-icon--menunggu',
        disetujui: 'k-pengajuan-icon--disetujui',
        ditolak:   'k-pengajuan-icon--ditolak',
    }[i.status] ?? 'k-pengajuan-icon--menunggu';

    // Tombol upload dokumen — tampil jika menunggu dan dokumen belum lengkap
    const canUpload = i.status === 'menunggu' &&
        ['belum_upload', 'sudah_upload'].includes(i.status_dokumen);

    const uploadBtn = canUpload ? `
        <button class="k-icon-btn k-icon-btn--upload"
                data-upload-izin-id="${i.id_izin}"
                title="Upload Dokumen"
                aria-label="Upload dokumen untuk izin ini">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </button>` : '';

    const dokStatus = {
        belum_upload:  '<span style="font-size:11px;color:var(--status-alpa);">⚠ Dokumen belum upload</span>',
        sudah_upload:  '<span style="font-size:11px;color:var(--status-telat);">📎 Sudah upload, menunggu verifikasi</span>',
        lengkap:       '<span style="font-size:11px;color:var(--status-hadir);">✓ Dokumen lengkap</span>',
        tidak_lengkap: '<span style="font-size:11px;color:var(--status-alpa);">✕ Dokumen tidak lengkap</span>',
    }[i.status_dokumen] ?? '';

    // Label tanggal: tampilkan range jika multi-hari
    const tanggalLabel = (i.tanggal_selesai_izin && i.tanggal_selesai_izin !== i.tanggal_izin)
        ? `${formatDate(i.tanggal_izin)} – ${formatDate(i.tanggal_selesai_izin)}`
        : formatDate(i.tanggal_izin);

    const hariLabel = i.jumlah_hari > 1
        ? `<span style="font-size:11px;color:var(--eco-600);font-weight:600;">${i.jumlah_hari} hari</span>
           <span class="k-pengajuan-meta-dot" aria-hidden="true"></span>`
        : '';

    return `
        <div class="k-pengajuan-item">
            <div class="k-pengajuan-icon ${statusIcon}" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
            </div>
            <div class="k-pengajuan-body">
                <p class="k-pengajuan-title">
                    ${_escapeHtml(i.jenis_izin?.nama_jenis ?? '—')} &mdash;
                    ${tanggalLabel}
                </p>
                <div class="k-pengajuan-meta">
                    ${hariLabel}
                    ${i.keterangan ? `<span>${_escapeHtml(i.keterangan)}</span>
                        <span class="k-pengajuan-meta-dot" aria-hidden="true"></span>` : ''}
                    ${dokStatus}
                </div>
                ${i.catatan_penolakan ? `
                    <p style="font-size:11px;color:var(--status-alpa);margin-top:3px;">
                        Catatan: ${_escapeHtml(i.catatan_penolakan)}
                    </p>` : ''}
            </div>
            <div class="k-pengajuan-actions">
                ${getBadgeHtml(i.status, 'validasi')}
                ${uploadBtn}
            </div>
        </div>`;
}

function _skeletonIzin(count) {
    return Array.from({ length: count }).map(() => `
        <div class="k-pengajuan-item">
            <div class="k-skel k-skel--block"
                 style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                <div class="k-skel k-skel--text" style="width:60%;"></div>
                <div class="k-skel k-skel--text" style="width:40%;"></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                <div class="k-skel" style="width:60px;height:20px;border-radius:999px;"></div>
                <div class="k-skel" style="width:44px;height:26px;border-radius:var(--radius-md);"></div>
            </div>
        </div>`).join('');
}

function _emptyIzin(msg) {
    return `<div class="k-empty" style="padding:var(--space-6) var(--space-4);">
                <p class="k-empty-title">${_escapeHtml(msg)}</p>
            </div>`;
}

// ══════════════════════════════════════════════════════════════════════════════
// 9. FORM & MODAL HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function resetForm() {
    document.getElementById('form-izin')?.reset();
    const counter = document.getElementById('keterangan-count');
    if (counter) counter.textContent = '0';
    const wajibInfo = document.getElementById('izin-wajib-dokumen-info');
    if (wajibInfo) wajibInfo.style.display = 'none';
    const hariPreview = document.getElementById('izin-jumlah-hari-preview');
    if (hariPreview) hariPreview.style.display = 'none';
    hideFormAlert();
    clearFieldErrors();
}

function showFormAlert(msg, type = 'error') {
    const el = document.getElementById('izin-alert');
    if (!el) return;
    el.className = `k-alert k-alert--${type}`;
    el.innerHTML = `
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        <span>${_escapeHtml(msg)}</span>`;
    el.style.display = 'flex';
}

function hideFormAlert() {
    const el = document.getElementById('izin-alert');
    if (el) el.style.display = 'none';
}

function showUploadAlert(msg, type = 'error') {
    const el = document.getElementById('upload-alert');
    if (!el) return;
    el.className = `k-alert k-alert--${type}`;
    el.innerHTML = `<span>${_escapeHtml(msg)}</span>`;
    el.style.display = 'flex';
}

function hideUploadAlert() {
    const el = document.getElementById('upload-alert');
    if (el) el.style.display = 'none';
}

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.add('k-field-error--visible');
}

function clearFieldErrors() {
    document.querySelectorAll('#form-izin .k-field-error').forEach((el) => {
        el.textContent = '';
        el.classList.remove('k-field-error--visible');
    });
}

function renderFieldErrors(errors) {
    const fieldMap = {
        id_jenis_izin:        'err-izin-jenis',
        tanggal_izin:         'err-izin-tanggal-mulai',
        tanggal_selesai_izin: 'err-izin-tanggal-selesai',
        keterangan:           'err-izin-keterangan',
    };
    Object.entries(errors).forEach(([field, msgs]) => {
        const id = fieldMap[field];
        if (id) showFieldError(id, Array.isArray(msgs) ? msgs[0] : msgs);
    });
}

function setSubmitLoading(isLoading) {
    const btn     = document.getElementById('btn-submit-izin');
    const text    = document.getElementById('submit-izin-text');
    const spinner = document.getElementById('spinner-izin');
    if (!btn) return;
    btn.disabled = isLoading;
    if (text)    text.style.display    = isLoading ? 'none' : '';
    if (spinner) spinner.classList.toggle('k-btn-spinner--visible', isLoading);
}

function setUploadLoading(isLoading) {
    const btn     = document.getElementById('btn-confirm-upload');
    const text    = document.getElementById('upload-text');
    const spinner = document.getElementById('spinner-upload');
    if (!btn) return;
    btn.disabled = isLoading;
    if (text)    text.style.display    = isLoading ? 'none' : '';
    if (spinner) spinner.classList.toggle('k-btn-spinner--visible', isLoading);
}