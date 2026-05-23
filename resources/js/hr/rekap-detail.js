/**
 * resources/js/hr/rekap-detail.js
 * Halaman B — Detail Rekap Per Bulan (Daftar Rekap Per Karyawan)
 * E-Outsourcing PT Ecogreen Oleochemicals Batam Plant
 */

const state = {
    bulan: null,
    tahun: null,
    page: 1,
    filters: {
        departemen: '',
        perusahaan: '',
        statusRekap: '',
        search: '',
    },
    selectedIds: new Set(),
    currentRows: [],
    currentRekapId: null,
    currentRekapData: null,
    peringatanMode: null,
    konfirmasiFinalMode: null,
};

const BULAN_LABEL = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

document.addEventListener('DOMContentLoaded', () => {
    // Baca bulan & tahun dari URL
    const params = new URLSearchParams(window.location.search);
    state.bulan = Number(params.get('bulan')) || new Date().getMonth() + 1;
    state.tahun = Number(params.get('tahun')) || new Date().getFullYear();

    setupUI();
    loadFilterOptions();
    bindEvents();
    loadRekapDetail();
});

function setupUI() {
    // Update info periode
    const infoPeriode = document.getElementById('info-periode-rekap-detail');
    if (infoPeriode) {
        infoPeriode.innerHTML = `
            <h1 class="page-title">Detail Rekap — ${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</h1>
            <p class="page-subtitle">Kelola rekap absensi per karyawan</p>
        `;
    }

    // Setup tabs
    const tabsContainer = document.getElementById('tabs-status-rekap');
    if (tabsContainer) {
        tabsContainer.innerHTML = `
            <button class="hr-tab hr-tab--active" data-status="">Semua</button>
            <button class="hr-tab" data-status="belum_generate">Belum Generate</button>
            <button class="hr-tab" data-status="draft">Draft</button>
            <button class="hr-tab" data-status="final">Final</button>
        `;
    }
}

async function loadFilterOptions() {
    try {
        const res = await apiFetch('/api/hr/dashboard/filter-options');
        if (!res.status || !res.data) return;

        const departemen = res.data.departemen || [];
        const perusahaan = res.data.perusahaan || [];

        const selectDept = document.getElementById('filter-departemen-detail');
        const selectPeru = document.getElementById('filter-perusahaan-detail');

        if (selectDept) {
            selectDept.innerHTML = '<option value="">Semua Departemen</option>';
            departemen.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id_departemen;
                opt.textContent = d.nama_departemen;
                selectDept.appendChild(opt);
            });
        }

        if (selectPeru) {
            selectPeru.innerHTML = '<option value="">Semua Perusahaan</option>';
            perusahaan.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id_perusahaan;
                opt.textContent = p.nama_perusahaan;
                selectPeru.appendChild(opt);
            });
        }
    } catch (err) {
        console.error('[Rekap Detail] loadFilterOptions error:', err);
    }
}

function bindEvents() {
    // Filter panel
    document.getElementById('btn-terapkan-filter-detail')?.addEventListener('click', () => {
        state.filters.departemen = document.getElementById('filter-departemen-detail')?.value || '';
        state.filters.perusahaan = document.getElementById('filter-perusahaan-detail')?.value || '';
        state.filters.statusRekap = document.getElementById('filter-status-rekap-detail')?.value || '';
        state.filters.search = document.getElementById('filter-search-rekap')?.value.trim() || '';
        state.page = 1;
        loadRekapDetail();
    });

    document.getElementById('btn-reset-filter-detail')?.addEventListener('click', () => {
        state.filters = { departemen: '', perusahaan: '', statusRekap: '', search: '' };
        document.getElementById('filter-departemen-detail').value = '';
        document.getElementById('filter-perusahaan-detail').value = '';
        document.getElementById('filter-status-rekap-detail').value = '';
        document.getElementById('filter-search-rekap').value = '';
        syncStatusTabs('');
        state.page = 1;
        loadRekapDetail();
    });

    // Tabs cepat
    document.getElementById('tabs-status-rekap')?.addEventListener('click', (e) => {
        const tab = e.target.closest('.hr-tab');
        if (!tab) return;

        const status = tab.dataset.status || '';
        state.filters.statusRekap = status;
        document.getElementById('filter-status-rekap-detail').value = status;
        syncStatusTabs(status);
        state.page = 1;
        loadRekapDetail();
    });

    // Panel aksi atas
    document.getElementById('btn-unduh-excel-detail')?.addEventListener('click', unduhExcel);
    document.getElementById('btn-generate-ulang-detail')?.addEventListener('click', generateUlang);
    document.getElementById('btn-final-semua-detail')?.addEventListener('click', tetapkanSemuaFinal);
    document.getElementById('btn-tutup-generate-ulang-detail')?.addEventListener('click', () => closeModal('modal-konfirmasi-generate-ulang-detail'));
    document.getElementById('btn-batal-generate-ulang-detail')?.addEventListener('click', () => closeModal('modal-konfirmasi-generate-ulang-detail'));
    document.getElementById('btn-submit-generate-ulang-detail')?.addEventListener('click', submitGenerateUlang);

    // Bulk action bar
    document.getElementById('btn-bulk-final')?.addEventListener('click', bulkFinal);
    document.getElementById('btn-batal-bulk')?.addEventListener('click', () => {
        clearSelection();
        updateBulkActionBar();
    });

    // Modal handlers
    document.getElementById('btn-tutup-modal-detail-rekap')?.addEventListener('click', () => closeModal('modal-detail-rekap'));
    document.getElementById('btn-batal-peringatan-detail')?.addEventListener('click', () => closeModal('modal-peringatan-rekap-detail'));
    document.getElementById('btn-verifikasi-dulu-detail')?.addEventListener('click', () => {
        window.location.href = `/hr/dokumen?bulan=${state.bulan}&tahun=${state.tahun}`;
    });
    document.getElementById('btn-lanjutkan-final-detail')?.addEventListener('click', () => {
        closeModal('modal-peringatan-rekap-detail');
        if (state.peringatanMode === 'bulk-all') {
            openModalKonfirmasiBulkAllFinal();
        } else if (state.selectedIds.size > 0) {
            openModalKonfirmasiBulkFinal();
        } else if (state.currentRekapId) {
            openModalKonfirmasiSingleFinal();
        }
    });
    document.getElementById('btn-tutup-final-detail')?.addEventListener('click', () => closeModal('modal-konfirmasi-final-detail'));
    document.getElementById('btn-batal-final-detail')?.addEventListener('click', () => closeModal('modal-konfirmasi-final-detail'));
    document.getElementById('btn-submit-final-detail')?.addEventListener('click', submitKonfirmasiFinal);

    // Event delegation untuk tabel
    document.getElementById('tabel-rekap-detail')?.addEventListener('click', handleTableClick);
    document.getElementById('tabel-rekap-detail')?.addEventListener('change', handleTableChange);

    // Paginasi
    document.getElementById('paginasi-rekap-detail')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        const page = Number(btn.dataset.page);
        if (page > 0) {
            state.page = page;
            loadRekapDetail();
        }
    });
}

async function loadRekapDetail() {
    showSkeleton();
    clearSelection();
    state.currentRows = [];

    const params = new URLSearchParams();
    params.set('bulan', state.bulan);
    params.set('tahun', state.tahun);
    params.set('page', state.page);

    if (state.filters.departemen) params.set('id_departemen', state.filters.departemen);
    if (state.filters.perusahaan) params.set('id_perusahaan', state.filters.perusahaan);
    if (state.filters.statusRekap) params.set('status_rekap', state.filters.statusRekap);
    if (state.filters.search) params.set('search', state.filters.search);

    try {
        const res = await apiFetch(`/api/hr/rekap?${params.toString()}`);
        if (!res.status) {
            toast(res.message || 'Gagal memuat data rekap.', 'error');
            renderTabel([], null);
            return;
        }

        const paginated = res.data || {};
        const data = paginated.data || [];

        renderTabel(data, paginated);
        renderPaginasi(paginated);
        renderStatCards(data);
    } catch (err) {
        console.error('[Rekap Detail] loadRekapDetail error:', err);
        toast('Terjadi kesalahan saat memuat data rekap.', 'error');
        renderTabel([], null);
    }
}

function renderTabel(rows, meta) {
    const container = document.getElementById('tabel-rekap-detail');
    if (!container) return;
    state.currentRows = Array.isArray(rows) ? rows : [];

    if (!rows.length) {
        container.innerHTML = `
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select-all-header" class="hr-checkbox" disabled></th>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>No. Karyawan</th>
                            <th>Departemen</th>
                            <th>Perusahaan</th>
                            <th>Hari Kerja</th>
                            <th>Hari Hadir</th>
                            <th>Hari Izin</th>
                            <th>Hari Alpa</th>
                            <th>Menit Normal</th>
                            <th>Menit Lembur</th>
                            <th>Menit Telat</th>
                            <th>Status</th>
                            <th>Digenerate Pada</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="16" style="text-align:center;padding:40px;color:#94a3b8;">
                                Tidak ada data rekap pada filter ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        return;
    }

    const startNo = ((state.page - 1) * 20) + 1;

    const tbody = rows.map((row, idx) => {
        const no = startNo + idx;
        const id = row.id_rekap || 0;
        const statusRekap = row.status_rekap || 'belum_generate';
        const isDraft = statusRekap === 'draft';
        const isFinal = statusRekap === 'final';
        const checked = state.selectedIds.has(id) ? 'checked' : '';
        const selectedClass = state.selectedIds.has(id) ? 'hr-row-selected' : '';
        const rowClass = isFinal ? 'hr-baris-final' : (isDraft ? 'hr-baris-draft' : '');

        const karyawan = row.karyawan || {};
        const alpaWarning = (row.total_hari_alpa || 0) > 3 ? ' ⚠️' : '';

        let badgeStatus = '';
        let aksiButtons = '';

        if (statusRekap === 'belum_generate') {
            badgeStatus = '<span class="hr-badge-belum-generate-tabel">⏱ Belum Generate</span>';
            aksiButtons = `<button class="hr-btn-sm hr-btn-primary btn-generate-single" data-id="${row.id_karyawan}">Generate</button>`;
        } else if (statusRekap === 'draft') {
            badgeStatus = '<span class="hr-badge-draft-tabel">✏ Draft</span>';
            aksiButtons = `
                <button class="hr-btn-sm hr-btn-outline btn-lihat-detail-rekap" data-id="${id}">Lihat Detail</button>
                <button class="hr-btn-sm hr-btn-primary btn-tetapkan-final-single" data-id="${id}" data-nama="${esc(karyawan.nama_lengkap || '')}">Tetapkan Final</button>
            `;
        } else if (statusRekap === 'final') {
            badgeStatus = '<span class="hr-badge-final-tabel">✓ Final</span>';
            aksiButtons = `<button class="hr-btn-sm hr-btn-outline btn-lihat-detail-rekap" data-id="${id}">Lihat Detail</button>`;
        }

        const digeneratePada = row.created_at ? formatTanggalWaktu(row.created_at) : '—';

        return `
            <tr class="${selectedClass} ${rowClass}" data-row-id="${id}" data-status="${statusRekap}">
                <td><input type="checkbox" class="hr-checkbox row-checkbox" data-id="${id}" ${isDraft ? '' : 'disabled'} ${checked}></td>
                <td>${no}</td>
                <td>
                    <div style="font-weight:500;color:#0f172a;">${esc(karyawan.nama_lengkap || '-')}</div>
                    <div style="font-size:11px;color:#94a3b8;">${esc(karyawan.nomor_karyawan || '')}</div>
                </td>
                <td style="font-size:12px;">${esc(karyawan.nomor_karyawan || '-')}</td>
                <td style="font-size:12px;">${esc(karyawan.departemen || '-')}</td>
                <td style="font-size:12px;">${esc(karyawan.perusahaan || '-')}</td>
                <td style="text-align:center;">${row.total_hari_kerja || 0}</td>
                <td style="text-align:center;">${row.total_hari_hadir || 0}</td>
                <td style="text-align:center;">${row.total_hari_izin || 0}</td>
                <td style="text-align:center;">${row.total_hari_alpa || 0}${alpaWarning}</td>
                <td style="text-align:center;">${row.total_menit_normal || 0}</td>
                <td style="text-align:center;">${row.total_menit_lembur || 0}</td>
                <td style="text-align:center;">${row.total_menit_telat || 0}</td>
                <td>${badgeStatus}</td>
                <td style="font-size:11px;">${digeneratePada}</td>
                <td style="white-space:nowrap;">${aksiButtons}</td>
            </tr>
        `;
    }).join('');

    container.innerHTML = `
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="select-all-header" class="hr-checkbox"></th>
                        <th>No</th>
                        <th>Nama Karyawan</th>
                        <th>No. Karyawan</th>
                        <th>Departemen</th>
                        <th>Perusahaan</th>
                        <th>Hari Kerja</th>
                        <th>Hari Hadir</th>
                        <th>Hari Izin</th>
                        <th>Hari Alpa</th>
                        <th>Menit Normal</th>
                        <th>Menit Lembur</th>
                        <th>Menit Telat</th>
                        <th>Status</th>
                        <th>Digenerate Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    ${tbody}
                </tbody>
            </table>
        </div>
    `;

    // Bind select all
    document.getElementById('select-all-header')?.addEventListener('change', (e) => {
        const checked = e.target.checked;
        const draftRows = Array.from(document.querySelectorAll('tr[data-status="draft"]'));
        draftRows.forEach(row => {
            const id = Number(row.dataset.rowId);
            const checkbox = row.querySelector('.row-checkbox');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = checked;
                if (checked) state.selectedIds.add(id);
                else state.selectedIds.delete(id);
            }
        });
        renderSelectedRows();
        updateBulkActionBar();
    });
}

function renderPaginasi(meta) {
    const container = document.getElementById('paginasi-rekap-detail');
    if (!container) return;

    const current = Number(meta?.current_page || 1);
    const last = Number(meta?.last_page || 1);

    if (last <= 1) {
        container.innerHTML = '';
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

    container.innerHTML = `
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

function renderStatCards(data) {
    const agregat = hitungAgregat(data);

    const cardKaryawan = document.getElementById('card-total-karyawan-detail');
    const cardLembur = document.getElementById('card-total-lembur-detail');
    const cardHadir = document.getElementById('card-total-hadir-detail');
    const cardAlpa = document.getElementById('card-total-alpa-detail');

    if (cardKaryawan) {
        cardKaryawan.innerHTML = `
            <div class="hr-stat-card hr-stat-card--green">
                <div class="hr-stat-card-header">
                    <div class="hr-stat-card-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        </svg>
                    </div>
                    <span class="hr-stat-card-label">Total Karyawan</span>
                </div>
                <div class="hr-stat-card-value">
                    <span class="nilai">${agregat.total_karyawan}</span>
                </div>
                <div class="hr-stat-card-footer">
                    <span class="label">Dalam tampilan saat ini</span>
                </div>
            </div>
        `;
    }

    if (cardLembur) {
        cardLembur.innerHTML = `
            <div class="hr-stat-card hr-stat-card--blue">
                <div class="hr-stat-card-header">
                    <div class="hr-stat-card-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <span class="hr-stat-card-label">Total Menit Lembur</span>
                </div>
                <div class="hr-stat-card-value">
                    <span class="nilai">${agregat.total_menit_lembur.toLocaleString()}</span>
                </div>
                <div class="hr-stat-card-footer">
                    <span class="label">Menit lembur resmi</span>
                </div>
            </div>
        `;
    }

    if (cardHadir) {
        cardHadir.innerHTML = `
            <div class="hr-stat-card hr-stat-card--amber">
                <div class="hr-stat-card-header">
                    <div class="hr-stat-card-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <span class="hr-stat-card-label">Total Hari Hadir</span>
                </div>
                <div class="hr-stat-card-value">
                    <span class="nilai">${agregat.total_hari_hadir}</span>
                </div>
                <div class="hr-stat-card-footer">
                    <span class="label">Hari hadir kumulatif</span>
                </div>
            </div>
        `;
    }

    if (cardAlpa) {
        cardAlpa.innerHTML = `
            <div class="hr-stat-card hr-stat-card--violet">
                <div class="hr-stat-card-header">
                    <div class="hr-stat-card-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <span class="hr-stat-card-label">Total Hari Alpa</span>
                </div>
                <div class="hr-stat-card-value">
                    <span class="nilai">${agregat.total_hari_alpa}</span>
                </div>
                <div class="hr-stat-card-footer">
                    <span class="label">Hari alpa kumulatif</span>
                </div>
            </div>
        `;
    }
}

function handleTableClick(e) {
    const btnGenerate = e.target.closest('.btn-generate-single');
    if (btnGenerate) {
        const idKaryawan = Number(btnGenerate.dataset.id);
        generateSingle(idKaryawan);
        return;
    }

    const btnDetail = e.target.closest('.btn-lihat-detail-rekap');
    if (btnDetail) {
        const idRekap = Number(btnDetail.dataset.id);
        lihatDetailRekap(idRekap);
        return;
    }

    const btnFinal = e.target.closest('.btn-tetapkan-final-single');
    if (btnFinal) {
        const idRekap = Number(btnFinal.dataset.id);
        const nama = btnFinal.dataset.nama || '';
        tetapkanFinalSingle(idRekap, nama);
        return;
    }
}

function handleTableChange(e) {
    const checkbox = e.target.closest('.row-checkbox');
    if (!checkbox) return;

    const id = Number(checkbox.dataset.id);
    if (!id) return;

    if (checkbox.checked) state.selectedIds.add(id);
    else state.selectedIds.delete(id);

    renderSelectedRows();
    updateBulkActionBar();
}

async function generateSingle(idKaryawan) {
    if (!idKaryawan) return;

    try {
        const res = await apiFetch('/api/hr/rekap/generate', {
            method: 'POST',
            body: JSON.stringify({ bulan: state.bulan, tahun: state.tahun }),
        });

        if (!res.status) {
            toast(res.message || 'Generate rekap gagal.', 'error');
            return;
        }

        toast('Rekap berhasil digenerate.', 'success');
        loadRekapDetail();
    } catch (err) {
        console.error('[Rekap Detail] generateSingle error:', err);
        toast('Terjadi kesalahan saat generate rekap.', 'error');
    }
}

async function lihatDetailRekap(idRekap) {
    if (!idRekap) return;

    try {
        // Gunakan data lengkap hasil API yang sudah tersimpan di state.
        let rekapData = state.currentRows.find(r => Number(r.id_rekap) === Number(idRekap)) || null;

        if (!rekapData) {
            // Fallback: fetch dari API
            const params = new URLSearchParams();
            params.set('bulan', state.bulan);
            params.set('tahun', state.tahun);
            params.set('page', state.page);
            if (state.filters.departemen) params.set('id_departemen', state.filters.departemen);
            if (state.filters.perusahaan) params.set('id_perusahaan', state.filters.perusahaan);
            if (state.filters.statusRekap) params.set('status_rekap', state.filters.statusRekap);
            if (state.filters.search) params.set('search', state.filters.search);

            const res = await apiFetch(`/api/hr/rekap?${params.toString()}`);
            if (res.status && res.data) {
                const allData = res.data.data || [];
                rekapData = allData.find(r => Number(r.id_rekap) === Number(idRekap)) || null;
            }
        }

        if (!rekapData) {
            toast('Data rekap tidak ditemukan.', 'error');
            return;
        }

        renderModalDetailRekap(rekapData);
        openModal('modal-detail-rekap');
    } catch (err) {
        console.error('[Rekap Detail] lihatDetailRekap error:', err);
        toast('Gagal memuat detail rekap.', 'error');
    }
}

function renderModalDetailRekap(data) {
    const body = document.getElementById('modal-detail-rekap-body');
    if (!body) return;

    const karyawan = data.karyawan || {};
    const statusRekap = data.status_rekap || 'belum_generate';
    const digeneratePada = data.created_at ? formatTanggalWaktu(data.created_at) : '—';
    const pembuatNama = data.dibuat_oleh || '—';

    let badgeStatus = '';
    if (statusRekap === 'belum_generate') badgeStatus = '<span class="hr-badge-belum-generate-tabel">⏱ Belum Generate</span>';
    else if (statusRekap === 'draft') badgeStatus = '<span class="hr-badge-draft-tabel">✏ Draft</span>';
    else if (statusRekap === 'final') badgeStatus = '<span class="hr-badge-final-tabel">✓ Final</span>';

    let aksiSection = '';
    if (statusRekap === 'draft') {
        aksiSection = `
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="hr-btn-outline" onclick="document.getElementById('modal-detail-rekap').style.display='none'">Tutup</button>
                    <button class="hr-btn-primary btn-tetapkan-final-modal" data-id="${data.id_rekap}" data-nama="${esc(karyawan.nama_lengkap || '')}">Tetapkan Final</button>
                </div>
            </div>
        `;
    } else if (statusRekap === 'final') {
        aksiSection = `
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <p style="color:#10b981;font-size:13px;margin:0 0 12px;">✓ Rekap ini sudah dikunci sebagai Final</p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="hr-btn-outline" onclick="document.getElementById('modal-detail-rekap').style.display='none'">Tutup</button>
                </div>
            </div>
        `;
    } else {
        aksiSection = `
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9;">
                <p style="color:#94a3b8;font-size:13px;margin:0 0 12px;">Rekap belum digenerate untuk karyawan ini.</p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="hr-btn-outline" onclick="document.getElementById('modal-detail-rekap').style.display='none'">Tutup</button>
                    <button class="hr-btn-primary btn-generate-rekap-modal" data-id="${karyawan.id_karyawan}">Generate Rekap Ini</button>
                </div>
            </div>
        `;
    }

    body.innerHTML = `
        <div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:16px;">
            <table style="width:100%;font-size:13px;">
                <tr><td style="padding:4px 0;color:#64748b;width:180px;">Nama Karyawan</td><td style="padding:4px 0;font-weight:500;">: ${esc(karyawan.nama_lengkap || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">No. Karyawan</td><td style="padding:4px 0;">: ${esc(karyawan.nomor_karyawan || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Departemen</td><td style="padding:4px 0;">: ${esc(karyawan.departemen || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Perusahaan</td><td style="padding:4px 0;">: ${esc(karyawan.perusahaan || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Posisi</td><td style="padding:4px 0;">: ${esc(karyawan.posisi || '-')}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Periode</td><td style="padding:4px 0;">: ${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Status Rekap</td><td style="padding:4px 0;">: ${badgeStatus}</td></tr>
                <tr><td style="padding:4px 0;color:#64748b;">Digenerate Pada</td><td style="padding:4px 0;">: ${digeneratePada} oleh ${pembuatNama}</td></tr>
            </table>
        </div>

        <h4 style="font-family:'Syne',sans-serif;font-size:14px;margin:0 0 12px;color:#0f172a;">Data Rekap</h4>
        <div class="hr-rekap-detail-grid">
            <div class="hr-rekap-detail-section">
                <h4>Kehadiran</h4>
                <div class="hr-rekap-detail-row">
                    <span class="label">Hari Kerja</span>
                    <span class="value">${data.total_hari_kerja || 0} hari</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Hari Hadir</span>
                    <span class="value">${data.total_hari_hadir || 0} hari</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Hari Izin</span>
                    <span class="value">${data.total_hari_izin || 0} hari</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Hari Alpa</span>
                    <span class="value">${data.total_hari_alpa || 0} hari</span>
                </div>
            </div>
            <div class="hr-rekap-detail-section">
                <h4>Waktu Kerja</h4>
                <div class="hr-rekap-detail-row">
                    <span class="label">Menit Normal</span>
                    <span class="value">${data.total_menit_normal || 0} mnt</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Menit Lembur</span>
                    <span class="value">${data.total_menit_lembur || 0} mnt</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Menit Telat</span>
                    <span class="value">${data.total_menit_telat || 0} mnt</span>
                </div>
                <div class="hr-rekap-detail-row">
                    <span class="label">Menit Plg Cpt</span>
                    <span class="value">${data.total_menit_pulang_cepat || 0} mnt</span>
                </div>
            </div>
        </div>

        ${aksiSection}
    `;

    // Bind event untuk tombol di modal
    body.querySelector('.btn-tetapkan-final-modal')?.addEventListener('click', (e) => {
        const id = Number(e.target.dataset.id);
        const nama = e.target.dataset.nama;
        tetapkanFinalSingle(id, nama);
    });

    body.querySelector('.btn-generate-rekap-modal')?.addEventListener('click', (e) => {
        const id = Number(e.target.dataset.id);
        generateSingle(id);
        closeModal('modal-detail-rekap');
    });
}

async function tetapkanFinalSingle(idRekap, namaKaryawan) {
    if (!idRekap) return;

    state.currentRekapId = idRekap;

    // Cek dokumen
    try {
        const res = await apiFetch(`/api/hr/rekap/cek-dokumen?bulan=${state.bulan}&tahun=${state.tahun}`);
        if (!res.status) {
            toast(res.message || 'Gagal cek dokumen.', 'error');
            return;
        }

        const data = res.data || {};
        if (data.ada_tidak_lengkap) {
            openModalPeringatanDokumen(data, 'single');
        } else {
            openModalKonfirmasiSingleFinal(namaKaryawan);
        }
    } catch (err) {
        console.error('[Rekap Detail] cek dokumen error:', err);
        toast('Terjadi kesalahan saat cek dokumen.', 'error');
    }
}

function openModalKonfirmasiSingleFinal(namaKaryawan = '') {
    const body = document.getElementById('modal-konfirmasi-final-detail-body');
    if (!body) return;
    state.konfirmasiFinalMode = 'single';

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Tetapkan rekap <strong style="color:#0f172a;">${esc(namaKaryawan)}</strong> periode <strong>${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</strong> sebagai Final?
        </p>
        <p style="color:#ef4444;font-size:12px;margin:0;">
            Aksi ini tidak dapat dibatalkan.
        </p>
    `;

    openModal('modal-konfirmasi-final-detail');
}

async function submitSingleFinal() {
    const idRekap = state.currentRekapId;
    if (!idRekap) return;

    try {
        const res = await apiFetch(`/api/hr/rekap/${idRekap}/final`, { method: 'POST' });
        if (!res.status) {
            toast(res.message || 'Tetapkan final gagal.', 'error');
            return;
        }

        toast(res.message || 'Rekap berhasil ditetapkan Final.', 'success');
        state.konfirmasiFinalMode = null;
        closeModal('modal-konfirmasi-final-detail');
        closeModal('modal-detail-rekap');
        loadRekapDetail();
    } catch (err) {
        console.error('[Rekap Detail] submitSingleFinal error:', err);
        toast('Terjadi kesalahan saat tetapkan final.', 'error');
    }
}

async function generateUlang() {
    openModalKonfirmasiGenerateUlang();
}

function openModalKonfirmasiGenerateUlang() {
    const body = document.getElementById('modal-konfirmasi-generate-ulang-detail-body');
    if (!body) return;

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Generate ulang rekap untuk semua karyawan aktif pada periode
            <strong style="color:#0f172a;">${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</strong>?
        </p>
        <p style="color:#64748b;font-size:12px;margin:0 0 8px;">
            Rekap berstatus <strong>Draft</strong> akan diperbarui mengikuti data terbaru.
        </p>
        <p style="color:#ef4444;font-size:12px;margin:0;">
            Rekap yang sudah <strong>Final</strong> tidak akan diubah.
        </p>
    `;

    openModal('modal-konfirmasi-generate-ulang-detail');
}

async function submitGenerateUlang() {
    try {
        const res = await apiFetch('/api/hr/rekap/generate', {
            method: 'POST',
            body: JSON.stringify({ bulan: state.bulan, tahun: state.tahun }),
        });

        if (!res.status) {
            toast(res.message || 'Generate ulang gagal.', 'error');
            return;
        }

        toast(res.message || 'Rekap berhasil digenerate ulang.', 'success');
        closeModal('modal-konfirmasi-generate-ulang-detail');
        loadRekapDetail();
    } catch (err) {
        console.error('[Rekap Detail] generateUlang error:', err);
        toast('Terjadi kesalahan saat generate ulang.', 'error');
    }
}

async function tetapkanSemuaFinal() {
    // Tetapkan semua draft sesuai filter aktif
    // Cek dokumen dulu
    try {
        const res = await apiFetch(`/api/hr/rekap/cek-dokumen?bulan=${state.bulan}&tahun=${state.tahun}`);
        if (!res.status) {
            toast(res.message || 'Gagal cek dokumen.', 'error');
            return;
        }

        const data = res.data || {};
        if (data.ada_tidak_lengkap) {
            openModalPeringatanDokumen(data, 'bulk-all');
        } else {
            openModalKonfirmasiBulkAllFinal();
        }
    } catch (err) {
        console.error('[Rekap Detail] cek dokumen error:', err);
        toast('Terjadi kesalahan saat cek dokumen.', 'error');
    }
}

function openModalKonfirmasiBulkAllFinal() {
    const body = document.getElementById('modal-konfirmasi-final-detail-body');
    if (!body) return;
    state.konfirmasiFinalMode = 'bulk-all';

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Tetapkan semua rekap <strong style="color:#0f172a;">Draft</strong> menjadi Final untuk periode
            <strong>${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</strong>?
        </p>
        <p style="color:#64748b;font-size:12px;margin:0 0 8px;">
            Rekap berstatus <strong>Final</strong> tidak akan diubah.
        </p>
        <p style="color:#ef4444;font-size:12px;margin:0;">
            Aksi ini tidak dapat dibatalkan.
        </p>
    `;

    openModal('modal-konfirmasi-final-detail');
}

async function submitBulkAllFinal() {
    try {
        // Ambil semua draft
        const params = new URLSearchParams();
        params.set('bulan', state.bulan);
        params.set('tahun', state.tahun);
        params.set('status_rekap', 'draft');
        if (state.filters.departemen) params.set('id_departemen', state.filters.departemen);
        if (state.filters.perusahaan) params.set('id_perusahaan', state.filters.perusahaan);

        const res = await apiFetch(`/api/hr/rekap?${params.toString()}`);
        if (!res.status || !res.data) {
            toast('Gagal mengambil data rekap draft.', 'error');
            return;
        }

        const draftList = res.data.data || [];
        if (draftList.length === 0) {
            toast('Tidak ada rekap draft untuk ditetapkan final.', 'warning');
            return;
        }

        // Show progress modal
        openModal('modal-progress-bulk');
        document.getElementById('modal-progress-label').textContent = 'Menetapkan Final...';
        document.getElementById('progress-bulk-fill').style.width = '0%';
        document.getElementById('modal-progress-count').textContent = '0 dari ' + draftList.length + ' diproses...';

        let berhasil = 0;
        let gagal = 0;

        for (let i = 0; i < draftList.length; i++) {
            try {
                const finalRes = await apiFetch(`/api/hr/rekap/${draftList[i].id_rekap}/final`, { method: 'POST' });
                if (finalRes.status) berhasil++;
                else gagal++;
            } catch {
                gagal++;
            }

            const pct = Math.round(((i + 1) / draftList.length) * 100);
            document.getElementById('progress-bulk-fill').style.width = pct + '%';
            document.getElementById('modal-progress-count').textContent = `${i + 1} dari ${draftList.length} diproses...`;
        }

        closeModal('modal-progress-bulk');

        if (gagal === 0) {
            toast(`${berhasil} rekap berhasil ditetapkan Final.`, 'success');
        } else {
            toast(`${berhasil} berhasil, ${gagal} gagal ditetapkan Final.`, 'warning');
        }

        loadRekapDetail();
    } catch (err) {
        console.error('[Rekap Detail] submitBulkAllFinal error:', err);
        toast('Terjadi kesalahan saat bulk final.', 'error');
        closeModal('modal-progress-bulk');
    }
}

async function bulkFinal() {
    if (state.selectedIds.size === 0) {
        toast('Pilih minimal 1 rekap terlebih dahulu.', 'warning');
        return;
    }

    // Cek dokumen
    try {
        const res = await apiFetch(`/api/hr/rekap/cek-dokumen?bulan=${state.bulan}&tahun=${state.tahun}`);
        if (!res.status) {
            toast(res.message || 'Gagal cek dokumen.', 'error');
            return;
        }

        const data = res.data || {};
        if (data.ada_tidak_lengkap) {
            openModalPeringatanDokumen(data, 'bulk');
        } else {
            openModalKonfirmasiBulkFinal();
        }
    } catch (err) {
        console.error('[Rekap Detail] cek dokumen error:', err);
        toast('Terjadi kesalahan saat cek dokumen.', 'error');
    }
}

function openModalPeringatanDokumen(data, mode) {
    const body = document.getElementById('modal-peringatan-rekap-detail-body');
    if (!body) return;

    const detail = data.detail || [];
    const listHtml = detail.slice(0, 5).map(d => `
        <li style="margin-bottom:6px;">
            <strong>${esc(d.nama_karyawan || '-')}</strong> — ${d.tanggal_izin || '-'}<br>
            <span style="font-size:11px;color:#94a3b8;">Status: ${esc(d.status_dokumen || '-')}</span>
        </li>
    `).join('');

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Terdapat <strong style="color:#ef4444;">${data.jumlah || 0} pengajuan izin</strong> dengan dokumen belum lengkap/terverifikasi.
        </p>
        <ul style="font-size:12px;color:#475569;margin:0 0 12px 20px;max-height:200px;overflow-y:auto;">
            ${listHtml}
            ${detail.length > 5 ? `<li style="color:#94a3b8;">... dan ${detail.length - 5} lainnya</li>` : ''}
        </ul>
        <p style="color:#64748b;font-size:12px;margin:0;">
            Rekap tetap dapat ditetapkan Final, namun data izin mungkin belum akurat.
        </p>
    `;

    openModal('modal-peringatan-rekap-detail');

    // Store mode untuk tahu action selanjutnya
    state.peringatanMode = mode;
}

function openModalKonfirmasiBulkFinal() {
    const body = document.getElementById('modal-konfirmasi-final-detail-body');
    const count = state.selectedIds.size;
    if (!body || count <= 0) return;
    state.konfirmasiFinalMode = 'bulk';

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Tetapkan <strong style="color:#0f172a;">${count} rekap terpilih</strong> sebagai Final untuk periode
            <strong>${BULAN_LABEL[state.bulan - 1]} ${state.tahun}</strong>?
        </p>
        <p style="color:#ef4444;font-size:12px;margin:0;">
            Aksi ini tidak dapat dibatalkan.
        </p>
    `;

    openModal('modal-konfirmasi-final-detail');
}

async function submitKonfirmasiFinal() {
    const mode = state.konfirmasiFinalMode;
    if (!mode) return;
    state.konfirmasiFinalMode = null;

    closeModal('modal-konfirmasi-final-detail');

    if (mode === 'single') {
        await submitSingleFinal();
    } else if (mode === 'bulk-all') {
        await submitBulkAllFinal();
    } else if (mode === 'bulk') {
        await submitBulkFinal();
    }
}

async function submitBulkFinal() {
    const listIdRekap = Array.from(state.selectedIds);
    if (listIdRekap.length === 0) return;

    // Show progress modal
    openModal('modal-progress-bulk');
    document.getElementById('modal-progress-label').textContent = 'Menetapkan Final...';
    document.getElementById('progress-bulk-fill').style.width = '0%';
    document.getElementById('modal-progress-count').textContent = '0 dari ' + listIdRekap.length + ' diproses...';

    let berhasil = 0;
    let gagal = 0;

    for (let i = 0; i < listIdRekap.length; i++) {
        try {
            const res = await apiFetch(`/api/hr/rekap/${listIdRekap[i]}/final`, { method: 'POST' });
            if (res.status) berhasil++;
            else gagal++;
        } catch {
            gagal++;
        }

        const pct = Math.round(((i + 1) / listIdRekap.length) * 100);
        document.getElementById('progress-bulk-fill').style.width = pct + '%';
        document.getElementById('modal-progress-count').textContent = `${i + 1} dari ${listIdRekap.length} diproses...`;
    }

    closeModal('modal-progress-bulk');

    if (gagal === 0) {
        toast(`${berhasil} rekap berhasil ditetapkan Final.`, 'success');
    } else {
        toast(`${berhasil} berhasil, ${gagal} gagal ditetapkan Final.`, 'warning');
    }

    clearSelection();
    updateBulkActionBar();
    loadRekapDetail();
}

function unduhExcel() {
    const params = new URLSearchParams();
    params.set('bulan', state.bulan);
    params.set('tahun', state.tahun);
    if (state.filters.departemen) params.set('id_departemen', state.filters.departemen);
    if (state.filters.perusahaan) params.set('id_perusahaan', state.filters.perusahaan);

    window.location.href = `/api/hr/rekap/unduh?${params.toString()}`;
    toast(`Mengunduh rekap ${BULAN_LABEL[state.bulan - 1]} ${state.tahun}...`, 'success');
}

// Helper functions
function hitungAgregat(dataRekap) {
    return {
        total_karyawan: dataRekap.length,
        total_menit_lembur: dataRekap.reduce((sum, r) => sum + (r.total_menit_lembur || 0), 0),
        total_hari_hadir: dataRekap.reduce((sum, r) => sum + (r.total_hari_hadir || 0), 0),
        total_hari_alpa: dataRekap.reduce((sum, r) => sum + (r.total_hari_alpa || 0), 0),
    };
}

function clearSelection() {
    state.selectedIds.clear();
}

function renderSelectedRows() {
    const rows = document.querySelectorAll('tr[data-row-id]');
    rows.forEach(row => {
        const id = Number(row.dataset.rowId);
        const checked = state.selectedIds.has(id);
        row.classList.toggle('hr-row-selected', checked);
        const checkbox = row.querySelector('.row-checkbox');
        if (checkbox) checkbox.checked = checked;
    });
}

function updateBulkActionBar() {
    const count = state.selectedIds.size;
    const bar = document.getElementById('bulk-action-bar');
    const label = document.getElementById('bulk-count-label');
    const countSpan = document.getElementById('bulk-count');

    if (bar) bar.style.display = count > 0 ? 'flex' : 'none';
    if (label) label.textContent = `${count} rekap dipilih`;
    if (countSpan) countSpan.textContent = count;
}

function syncStatusTabs(status) {
    document.querySelectorAll('.hr-tab').forEach(tab => {
        tab.classList.toggle('hr-tab--active', (tab.dataset.status || '') === status);
    });
}

function showSkeleton() {
    const container = document.getElementById('tabel-rekap-detail');
    if (!container) return;

    container.innerHTML = `
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;"></th>
                        <th>No</th>
                        <th>Nama Karyawan</th>
                        <th>No. Karyawan</th>
                        <th>Departemen</th>
                        <th>Perusahaan</th>
                        <th>Hari Kerja</th>
                        <th>Hari Hadir</th>
                        <th>Hari Izin</th>
                        <th>Hari Alpa</th>
                        <th>Menit Normal</th>
                        <th>Menit Lembur</th>
                        <th>Menit Telat</th>
                        <th>Status</th>
                        <th>Digenerate Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-skeleton">
                        <td colspan="16">
                            <div class="skeleton-wrap">
                                <div class="skeleton-line"></div>
                                <div class="skeleton-line skeleton-line--medium"></div>
                                <div class="skeleton-line"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

function toast(message, type = 'success') {
    const toast = document.getElementById('hr-toast-rekap-detail');
    if (!toast) return;

    toast.textContent = message;
    toast.className = `hr-toast ${type}`;
    toast.style.display = 'block';

    setTimeout(() => {
        toast.style.display = 'none';
    }, 3500);
}

async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {}),
        },
    });

    let json;
    try {
        json = await response.json();
    } catch {
        throw new Error(`Respons server tidak valid (${response.status}).`);
    }

    return json;
}

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function formatTanggalWaktu(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hour = String(d.getHours()).padStart(2, '0');
    const minute = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hour}:${minute}`;
}
