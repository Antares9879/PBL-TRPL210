/**
 * resources/js/hr/dokumen.js
 * Verifikasi Dokumen Izin HR - Bulk Action + Detail Verifikasi
 */

window.clearBulkSelection = function() {
    // Reset state
    state.selectedIds.clear();

    // Uncheck semua row checkbox
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);

    // Uncheck select-all
    if (el.selectAllHeader) el.selectAllHeader.checked = false;
    if (el.selectAllTop)    el.selectAllTop.checked    = false;

    // Sembunyikan toolbar
    const bar = document.getElementById('bulk-action-bar');
    if (bar) bar.style.display = 'none';
};


const state = {
    page: 1,
    filters: {
        bulan: '',
        tahun: '',
        jenisIzin: '',
        statusDokumen: '',
        statusValidasiAdmin: '',
        perusahaan: '',
        departemen: '',
        search: ''
    },
    selectedIds: new Set(),
    selectedBulkAction: '',
    selectedIzinId: null,
    selectedAksi: null
};

const el = {};
const BULAN_LABEL = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

document.addEventListener('DOMContentLoaded', () => {
    cacheElements();
    setupDefaultFilters();
    bindEvents();
    loadFilterOptions();
    loadPengajuanIzin(1);
});

function cacheElements() {
    el.filterBulan = document.getElementById('filter-bulan');
    el.filterTahun = document.getElementById('filter-tahun');
    el.filterJenisIzin = document.getElementById('filter-jenis-izin');
    el.filterStatusDokumen = document.getElementById('filter-status-dokumen');
    el.filterStatusValidasiAdmin = document.getElementById('filter-status-validasi-admin');
    el.filterPerusahaan = document.getElementById('filter-perusahaan');
    el.filterDepartemen = document.getElementById('filter-departemen');
    el.filterSearch = document.getElementById('filter-search');
    el.btnTerapkan = document.getElementById('btn-terapkan-filter-detail');
    el.btnReset = document.getElementById('btn-reset-filter');

    el.selectAllHeader = document.getElementById('select-all-header');
    el.selectAllTop = document.getElementById('select-all-checkbox');

    el.tbody = document.getElementById('tbody-pengajuan-izin');
    el.paginasi = document.getElementById('paginasi-pengajuan');

    el.modalBulk = document.getElementById('modal-bulk-konfirmasi');
    el.modalBulkTitle = document.getElementById('modal-bulk-title');
    el.modalBulkBody = document.getElementById('modal-bulk-body');
    el.inputBulkCatatan = document.getElementById('input-bulk-catatan');
    el.btnBulkBatal = document.getElementById('btn-bulk-batal');
    el.btnBulkSubmit = document.getElementById('btn-bulk-submit');

    el.modalDetail = document.getElementById('modal-detail-izin');
    el.modalDetailBody = document.getElementById('modal-detail-body');

    el.modalVerifikasi = document.getElementById('modal-konfirmasi-verifikasi');
    el.modalVerifikasiTitle = document.getElementById('modal-verifikasi-title');
    el.modalVerifikasiBody = document.getElementById('modal-konfirmasi-verifikasi-body');
    el.inputCatatanDokumen = document.getElementById('input-catatan-dokumen');
    el.btnBatalVerifikasi = document.getElementById('btn-batal-verifikasi');
    el.btnSubmitVerifikasi = document.getElementById('btn-submit-verifikasi');

    el.lightbox = document.getElementById('lightbox-dokumen');
    el.lightboxContent = document.getElementById('lightbox-content');
    el.lightboxNamaFile = document.getElementById('lightbox-nama-file');
    el.btnLightboxTabBaru = document.getElementById('btn-lightbox-tab-baru');
    el.btnLightboxClose = document.getElementById('btn-lightbox-close');
}

function setupDefaultFilters() {
    const now = new Date();
    const month = String(now.getMonth() + 1);
    const year = String(now.getFullYear());

    populateTahunOptions(now.getFullYear());

    state.filters.bulan = month;
    state.filters.tahun = year;

    if (el.filterBulan) el.filterBulan.value = month;
    if (el.filterTahun) el.filterTahun.value = year;
}

function populateTahunOptions(currentYear) {
    if (!el.filterTahun) return;

    el.filterTahun.innerHTML = '<option value="">Semua Tahun</option>';
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        el.filterTahun.appendChild(opt);
    }
}

async function loadFilterOptions() {
    try {
        const json = await apiFetch('/api/hr/dashboard/filter-options');
        if (!json.status || !json.data) return;

        const perusahaan = json.data.perusahaan || [];
        const departemen = json.data.departemen || [];

        perusahaan.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.id_perusahaan;
            opt.textContent = p.nama_perusahaan;
            el.filterPerusahaan?.appendChild(opt);
        });

        departemen.forEach((d) => {
            const opt = document.createElement('option');
            opt.value = d.id_departemen;
            opt.textContent = d.nama_departemen;
            el.filterDepartemen?.appendChild(opt);
        });
    } catch (err) {
        console.error('[HR Dokumen] loadFilterOptions', err);
    }
}

async function loadPengajuanIzin(page = 1) {
    state.page = page;
    showSkeleton();
    clearSelection();

    const params = new URLSearchParams();
    params.set('page', String(page));

    if (state.filters.bulan) params.set('bulan', state.filters.bulan);
    if (state.filters.tahun) params.set('tahun', state.filters.tahun);
    if (state.filters.jenisIzin) params.set('jenis_izin', state.filters.jenisIzin);
    if (state.filters.statusDokumen) params.set('status_dokumen', state.filters.statusDokumen);
    if (state.filters.statusValidasiAdmin) params.set('status_validasi_admin', state.filters.statusValidasiAdmin);
    if (state.filters.perusahaan) params.set('id_perusahaan', state.filters.perusahaan);
    if (state.filters.departemen) params.set('id_departemen', state.filters.departemen);
    if (state.filters.search) params.set('search', state.filters.search);

    try {
        const json = await apiFetch(`/api/hr/dokumen?${params.toString()}`);
        if (!json.status) {
            toast(json.message || 'Gagal memuat data pengajuan izin.', 'error');
            renderTabel([], null);
            return;
        }

        const paginated = json.data || {};
        renderTabel(paginated.data || [], paginated);
        renderPaginasi(paginated);
        updateBulkUI();
    } catch (err) {
        console.error('[HR Dokumen] loadPengajuanIzin', err);
        toast('Gagal memuat data pengajuan izin.', 'error');
        renderTabel([], null);
    }
}

function renderTabel(rows, meta) {
    if (!el.tbody) return;

    if (!rows.length) {
        el.tbody.innerHTML = `
            <tr>
                <td colspan="11" style="text-align:center;padding:40px;color:#94a3b8;">
                    Tidak ada data pengajuan izin pada filter ini.
                </td>
            </tr>
        `;
        return;
    }

    el.tbody.innerHTML = rows.map((row) => {
        const id = Number(row.id_izin);
        const checked = state.selectedIds.has(id) ? 'checked' : '';
        const selectedClass = state.selectedIds.has(id) ? 'hr-row-selected' : '';
        const karyawan = row.karyawan || {};
        const jenisIzin = row.jenis_izin || {};
        const jumlahDokumen = Number(row.jumlah_dokumen || 0);

        return `
            <tr class="${selectedClass}" data-row-id="${id}">
                <td>
                    <input type="checkbox" class="hr-checkbox row-checkbox" data-id="${id}" ${checked}>
                </td>
                <td>
                    <div style="font-weight:500;color:#0f172a;">${esc(karyawan.nama_lengkap || '-')}</div>
                    <div style="font-size:11px;color:#94a3b8;">${esc(karyawan.nomor_karyawan || '')}</div>
                </td>
                <td style="font-size:12px;">${esc(karyawan.departemen || '-')}</td>
                <td style="font-size:12px;">${esc(karyawan.perusahaan || '-')}</td>
                <td style="font-size:12px;">${esc(jenisIzin.nama_jenis || '-')}</td>
                <td style="font-size:12px;">${formatTanggalRange(row.tanggal_izin, row.tanggal_selesai_izin)}</td>
                <td style="text-align:center;">${row.jumlah_hari || 1}</td>
                <td style="text-align:center;">
                    <button class="hr-btn-sm hr-btn-outline btn-lihat-detail-izin" data-id="${id}">${jumlahDokumen} file</button>
                </td>
                <td>${badgeStatusValidasiAdmin(row.status_validasi_admin || row.status || '')}</td>
                <td>${badgeStatusDokumen(row.status_dokumen || '')}</td>
                <td>
                    <button class="hr-btn-sm hr-btn-primary btn-verifikasi" data-id="${id}">
                        Verifikasi
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPaginasi(meta) {
    if (!el.paginasi) return;

    const current = Number(meta?.current_page || 1);
    const last = Number(meta?.last_page || 1);
    if (last <= 1) {
        el.paginasi.innerHTML = '';
        return;
    }

    const pages = [];
    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - 1 && i <= current + 1)) {
            pages.push(`<button class="hr-paginasi-btn ${i === current ? 'hr-paginasi-btn--active' : ''}" data-page="${i}">${i}</button>`);
        } else if (i === current - 2 || i === current + 2) {
            pages.push('<span style="padding:0 4px;color:#94a3b8;">...</span>');
        }
    }

    el.paginasi.innerHTML = `
        <div class="hr-paginasi">
            <div class="hr-paginasi-info">Halaman ${current} dari ${last}</div>
            <div class="hr-paginasi-buttons">
                <button class="hr-paginasi-btn" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>‹ Prev</button>
                ${pages.join('')}
                <button class="hr-paginasi-btn" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}>Next ›</button>
            </div>
        </div>
    `;
}

function bindEvents() {
    el.btnTerapkan?.addEventListener('click', () => {
        state.filters.bulan = el.filterBulan?.value || '';
        state.filters.tahun = el.filterTahun?.value || '';
        state.filters.jenisIzin = el.filterJenisIzin?.value || '';
        state.filters.statusDokumen = el.filterStatusDokumen?.value || '';
        state.filters.statusValidasiAdmin = el.filterStatusValidasiAdmin?.value || '';
        state.filters.perusahaan = el.filterPerusahaan?.value || '';
        state.filters.departemen = el.filterDepartemen?.value || '';
        state.filters.search = (el.filterSearch?.value || '').trim();

        syncStatusTabs(state.filters.statusDokumen);
        loadPengajuanIzin(1);
    });

    el.btnReset?.addEventListener('click', () => {
        setupDefaultFilters();

        state.filters.jenisIzin = '';
        state.filters.statusDokumen = '';
        state.filters.statusValidasiAdmin = '';
        state.filters.perusahaan = '';
        state.filters.departemen = '';
        state.filters.search = '';

        if (el.filterJenisIzin) el.filterJenisIzin.value = '';
        if (el.filterStatusDokumen) el.filterStatusDokumen.value = '';
        if (el.filterStatusValidasiAdmin) el.filterStatusValidasiAdmin.value = '';
        if (el.filterPerusahaan) el.filterPerusahaan.value = '';
        if (el.filterDepartemen) el.filterDepartemen.value = '';
        if (el.filterSearch) el.filterSearch.value = '';

        syncStatusTabs('');
        loadPengajuanIzin(1);
    });

    document.querySelectorAll('.hr-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const status = tab.dataset.status || '';
            state.filters.statusDokumen = status;
            if (el.filterStatusDokumen) el.filterStatusDokumen.value = status;
            syncStatusTabs(status);
            loadPengajuanIzin(1);
        });
    });

    el.paginasi?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        const page = Number(btn.dataset.page || 1);
        if (page > 0) loadPengajuanIzin(page);
    });

    const handleSelectAll = (checked) => {
        getVisibleRowIds().forEach((id) => {
            if (checked) state.selectedIds.add(id);
            else state.selectedIds.delete(id);
        });
        renderSelectedRows();
        updateBulkUI();
    };

    el.selectAllHeader?.addEventListener('change', (e) => handleSelectAll(e.target.checked));
    el.selectAllTop?.addEventListener('change', (e) => handleSelectAll(e.target.checked));

    el.tbody?.addEventListener('change', (e) => {
        const checkbox = e.target.closest('.row-checkbox');
        if (!checkbox) return;

        const id = Number(checkbox.dataset.id);
        if (!id) return;

        if (checkbox.checked) state.selectedIds.add(id);
        else state.selectedIds.delete(id);

        renderSelectedRows();
        updateBulkUI();
    });

    el.bulkActionSelect?.addEventListener('change', () => {
        state.selectedBulkAction = el.bulkActionSelect.value;
        updateBulkUI();
    });

    el.btnBulkGo?.addEventListener('click', () => {
        if (state.selectedIds.size === 0) return toast('Pilih minimal 1 pengajuan terlebih dahulu.', 'warning');
        if (!state.selectedBulkAction) return toast('Pilih aksi bulk terlebih dahulu.', 'warning');
        openBulkModal();
    });

    el.btnBulkBatal?.addEventListener('click', () => closeModal(el.modalBulk));
    el.btnBulkSubmit?.addEventListener('click', submitBulkVerifikasi);

    document.addEventListener('click', (e) => {
        const verifBtn = e.target.closest('.btn-verifikasi');
        if (verifBtn) {
            const id = Number(verifBtn.dataset.id);
            if (id) lihatDetailIzin(id);
            return;
        }

        const detailBtn = e.target.closest('.btn-lihat-detail-izin');
        if (detailBtn) {
            const id = Number(detailBtn.dataset.id);
            if (id) lihatDetailIzin(id);
            return;
        }

        const btnLengkap = e.target.closest('.btn-tandai-lengkap, .btn-tandai-lengkap-modal');
        if (btnLengkap) {
            konfirmasiVerifikasi(Number(btnLengkap.dataset.id), 'tandai_lengkap', btnLengkap.dataset.nama || '');
            return;
        }

        const btnTidakLengkap = e.target.closest('.btn-tandai-tidak-lengkap, .btn-tandai-tidak-lengkap-modal');
        if (btnTidakLengkap) {
            konfirmasiVerifikasi(Number(btnTidakLengkap.dataset.id), 'tandai_tidak_lengkap', btnTidakLengkap.dataset.nama || '');
            return;
        }

        const btnBatalDetail = e.target.closest('.btn-batal-detail-modal');
        if (btnBatalDetail) {
            closeModal(el.modalDetail);
            return;
        }

        const btnPreview = e.target.closest('.btn-preview-dokumen');
        if (btnPreview) {
            previewDokumen(
                Number(btnPreview.dataset.idIzin),
                Number(btnPreview.dataset.idDok),
                btnPreview.dataset.nama || 'Dokumen',
                btnPreview.dataset.tipe || ''
            );
        }
    });

    el.btnBatalVerifikasi?.addEventListener('click', () => closeModal(el.modalVerifikasi));
    el.btnSubmitVerifikasi?.addEventListener('click', submitVerifikasi);

    el.btnLightboxClose?.addEventListener('click', closeLightbox);
    el.lightbox?.addEventListener('click', (e) => {
        if (e.target === el.lightbox) closeLightbox();
    });

    document.querySelectorAll('.hr-modal').forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.hr-modal, .hr-lightbox').forEach((node) => {
                if (node.style.display !== 'none') node.style.display = 'none';
            });
        }
    });
}

function updateBulkUI() {
    const count = state.selectedIds.size;
    const bar = document.getElementById('bulk-action-bar');
    
    if (bar) bar.style.display = count > 0 ? 'flex' : 'none';
    
    // Update badge count
    const countBadge = document.getElementById('selected-count');
    if (countBadge) countBadge.textContent = count;

    // Sync select-all checkbox
    const visible = getVisibleRowIds();
    const allChecked = visible.length > 0 && visible.every(id => state.selectedIds.has(id));
    if (el.selectAllHeader) el.selectAllHeader.checked = allChecked;
    if (el.selectAllTop) el.selectAllTop.checked = allChecked;
}

// Bind event untuk dua tombol langsung tanpa harus pilih di dropdown
document.getElementById('btn-bulk-lengkap')?.addEventListener('click', () => {
    state.selectedBulkAction = 'tandai_lengkap';
    openBulkModal();
});
document.getElementById('btn-bulk-tidak-lengkap')?.addEventListener('click', () => {
    state.selectedBulkAction = 'tandai_tidak_lengkap';
    openBulkModal();
});

function renderSelectedRows() {
    const rows = el.tbody?.querySelectorAll('tr[data-row-id]') || [];
    rows.forEach((rowEl) => {
        const id = Number(rowEl.dataset.rowId);
        const checked = state.selectedIds.has(id);
        rowEl.classList.toggle('hr-row-selected', checked);
        const box = rowEl.querySelector('.row-checkbox');
        if (box) box.checked = checked;
    });
}

function getVisibleRowIds() {
    const boxes = el.tbody?.querySelectorAll('.row-checkbox') || [];
    return Array.from(boxes)
        .map((node) => Number(node.dataset.id))
        .filter((id) => Number.isFinite(id) && id > 0);
}

function clearSelection() {
    state.selectedIds.clear();
    state.selectedBulkAction = '';
    if (el.bulkActionSelect) el.bulkActionSelect.value = '';
    updateBulkUI();
}

function syncStatusTabs(status) {
    document.querySelectorAll('.hr-tab').forEach((tab) => {
        tab.classList.toggle('hr-tab--active', (tab.dataset.status || '') === status);
    });
}

function openBulkModal() {
    const ids = Array.from(state.selectedIds);
    const aksi = state.selectedBulkAction;

    if (aksi === 'tandai_lengkap') {
        el.modalBulkTitle.textContent = 'Konfirmasi Tandai Lengkap';
        el.modalBulkBody.innerHTML = `<p style="color:#64748b;font-size:13px;">Tandai <strong style="color:#0f172a;">${ids.length} pengajuan</strong> sebagai <strong style="color:#10b981;">Lengkap</strong>?</p>`;
        el.inputBulkCatatan.style.display = 'none';
        el.inputBulkCatatan.value = '';
        el.btnBulkSubmit.className = 'hr-btn-primary';
        el.btnBulkSubmit.textContent = 'Ya, Tandai Lengkap';
    } else {
        el.modalBulkTitle.textContent = 'Konfirmasi Tandai Tidak Lengkap';
        el.modalBulkBody.innerHTML = `
            <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
                Tandai <strong style="color:#0f172a;">${ids.length} pengajuan</strong> sebagai <strong style="color:#ef4444;">Tidak Lengkap</strong>?
            </p>
            <p style="color:#ef4444;font-size:12px;margin:0;">Karyawan terkait akan menerima notifikasi terkait kekurangan dokumen.</p>
        `;
        el.inputBulkCatatan.style.display = 'block';
        el.inputBulkCatatan.value = '';
        el.btnBulkSubmit.className = 'hr-btn-danger';
        el.btnBulkSubmit.textContent = 'Ya, Tandai Tidak Lengkap';
    }

    openModal(el.modalBulk);
}

async function submitBulkVerifikasi() {
    const ids = Array.from(state.selectedIds);
    const aksi = state.selectedBulkAction;
    const catatan = (el.inputBulkCatatan?.value || '').trim();

    if (!ids.length || !aksi) return toast('Aksi bulk tidak valid.', 'error');
    if (aksi === 'tandai_tidak_lengkap' && !catatan) return toast('Catatan kekurangan dokumen wajib diisi.', 'error');

    const payload = { ids, aksi };
    if (catatan) payload.catatan_dokumen = catatan;

    try {
        const json = await apiFetch('/api/hr/dokumen/bulk-verifikasi', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (!json.status) {
            toast(json.message || 'Bulk verifikasi gagal.', 'error');
            return;
        }

        const ok = Number(json.data?.total_success || 0);
        const fail = Number(json.data?.total_failed || 0);
        if (fail > 0) toast(`Sebagian berhasil: ${ok} sukses, ${fail} gagal.`, 'warning');
        else toast(json.message || `${ok} pengajuan berhasil diverifikasi.`, 'success');

        closeModal(el.modalBulk);
        loadPengajuanIzin(state.page);
    } catch (err) {
        console.error('[HR Dokumen] submitBulkVerifikasi', err);
        toast('Terjadi kesalahan saat bulk verifikasi.', 'error');
    }
}

function konfirmasiVerifikasi(idIzin, aksi, namaKaryawan) {
    if (!idIzin || !aksi) return;

    state.selectedIzinId = idIzin;
    state.selectedAksi = aksi;

    if (aksi === 'tandai_lengkap') {
        el.modalVerifikasiTitle.textContent = 'Konfirmasi Tandai Lengkap';
        el.modalVerifikasiBody.innerHTML = `
            <p style="color:#64748b;font-size:13px;">
                Tandai dokumen pengajuan izin <strong style="color:#0f172a;">${esc(namaKaryawan)}</strong> sebagai <strong style="color:#10b981;">Lengkap</strong>?
            </p>
        `;
        el.inputCatatanDokumen.style.display = 'none';
        el.inputCatatanDokumen.value = '';
        el.btnSubmitVerifikasi.className = 'hr-btn-primary';
        el.btnSubmitVerifikasi.textContent = 'Ya, Tandai Lengkap';
    } else {
        el.modalVerifikasiTitle.textContent = 'Konfirmasi Tandai Tidak Lengkap';
        el.modalVerifikasiBody.innerHTML = `
            <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
                Tandai dokumen pengajuan izin <strong style="color:#0f172a;">${esc(namaKaryawan)}</strong> sebagai <strong style="color:#ef4444;">Tidak Lengkap</strong>?
            </p>
            <p style="color:#ef4444;font-size:12px;margin:0;">Karyawan terkait akan menerima notifikasi terkait kekurangan dokumen.</p>
        `;
        el.inputCatatanDokumen.style.display = 'block';
        el.inputCatatanDokumen.value = '';
        el.btnSubmitVerifikasi.className = 'hr-btn-danger';
        el.btnSubmitVerifikasi.textContent = 'Ya, Tandai Tidak Lengkap';
    }

    openModal(el.modalVerifikasi);
}

async function submitVerifikasi() {
    const id = state.selectedIzinId;
    const aksi = state.selectedAksi;
    const catatan = (el.inputCatatanDokumen?.value || '').trim();

    if (!id || !aksi) return toast('Aksi verifikasi tidak valid.', 'error');
    if (aksi === 'tandai_tidak_lengkap' && !catatan) return toast('Catatan kekurangan dokumen wajib diisi.', 'error');

    const payload = { aksi };
    if (catatan) payload.catatan_dokumen = catatan;

    try {
        const json = await apiFetch(`/api/hr/dokumen/${id}/verifikasi`, {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (!json.status) return toast(json.message || 'Verifikasi gagal.', 'error');

        toast(json.message || 'Verifikasi berhasil.', 'success');
        closeModal(el.modalVerifikasi);
        closeModal(el.modalDetail);
        loadPengajuanIzin(state.page);
    } catch (err) {
        console.error('[HR Dokumen] submitVerifikasi', err);
        toast('Terjadi kesalahan saat verifikasi dokumen.', 'error');
    }
}

async function lihatDetailIzin(idIzin) {
    try {
        const json = await apiFetch(`/api/hr/dokumen/${idIzin}`);
        if (!json.status || !json.data) return toast(json.message || 'Data izin tidak ditemukan.', 'error');
        renderModalDetail(json.data);
        openModal(el.modalDetail);
    } catch (err) {
        console.error('[HR Dokumen] lihatDetailIzin', err);
        toast('Gagal memuat detail pengajuan izin.', 'error');
    }
}

function renderModalDetail(data) {
    const karyawan = data.karyawan || {};
    const jenisIzin = data.jenis_izin || {};
    const dokumen = data.dokumen || [];

    let html = `
        <div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:16px;">
            <table style="width:100%;font-size:13px;">
                <tr><td style="padding:4px 0;color:#64748b;width:180px;">Nama Karyawan</td><td style="padding:4px 0;font-weight:500;">: ${esc(karyawan.nama_lengkap || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">NIK/No Karyawan</td><td style="padding:4px 0;">: ${esc(karyawan.nomor_karyawan || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Departemen</td><td style="padding:4px 0;">: ${esc(karyawan.departemen || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Perusahaan</td><td style="padding:4px 0;">: ${esc(karyawan.perusahaan || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Jenis Izin</td><td style="padding:4px 0;">: ${esc(jenisIzin.nama_jenis || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Tanggal Izin</td><td style="padding:4px 0;">: ${formatTanggalRange(data.tanggal_izin, data.tanggal_selesai_izin)} (${data.jumlah_hari || 1} hari)</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Status Validasi Admin</td><td style="padding:4px 0;">: ${badgeStatusValidasiAdmin(data.status_validasi_admin || data.status || '')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Keterangan</td><td style="padding:4px 0;">: ${esc(data.keterangan || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Status Dokumen</td><td style="padding:4px 0;">: ${badgeStatusDokumen(data.status_dokumen)}</td></tr>
            </table>
        </div>
        <h4 style="font-family:'Syne',sans-serif;font-size:14px;margin:0 0 12px;color:#0f172a;">Dokumen Pendukung</h4>
    `;

    if (!dokumen.length) {
        html += '<p style="color:#94a3b8;font-size:13px;padding:16px;text-align:center;background:#f8fafc;border-radius:8px;">Belum ada dokumen yang diunggah</p>';
    } else {
        html += '<div style="display:grid;gap:8px;">';
        dokumen.forEach((doc) => {
            html += `
                <div class="hr-dokumen-card">
                    <div class="hr-dokumen-info">
                        <div style="font-weight:500;font-size:13px;color:#0f172a;">${esc(doc.nama_file || 'Dokumen')}</div>
                        <div style="font-size:11px;color:#94a3b8;">${String(doc.tipe_file || '').toUpperCase()} · ${doc.ukuran_kb || 0} KB · ${formatTanggalWaktu(doc.diunggah_pada)}</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="hr-btn-sm hr-btn-outline btn-preview-dokumen"
                            data-id-izin="${data.id_izin}"
                            data-id-dok="${doc.id_dokumen}"
                            data-nama="${esc(doc.nama_file || 'Dokumen')}"
                            data-tipe="${esc(doc.tipe_file || '')}">
                            Preview
                        </button>
                        <button class="hr-btn-sm hr-btn-outline" onclick="window.open('/api/hr/dokumen/${data.id_izin}/stream/${doc.id_dokumen}', '_blank')">Buka di Tab Baru</button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    }

    html += `
        <div style="margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9;">
            <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Status Dokumen saat ini: ${badgeStatusDokumen(data.status_dokumen)}</p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button class="hr-btn-outline btn-batal-detail-modal">Batal</button>
                <button class="hr-btn-danger btn-tandai-tidak-lengkap-modal" data-id="${data.id_izin}" data-nama="${esc(karyawan.nama_lengkap || '')}">Tandai Tidak Lengkap</button>
                <button class="hr-btn-primary btn-tandai-lengkap-modal" data-id="${data.id_izin}" data-nama="${esc(karyawan.nama_lengkap || '')}">Tandai Lengkap</button>
            </div>
        </div>
    `;

    el.modalDetailBody.innerHTML = html;
}

function previewDokumen(idIzin, idDok, namaFile, tipe) {
    if (!idIzin || !idDok) return;

    const url = `/api/hr/dokumen/${idIzin}/stream/${idDok}`;
    const ext = String(tipe || '').toLowerCase();
    el.lightboxNamaFile.textContent = namaFile;
    el.btnLightboxTabBaru.onclick = () => window.open(url, '_blank');

    if (ext === 'pdf') {
        el.lightboxContent.innerHTML = `<iframe src="${url}#toolbar=1" style="width:100%;height:100%;border:none;"></iframe>`;
    } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
        el.lightboxContent.innerHTML = `<img src="${url}" alt="${esc(namaFile)}" style="max-width:100%;max-height:100%;object-fit:contain;">`;
    } else {
        el.lightboxContent.innerHTML = `
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#94a3b8;">
                <p>Format ${esc(ext.toUpperCase() || 'FILE')} belum didukung untuk preview.</p>
                <button class="hr-btn-primary" onclick="window.open('${url}', '_blank')" style="margin-top:12px;">Download File</button>
            </div>
        `;
    }

    el.lightbox.style.display = 'flex';
}

function closeLightbox() {
    el.lightbox.style.display = 'none';
    el.lightboxContent.innerHTML = '';
}

function openModal(modal) {
    if (modal) modal.style.display = 'flex';
}

function closeModal(modal) {
    if (modal) modal.style.display = 'none';
}

async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {})
        }
    });

    let json;
    try {
        json = await response.json();
    } catch {
        throw new Error(`Respons server tidak valid (${response.status}).`);
    }

    return json;
}

function formatTanggal(tgl) {
    if (!tgl) return '-';
    const d = new Date(tgl);
    if (Number.isNaN(d.getTime())) return '-';
    return `${d.getDate()} ${BULAN_LABEL[d.getMonth()]} ${d.getFullYear()}`;
}

function formatTanggalRange(mulai, selesai) {
    const start = formatTanggal(mulai);
    const end = formatTanggal(selesai || mulai);
    return start === end ? start : `${start} - ${end}`;
}

function formatTanggalWaktu(tgl) {
    if (!tgl) return '-';
    const d = new Date(tgl);
    if (Number.isNaN(d.getTime())) return '-';
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} ${BULAN_LABEL[d.getMonth()]} ${d.getFullYear()} ${hh}:${mm}`;
}

function badgeStatusDokumen(status) {
    const map = {
        lengkap: '<span class="hr-badge-lengkap">Lengkap</span>',
        tidak_lengkap: '<span class="hr-badge-tidak-lengkap">Tidak Lengkap</span>',
        sudah_upload: '<span class="hr-badge-sudah-upload">Belum Diverifikasi</span>',
        belum_upload: '<span class="hr-badge-belum-upload">Belum Upload</span>'
    };
    return map[status] || `<span style="font-size:11px;color:#94a3b8;">${esc(status || '-')}</span>`;
}

function badgeStatusValidasiAdmin(status) {
    if (status === 'disetujui') {
        return '<span class="hr-badge-lengkap">Disetujui</span>';
    }
    if (status === 'ditolak') {
        return '<span class="hr-badge-tidak-lengkap">Ditolak</span>';
    }
    return '<span class="hr-badge-belum-upload">Belum Divalidasi Admin</span>';
}

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

function toast(message, type = 'success') {
    const className = type === 'success' ? 'sukses' : type;
    const node = document.createElement('div');
    node.className = `hr-toast ${className}`;
    node.textContent = message;
    document.body.appendChild(node);

    setTimeout(() => {
        node.style.opacity = '0';
        setTimeout(() => node.remove(), 250);
    }, 3200);
}

function showSkeleton() {
    el.tbody.innerHTML = `
        <tr class="table-skeleton">
            <td colspan="11">
                <div class="skeleton-wrap">
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line skeleton-line--medium"></div>
                    <div class="skeleton-line"></div>
                </div>
            </td>
        </tr>
    `;
}

window.loadPengajuanIzin = loadPengajuanIzin;
