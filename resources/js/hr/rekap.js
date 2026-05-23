/**
 * resources/js/hr/rekap.js
 * Halaman A — Daftar Rekap Per Bulan (12 kartu bulan)
 * E-Outsourcing PT Ecogreen Oleochemicals Batam Plant
 */

const state = {
    tahun: new Date().getFullYear(),
    bulanData: {}, // { bulan: { status, jumlahFinal, jumlahDraft, belumGenerate, totalKaryawan } }
    currentBulan: null,
    currentTahun: null,
};

const BULAN_LABEL = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

document.addEventListener('DOMContentLoaded', () => {
    setupFilterTahun();
    bindEvents();
    loadSemuaBulan(state.tahun);
});

function setupFilterTahun() {
    const select = document.getElementById('filter-tahun-rekap');
    if (!select) return;

    const currentYear = new Date().getFullYear();
    select.innerHTML = '';

    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        if (y === currentYear) opt.selected = true;
        select.appendChild(opt);
    }
}

function bindEvents() {
    document.getElementById('btn-terapkan-filter-rekap')?.addEventListener('click', () => {
        const tahun = Number(document.getElementById('filter-tahun-rekap')?.value || new Date().getFullYear());
        state.tahun = tahun;
        loadSemuaBulan(tahun);
    });

    document.getElementById('btn-batal-generate')?.addEventListener('click', () => closeModal('modal-konfirmasi-generate'));
    document.getElementById('btn-tutup-modal-generate')?.addEventListener('click', () => closeModal('modal-konfirmasi-generate'));
    document.getElementById('btn-submit-generate')?.addEventListener('click', submitGenerate);

    document.getElementById('btn-batal-peringatan-rekap')?.addEventListener('click', () => closeModal('modal-peringatan-rekap'));
    document.getElementById('btn-tutup-modal-peringatan')?.addEventListener('click', () => closeModal('modal-peringatan-rekap'));
    document.getElementById('btn-verifikasi-dulu')?.addEventListener('click', () => {
        window.location.href = `/hr/dokumen?bulan=${state.currentBulan}&tahun=${state.currentTahun}`;
    });
    document.getElementById('btn-lanjutkan-final')?.addEventListener('click', () => {
        closeModal('modal-peringatan-rekap');
        openModalKonfirmasiFinal();
    });

    document.getElementById('btn-batal-final')?.addEventListener('click', () => closeModal('modal-konfirmasi-final'));
    document.getElementById('btn-tutup-modal-final')?.addEventListener('click', () => closeModal('modal-konfirmasi-final'));
    document.getElementById('btn-submit-final')?.addEventListener('click', submitTetapkanSemuaFinal);

    // Event delegation untuk tombol di kartu
    document.getElementById('grid-kartu-rekap')?.addEventListener('click', (e) => {
        const btnGenerate = e.target.closest('.btn-generate-rekap');
        if (btnGenerate) {
            const bulan = Number(btnGenerate.dataset.bulan);
            const tahun = Number(btnGenerate.dataset.tahun);
            openModalGenerate(bulan, tahun);
            return;
        }

        const btnFinal = e.target.closest('.btn-tetapkan-final');
        if (btnFinal) {
            const bulan = Number(btnFinal.dataset.bulan);
            const tahun = Number(btnFinal.dataset.tahun);
            tetapkanSemuaFinal(bulan, tahun);
            return;
        }

        const btnUnduh = e.target.closest('.btn-unduh-excel');
        if (btnUnduh) {
            const bulan = Number(btnUnduh.dataset.bulan);
            const tahun = Number(btnUnduh.dataset.tahun);
            unduhExcel(bulan, tahun);
            return;
        }

        const btnDetail = e.target.closest('.btn-lihat-detail');
        if (btnDetail) {
            const bulan = Number(btnDetail.dataset.bulan);
            const tahun = Number(btnDetail.dataset.tahun);
            window.location.href = `/hr/rekap/detail?bulan=${bulan}&tahun=${tahun}`;
            return;
        }
    });
}

async function loadSemuaBulan(tahun) {
    const grid = document.getElementById('grid-kartu-rekap');
    if (!grid) return;

    // Reset state
    state.bulanData = {};

    // Tampilkan skeleton
    grid.innerHTML = Array(12).fill(0).map(() => '<div class="hr-skeleton-card hr-skeleton"></div>').join('');

    // Load data untuk setiap bulan
    const promises = [];
    for (let bulan = 1; bulan <= 12; bulan++) {
        promises.push(loadDataBulan(bulan, tahun));
    }

    await Promise.all(promises);

    // Render semua kartu
    renderSemuaKartu(tahun);
}

async function loadDataBulan(bulan, tahun) {
    try {
        const res = await apiFetch(`/api/hr/rekap?bulan=${bulan}&tahun=${tahun}`);
        if (!res.status || !res.data) {
            state.bulanData[bulan] = { status: 'belum_generate', jumlahFinal: 0, jumlahDraft: 0, belumGenerate: 0, totalKaryawan: 0 };
            return;
        }

        const data = res.data.data || [];
        const jumlahFinal = data.filter(r => r.status_rekap === 'final').length;
        const jumlahDraft = data.filter(r => r.status_rekap === 'draft').length;
        const totalGenerate = jumlahFinal + jumlahDraft;

        // Ambil total karyawan aktif dari endpoint lain atau asumsi dari data
        const totalKaryawan = data.length > 0 ? res.data.total || data.length : 0;
        const belumGenerate = Math.max(0, totalKaryawan - totalGenerate);

        // Tentukan status bulan
        let statusBulan = 'belum_generate';
        if (belumGenerate === 0 && jumlahFinal === totalGenerate && totalGenerate > 0) {
            statusBulan = 'selesai';
        } else if (belumGenerate === 0 && jumlahDraft > 0) {
            statusBulan = 'ada_draft';
        }

        state.bulanData[bulan] = {
            status: statusBulan,
            jumlahFinal,
            jumlahDraft,
            belumGenerate,
            totalKaryawan,
        };
    } catch (err) {
        console.error(`[Rekap] Error loading bulan ${bulan}:`, err);
        state.bulanData[bulan] = { status: 'belum_generate', jumlahFinal: 0, jumlahDraft: 0, belumGenerate: 0, totalKaryawan: 0 };
    }
}

function renderSemuaKartu(tahun) {
    const grid = document.getElementById('grid-kartu-rekap');
    if (!grid) return;

    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();

    grid.innerHTML = '';

    for (let bulan = 1; bulan <= 12; bulan++) {
        const data = state.bulanData[bulan] || { status: 'belum_generate', jumlahFinal: 0, jumlahDraft: 0, belumGenerate: 0, totalKaryawan: 0 };
        const isBelumTerjadi = (tahun === currentYear && bulan > currentMonth) || (tahun > currentYear);

        const kartu = renderKartuBulan(bulan, tahun, data, isBelumTerjadi);
        grid.appendChild(kartu);
    }
}

function renderKartuBulan(bulan, tahun, data, isBelumTerjadi) {
    const div = document.createElement('div');
    div.className = `hr-kartu-bulan-rekap ${isBelumTerjadi ? 'belum-terjadi' : ''}`;
    div.dataset.bulan = bulan;

    const { status, jumlahFinal, jumlahDraft, belumGenerate, totalKaryawan } = data;

    // Badge status
    let badgeClass = 'hr-badge-belum-generate';
    let badgeLabel = 'Belum Generate';
    let badgeIcon = '⏱';

    if (status === 'ada_draft') {
        badgeClass = 'hr-badge-ada-draft';
        badgeLabel = 'Ada Draft';
        badgeIcon = '✏';
    } else if (status === 'selesai') {
        badgeClass = 'hr-badge-selesai';
        badgeLabel = 'Selesai';
        badgeIcon = '✓';
    }

    // Ringkasan angka
    const ringkasan = `${jumlahFinal} Final · ${jumlahDraft} Draft · ${belumGenerate} Belum Generate`;

    // Tombol aksi
    let tombolAksi = '';
    if (!isBelumTerjadi) {
        if (status === 'belum_generate') {
            tombolAksi = `
                <button class="hr-btn-sm hr-btn-primary btn-generate-rekap" data-bulan="${bulan}" data-tahun="${tahun}">Generate Rekap</button>
                <button class="hr-btn-sm hr-btn-outline btn-lihat-detail" data-bulan="${bulan}" data-tahun="${tahun}">Lihat Detail</button>
            `;
        } else if (status === 'ada_draft') {
            tombolAksi = `
                <button class="hr-btn-sm hr-btn-outline btn-unduh-excel" data-bulan="${bulan}" data-tahun="${tahun}">Unduh Excel</button>
                <button class="hr-btn-sm hr-btn-primary btn-tetapkan-final" data-bulan="${bulan}" data-tahun="${tahun}">Tetapkan Semua Final</button>
                <button class="hr-btn-sm hr-btn-outline btn-lihat-detail" data-bulan="${bulan}" data-tahun="${tahun}">Lihat Detail</button>
            `;
        } else if (status === 'selesai') {
            tombolAksi = `
                <button class="hr-btn-sm hr-btn-outline btn-unduh-excel" data-bulan="${bulan}" data-tahun="${tahun}">Unduh Excel</button>
                <button class="hr-btn-sm hr-btn-outline btn-lihat-detail" data-bulan="${bulan}" data-tahun="${tahun}">Lihat Detail</button>
            `;
        }
    }

    div.innerHTML = `
        <div class="hr-kartu-bulan-header">
            <h3 class="hr-kartu-bulan-title">${BULAN_LABEL[bulan - 1]} ${tahun}</h3>
        </div>
        <div class="hr-kartu-bulan-status">
            <span class="${badgeClass}">${badgeIcon} ${badgeLabel}</span>
        </div>
        <div class="hr-ringkasan-angka">${ringkasan}</div>
        <div class="hr-kartu-bulan-actions">
            ${tombolAksi}
        </div>
    `;

    return div;
}

function openModalGenerate(bulan, tahun) {
    state.currentBulan = bulan;
    state.currentTahun = tahun;

    const body = document.getElementById('modal-generate-body');
    if (!body) return;

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Generate rekap absensi untuk:
        </p>
        <table style="width:100%;font-size:13px;margin-bottom:12px;">
            <tr><td style="padding:4px 0;color:#64748b;width:120px;">Periode</td><td style="padding:4px 0;font-weight:500;">: ${BULAN_LABEL[bulan - 1]} ${tahun}</td></tr>
            <tr><td style="padding:4px 0;color:#64748b;">Karyawan</td><td style="padding:4px 0;font-weight:500;">: Semua karyawan aktif</td></tr>
        </table>
        <p style="color:#94a3b8;font-size:12px;margin:0;">
            Rekap yang sudah ada (Draft) akan diperbarui. Rekap berstatus Final tidak akan diubah.
        </p>
    `;

    openModal('modal-konfirmasi-generate');
}

async function submitGenerate() {
    const bulan = state.currentBulan;
    const tahun = state.currentTahun;

    if (!bulan || !tahun) return;

    const kartu = document.querySelector(`[data-bulan="${bulan}"]`);
    if (kartu) {
        kartu.innerHTML += '<div class="hr-kartu-loading-overlay"><div class="hr-spinner"></div></div>';
    }

    try {
        const res = await apiFetch('/api/hr/rekap/generate', {
            method: 'POST',
            body: JSON.stringify({ bulan, tahun }),
        });

        if (!res.status) {
            toast(res.message || 'Generate rekap gagal.', 'error');
            return;
        }

        toast(res.message || `Rekap ${BULAN_LABEL[bulan - 1]} ${tahun} berhasil digenerate.`, 'success');
        closeModal('modal-konfirmasi-generate');

        // Reload kartu bulan ini saja
        await loadDataBulan(bulan, tahun);
        const data = state.bulanData[bulan];
        const now = new Date();
        const isBelumTerjadi = (tahun === now.getFullYear() && bulan > now.getMonth() + 1) || (tahun > now.getFullYear());
        const newKartu = renderKartuBulan(bulan, tahun, data, isBelumTerjadi);
        if (kartu) kartu.replaceWith(newKartu);
    } catch (err) {
        console.error('[Rekap] submitGenerate error:', err);
        toast('Terjadi kesalahan saat generate rekap.', 'error');
    } finally {
        const overlay = kartu?.querySelector('.hr-kartu-loading-overlay');
        if (overlay) overlay.remove();
    }
}

async function tetapkanSemuaFinal(bulan, tahun) {
    state.currentBulan = bulan;
    state.currentTahun = tahun;

    // Cek dokumen
    try {
        const res = await apiFetch(`/api/hr/rekap/cek-dokumen?bulan=${bulan}&tahun=${tahun}`);
        if (!res.status) {
            toast(res.message || 'Gagal cek dokumen.', 'error');
            return;
        }

        const data = res.data || {};
        if (data.ada_tidak_lengkap) {
            openModalPeringatanDokumen(data);
        } else {
            openModalKonfirmasiFinal();
        }
    } catch (err) {
        console.error('[Rekap] cek dokumen error:', err);
        toast('Terjadi kesalahan saat cek dokumen.', 'error');
    }
}

function openModalPeringatanDokumen(data) {
    const body = document.getElementById('modal-peringatan-rekap-body');
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

    openModal('modal-peringatan-rekap');
}

function openModalKonfirmasiFinal() {
    const bulan = state.currentBulan;
    const tahun = state.currentTahun;
    const data = state.bulanData[bulan] || {};

    const body = document.getElementById('modal-final-body');
    if (!body) return;

    body.innerHTML = `
        <p style="color:#64748b;font-size:13px;margin-bottom:12px;">
            Tetapkan semua rekap Draft menjadi Final untuk periode <strong style="color:#0f172a;">${BULAN_LABEL[bulan - 1]} ${tahun}</strong>.
        </p>
        <table style="width:100%;font-size:13px;margin-bottom:12px;">
            <tr><td style="padding:4px 0;color:#64748b;width:180px;">Jumlah rekap Draft</td><td style="padding:4px 0;font-weight:500;">: ${data.jumlahDraft || 0}</td></tr>
            <tr><td style="padding:4px 0;color:#64748b;">Rekap Final</td><td style="padding:4px 0;">: Tidak akan diubah</td></tr>
        </table>
        <p style="color:#ef4444;font-size:12px;margin:0;">
            Aksi ini tidak dapat dibatalkan.
        </p>
    `;

    openModal('modal-konfirmasi-final');
}

async function submitTetapkanSemuaFinal() {
    const bulan = state.currentBulan;
    const tahun = state.currentTahun;

    if (!bulan || !tahun) return;

    const kartu = document.querySelector(`[data-bulan="${bulan}"]`);
    if (kartu) {
        kartu.innerHTML += '<div class="hr-kartu-loading-overlay"><div class="hr-spinner"></div></div>';
    }

    try {
        // Ambil semua rekap draft
        const res = await apiFetch(`/api/hr/rekap?bulan=${bulan}&tahun=${tahun}&status_rekap=draft`);
        if (!res.status || !res.data) {
            toast('Gagal mengambil data rekap draft.', 'error');
            return;
        }

        const draftList = res.data.data || [];
        if (draftList.length === 0) {
            toast('Tidak ada rekap draft untuk ditetapkan final.', 'warning');
            closeModal('modal-konfirmasi-final');
            return;
        }

        // Loop final
        let berhasil = 0;
        let gagal = 0;

        for (const rekap of draftList) {
            try {
                const finalRes = await apiFetch(`/api/hr/rekap/${rekap.id_rekap}/final`, { method: 'POST' });
                if (finalRes.status) berhasil++;
                else gagal++;
            } catch {
                gagal++;
            }
        }

        if (gagal === 0) {
            toast(`${berhasil} rekap berhasil ditetapkan Final.`, 'success');
        } else {
            toast(`${berhasil} berhasil, ${gagal} gagal ditetapkan Final.`, 'warning');
        }

        closeModal('modal-konfirmasi-final');

        // Reload kartu
        await loadDataBulan(bulan, tahun);
        const data = state.bulanData[bulan];
        const now = new Date();
        const isBelumTerjadi = (tahun === now.getFullYear() && bulan > now.getMonth() + 1) || (tahun > now.getFullYear());
        const newKartu = renderKartuBulan(bulan, tahun, data, isBelumTerjadi);
        if (kartu) kartu.replaceWith(newKartu);
    } catch (err) {
        console.error('[Rekap] submitTetapkanSemuaFinal error:', err);
        toast('Terjadi kesalahan saat tetapkan final.', 'error');
    } finally {
        const overlay = kartu?.querySelector('.hr-kartu-loading-overlay');
        if (overlay) overlay.remove();
    }
}

function unduhExcel(bulan, tahun) {
    window.location.href = `/api/hr/rekap/unduh?bulan=${bulan}&tahun=${tahun}`;
    toast(`Mengunduh rekap ${BULAN_LABEL[bulan - 1]} ${tahun}...`, 'success');
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
    const toast = document.getElementById('hr-toast-rekap');
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
