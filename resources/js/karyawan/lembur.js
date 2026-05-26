/**
 * resources/js/karyawan/lembur.js
 *
 * Halaman Pengajuan Lembur (F03).
 * Fitur:
 *   - Form pengajuan lembur dengan validasi tanggal (tidak boleh masa depan, H+1)
 *   - Cek data absensi pada tanggal terpilih secara real-time
 *   - Preview estimasi menit lembur saat jam mulai/selesai diisi
 *   - Tab riwayat lembur dengan filter status + paginasi
 *   - Badge jumlah lembur pending di tab
 *
 * Endpoints:
 *   GET  /api/karyawan/lembur?status=X&page=Y → riwayat lembur
 *   GET  /api/karyawan/riwayat?bulan=X&tahun=Y → cek absensi pada tanggal terpilih
 *   POST /api/karyawan/lembur                  → submit pengajuan
 */

'use strict';

import {
    apiFetch,
    toast,
    formatDate,
    formatMinutes,
    getBadgeHtml,
    renderPagination,
    debounce,
    _escapeHtml,
} from './_utils.js';

// ══════════════════════════════════════════════════════════════════════════════
// 1. STATE
// ══════════════════════════════════════════════════════════════════════════════

const state = {
    activeTab:   'form',    // 'form' | 'riwayat'
    riwayatPage: 1,
    filterStatus: '',
    isSubmitting: false,
    absensiCache: {},       // cache: tanggal → absensi data
};

// ══════════════════════════════════════════════════════════════════════════════
// 2. INISIALISASI
// ══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    bindTabs();
    bindForm();
    bindRiwayat();
    loadRiwayatLembur(1);
    countPending();
});

// ══════════════════════════════════════════════════════════════════════════════
// 3. TAB MANAGEMENT
// ══════════════════════════════════════════════════════════════════════════════

function bindTabs() {
    document.getElementById('tab-form-lembur')?.addEventListener('click', () => switchTab('form'));
    document.getElementById('tab-riwayat-lembur')?.addEventListener('click', () => switchTab('riwayat'));
}

function switchTab(tab) {
    state.activeTab = tab;
    const panels = { form: 'panel-form-lembur', riwayat: 'panel-riwayat-lembur' };
    const tabs   = { form: 'tab-form-lembur',   riwayat: 'tab-riwayat-lembur' };

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
// 4. FORM PENGAJUAN
// ══════════════════════════════════════════════════════════════════════════════

function bindForm() {
    const form          = document.getElementById('form-lembur');
    const tanggalInput  = document.getElementById('lembur-tanggal');
    const jamMulaiInput = document.getElementById('lembur-jam-mulai');
    const jamSelesai    = document.getElementById('lembur-jam-selesai');
    const alasanInput   = document.getElementById('lembur-alasan');
    const resetBtn      = document.getElementById('btn-reset-lembur');

    // Set max tanggal = hari ini
    if (tanggalInput) tanggalInput.max = new Date().toISOString().split('T')[0];

    tanggalInput?.addEventListener('change', debounce(onTanggalChange, 400));

    // Preview menit estimasi saat jam berubah
    const updatePreview = debounce(updateMenitPreview, 300);
    jamMulaiInput?.addEventListener('input', () => {
        hideAutoFillIndicator();
        updatePreview();
    });
    jamSelesai?.addEventListener('input', () => {
        hideAutoFillIndicator();
        updatePreview();
    });

    // Counter karakter alasan
    alasanInput?.addEventListener('input', () => {
        const counter = document.getElementById('alasan-count');
        if (counter) counter.textContent = alasanInput.value.length;
    });

    resetBtn?.addEventListener('click', resetForm);
    form?.addEventListener('submit', handleFormSubmit);
}

async function onTanggalChange(e) {
    const tanggal = e.target.value;
    if (!tanggal) return;

    const infoEl = document.getElementById('lembur-absensi-info');
    if (!infoEl) return;

    // Cek batas H+1
    const lembur = new Date(tanggal);
    const hPlusOne = new Date(lembur);
    hPlusOne.setDate(hPlusOne.getDate() + 1);
    const today = new Date();

    if (today > hPlusOne) {
        showFormAlert(
            `Tanggal ${tanggal} sudah melewati batas H+1. Pengajuan lembur untuk tanggal ini tidak dapat diterima.`,
            'error'
        );
        infoEl.style.display = 'none';
        return;
    } else {
        hideFormAlert();
    }

    // Cek dari cache
    if (state.absensiCache[tanggal]) {
        renderAbsensiInfo(state.absensiCache[tanggal]);
        return;
    }

    // Fetch absensi pada tanggal lembur
    const d = new Date(tanggal);
    try {
        const res = await apiFetch(
            `/api/karyawan/riwayat?bulan=${d.getMonth() + 1}&tahun=${d.getFullYear()}`
        );
        if (res.status) {
            const list    = res.data?.data ?? [];
            const absensi = list.find((a) => a.tanggal_absensi === tanggal);
            state.absensiCache[tanggal] = absensi ?? null;
            renderAbsensiInfo(absensi ?? null);
        }
    } catch {
        infoEl.style.display = 'none';
    }
}

function renderAbsensiInfo(absensi) {
    const infoEl   = document.getElementById('lembur-absensi-info');
    const checkin  = document.getElementById('lembur-absensi-checkin');
    const checkout = document.getElementById('lembur-absensi-checkout');
    const menit    = document.getElementById('lembur-menit-kelebihan');

    if (!infoEl) return;

    if (!absensi || !absensi.waktu_check_out) {
        applyAutoFillJamLembur(null);
        infoEl.style.display = 'none';
        showFormAlert(
            'Tidak ditemukan data absensi check-out pada tanggal ini. Pastikan Anda sudah check-out terlebih dahulu.',
            'warning'
        );
        return;
    }

    const kelebihan = absensi.menit_kelebihan ?? 0;
    const MINIMUM_MENIT = 60;

    // Tampilkan info absensi
    infoEl.style.display = '';
    if (checkin)  checkin.textContent  = formatJam24(absensi.waktu_check_in);
    if (checkout) checkout.textContent = formatJam24(absensi.waktu_check_out);
    if (menit) {
        menit.textContent = kelebihan > 0 ? formatMinutes(kelebihan) : '0 mnt (tidak ada kelebihan)';
    }
    applyAutoFillJamLembur(absensi);

    // Tampilkan warning jika kelebihan < 60 menit
    if (kelebihan > 0 && kelebihan < MINIMUM_MENIT) {
        showFormAlert(
            `Kelebihan waktu kerja Anda adalah ${kelebihan} menit. Minimum ${MINIMUM_MENIT} menit (1 jam) diperlukan untuk dapat mengajukan lembur. Kelebihan waktu tetap tercatat untuk transparansi.`,
            'warning'
        );
    } else if (kelebihan === 0) {
        showFormAlert(
            'Tidak ada kelebihan waktu kerja yang tercatat pada tanggal tersebut.',
            'warning'
        );
    } else {
        hideFormAlert();
    }
}

function applyAutoFillJamLembur(absensi) {
    const jamMulaiInput   = document.getElementById('lembur-jam-mulai');
    const jamSelesaiInput = document.getElementById('lembur-jam-selesai');
    const previewEl       = document.getElementById('lembur-preview-menit');
    const indicatorEl     = document.getElementById('lembur-autofill-indicator');
    const jamMulai        = normalizeJamValue(absensi?.shift?.jam_pulang);
    const jamSelesai      = normalizeJamValue(absensi?.waktu_check_out);

    if (!jamMulaiInput || !jamSelesaiInput) return;

    if (!absensi) {
        jamMulaiInput.value = '';
        jamSelesaiInput.value = '';
        if (previewEl) previewEl.style.display = 'none';
        if (indicatorEl) indicatorEl.style.display = 'none';
        return;
    }

    jamMulaiInput.value = '';
    jamSelesaiInput.value = '';

    if (jamMulai) jamMulaiInput.value = jamMulai;
    if (jamSelesai) jamSelesaiInput.value = jamSelesai;

    if (jamMulaiInput.value && jamSelesaiInput.value) {
        updateMenitPreview();
    }
    if (indicatorEl) {
        indicatorEl.textContent =
            `Jam lembur terisi otomatis dari absensi: mulai ${jamMulaiInput.value} (jam pulang shift), ` +
            `selesai ${jamSelesaiInput.value} (jam check-out).`;
        indicatorEl.style.display = '';
    }
}

function normalizeJamValue(jam) {
    if (typeof jam !== 'string' || jam.trim() === '') return '';
    const trimmed = jam.trim();
    if (/^([01]\d|2[0-3]):([0-5]\d)(:\d{2})?$/.test(trimmed)) {
        return trimmed.slice(0, 5);
    }

    const d = new Date(trimmed);
    if (!isNaN(d)) {
        return d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }

    return '';
}

function formatJam24(jam) {
    return normalizeJamValue(jam) || '—';
}

function updateMenitPreview() {
    const mulaiVal   = document.getElementById('lembur-jam-mulai')?.value;
    const selesaiVal = document.getElementById('lembur-jam-selesai')?.value;
    const previewEl  = document.getElementById('lembur-preview-menit');
    const angkaEl    = document.getElementById('lembur-preview-angka');

    if (!mulaiVal || !selesaiVal || !previewEl || !angkaEl) return;
    if (!isJam24(mulaiVal) || !isJam24(selesaiVal)) {
        previewEl.style.display = 'none';
        return;
    }

    const base   = new Date('2000-01-01T00:00:00');
    let mulai    = new Date(`2000-01-01T${mulaiVal}:00`);
    let selesai  = new Date(`2000-01-01T${selesaiVal}:00`);

    // Handle melewati tengah malam
    if (selesai <= mulai) selesai.setDate(selesai.getDate() + 1);

    const menit = Math.max(0, Math.round((selesai - mulai) / 60000));

    angkaEl.textContent = `${menit} mnt`;
    previewEl.style.display = menit > 0 ? 'flex' : 'none';
}

async function handleFormSubmit(e) {
    e.preventDefault();
    if (state.isSubmitting) return;

    hideFormAlert();
    clearFieldErrors();

    const form = e.target;
    const data = {
        tanggal_lembur:       form.tanggal_lembur?.value,
        jam_mulai_estimasi:   form.jam_mulai_estimasi?.value,
        jam_selesai_estimasi: form.jam_selesai_estimasi?.value,
        alasan_lembur:        form.alasan_lembur?.value?.trim(),
    };

    // Validasi frontend dasar
    if (!validateLemburForm(data)) return;

    state.isSubmitting = true;
    setSubmitLoading(true);

    try {
        const res = await apiFetch('/api/karyawan/lembur', {
            method: 'POST',
            body: data,
        });

        if (!res.status) {
            // Tampilkan error per field jika ada
            if (res.data && typeof res.data === 'object') {
                renderFieldErrors(res.data);
            }
            showFormAlert(res.message ?? 'Gagal mengajukan lembur.', 'error');
            return;
        }

        // Berhasil
        if (res.data?.status === 'kadaluarsa') {
            toast('Pengajuan ditolak otomatis karena melewati batas H+1.', 'warning', 5000);
        } else {
            toast('Pengajuan lembur berhasil dikirim. Menunggu persetujuan User Departemen.', 'success', 5000);
        }

        resetForm();
        loadRiwayatLembur(1);
        countPending();

    } catch (err) {
        showFormAlert(err.message, 'error');
    } finally {
        state.isSubmitting = false;
        setSubmitLoading(false);
    }
}

function validateLemburForm(data) {
    let valid = true;

    if (!data.tanggal_lembur) {
        showFieldError('err-lembur-tanggal', 'Tanggal lembur wajib diisi.');
        valid = false;
    }
    if (!data.jam_mulai_estimasi) {
        showFieldError('err-lembur-jam-mulai', 'Jam mulai wajib diisi.');
        valid = false;
    } else if (!isJam24(data.jam_mulai_estimasi)) {
        showFieldError('err-lembur-jam-mulai', 'Format jam mulai harus 24 jam (HH:mm).');
        valid = false;
    }
    if (!data.jam_selesai_estimasi) {
        showFieldError('err-lembur-jam-selesai', 'Jam selesai wajib diisi.');
        valid = false;
    } else if (!isJam24(data.jam_selesai_estimasi)) {
        showFieldError('err-lembur-jam-selesai', 'Format jam selesai harus 24 jam (HH:mm).');
        valid = false;
    }
    if (!data.alasan_lembur || data.alasan_lembur.length < 10) {
        showFieldError('err-lembur-alasan', 'Alasan lembur minimal 10 karakter.');
        valid = false;
    }

    return valid;
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. RIWAYAT LEMBUR
// ══════════════════════════════════════════════════════════════════════════════

function bindRiwayat() {
    document.getElementById('filter-status-lembur')?.addEventListener('change', (e) => {
        state.filterStatus = e.target.value;
        loadRiwayatLembur(1);
    });
}

async function loadRiwayatLembur(page) {
    state.riwayatPage = page;
    const container  = document.getElementById('riwayat-lembur-list');
    const pagEl      = document.getElementById('paginasi-lembur');
    if (!container) return;

    container.innerHTML = _skeletonLembur(3);

    let url = `/api/karyawan/lembur?page=${page}`;
    if (state.filterStatus) url += `&status=${state.filterStatus}`;

    try {
        const res = await apiFetch(url);

        if (!res.status) {
            container.innerHTML = _emptyLembur('Gagal memuat riwayat lembur.');
            return;
        }

        const list = res.data?.data ?? [];
        const meta = res.data;

        if (list.length === 0) {
            container.innerHTML = _emptyLembur('Tidak ada pengajuan lembur.');
            if (pagEl) pagEl.innerHTML = '';
            return;
        }

        container.innerHTML = list.map((l) => _renderLemburItem(l)).join('');

        if (pagEl) {
            renderPagination(pagEl, meta, loadRiwayatLembur);
        }

    } catch (err) {
        container.innerHTML = _emptyLembur(err.message);
    }
}

async function countPending() {
    try {
        const res = await apiFetch('/api/karyawan/lembur?status=menunggu');
        if (!res.status) return;
        const total = res.data?.total ?? 0;
        const badge = document.getElementById('badge-lembur-pending');
        if (badge) {
            badge.textContent    = total;
            badge.style.display  = total > 0 ? '' : 'none';
        }
    } catch { /* silent */ }
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. RENDER HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function _renderLemburItem(l) {
    const statusIcon = {
        menunggu:   { cls: 'k-pengajuan-icon--menunggu',  icon: '🕐' },
        disetujui:  { cls: 'k-pengajuan-icon--disetujui', icon: '✓' },
        ditolak:    { cls: 'k-pengajuan-icon--ditolak',   icon: '✕' },
        kadaluarsa: { cls: 'k-pengajuan-icon--kadaluarsa',icon: '!' },
    }[l.status] ?? { cls: 'k-pengajuan-icon--menunggu', icon: '?' };

    const batasPengajuan = l.batas_pengajuan
        ? `Batas: ${formatDate(l.batas_pengajuan)}`
        : '';

    const lemburMenit = l.menit_lembur_resmi > 0
        ? `${formatMinutes(l.menit_lembur_resmi)} resmi`
        : `${formatMinutes(l.menit_lembur_diajukan)} diajukan`;

    return `
        <div class="k-pengajuan-item">
            <div class="k-pengajuan-icon ${statusIcon.cls}"
                 aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div class="k-pengajuan-body">
                <p class="k-pengajuan-title">
                    Lembur ${formatDate(l.tanggal_lembur)} &nbsp; ${lemburMenit}
                </p>
                <div class="k-pengajuan-meta">
                    <span>${l.jam_mulai_estimasi ?? '—'} – ${l.jam_selesai_estimasi ?? '—'}</span>
                    ${batasPengajuan ? `
                        <span class="k-pengajuan-meta-dot" aria-hidden="true"></span>
                        <span>${_escapeHtml(batasPengajuan)}</span>
                    ` : ''}
                    ${l.alasan_lembur ? `
                        <span class="k-pengajuan-meta-dot" aria-hidden="true"></span>
                        <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            ${_escapeHtml(l.alasan_lembur)}
                        </span>
                    ` : ''}
                </div>
                ${l.catatan_penolakan ? `
                    <p style="font-size:11px;color:var(--status-alpa);margin-top:3px;">
                        Alasan ditolak: ${_escapeHtml(l.catatan_penolakan)}
                    </p>` : ''}
            </div>
            <div class="k-pengajuan-actions">
                ${getBadgeHtml(l.status, 'validasi')}
            </div>
        </div>`;
}

function _skeletonLembur(count) {
    return Array.from({ length: count }).map(() => `
        <div class="k-pengajuan-item">
            <div class="k-skel k-skel--block"
                 style="width:36px;height:36px;flex-shrink:0;border-radius:var(--radius-md);"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:5px;">
                <div class="k-skel k-skel--text" style="width:65%;"></div>
                <div class="k-skel k-skel--text" style="width:40%;"></div>
            </div>
            <div class="k-skel" style="width:60px;height:20px;border-radius:999px;"></div>
        </div>`).join('');
}

function _emptyLembur(msg) {
    return `<div class="k-empty" style="padding:var(--space-6) var(--space-4);">
                <p class="k-empty-title">${_escapeHtml(msg)}</p>
            </div>`;
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. FORM HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function resetForm() {
    const form = document.getElementById('form-lembur');
    form?.reset();
    document.getElementById('alasan-count').textContent = '0';
    document.getElementById('lembur-absensi-info').style.display = 'none';
    document.getElementById('lembur-preview-menit').style.display = 'none';
    hideAutoFillIndicator();
    hideFormAlert();
    clearFieldErrors();
}

function hideAutoFillIndicator() {
    const indicatorEl = document.getElementById('lembur-autofill-indicator');
    if (indicatorEl) indicatorEl.style.display = 'none';
}

function showFormAlert(msg, type = 'error') {
    const el = document.getElementById('lembur-alert');
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
    const el = document.getElementById('lembur-alert');
    if (el) el.style.display = 'none';
}

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.add('k-field-error--visible');
}

function clearFieldErrors() {
    document.querySelectorAll('.k-field-error').forEach((el) => {
        el.textContent = '';
        el.classList.remove('k-field-error--visible');
    });
}

function renderFieldErrors(errors) {
    const fieldMap = {
        tanggal_lembur:       'err-lembur-tanggal',
        jam_mulai_estimasi:   'err-lembur-jam-mulai',
        jam_selesai_estimasi: 'err-lembur-jam-selesai',
        alasan_lembur:        'err-lembur-alasan',
    };
    Object.entries(errors).forEach(([field, msgs]) => {
        const id = fieldMap[field];
        if (id) showFieldError(id, Array.isArray(msgs) ? msgs[0] : msgs);
    });
}

function setSubmitLoading(isLoading) {
    const btn     = document.getElementById('btn-submit-lembur');
    const text    = document.getElementById('submit-lembur-text');
    const spinner = document.getElementById('spinner-lembur');
    if (!btn) return;
    btn.disabled = isLoading;
    if (text)    text.style.display    = isLoading ? 'none' : '';
    if (spinner) spinner.classList.toggle('k-btn-spinner--visible', isLoading);
}

function isJam24(value) {
    return /^([01]\d|2[0-3]):([0-5]\d)$/.test(String(value ?? '').trim());
}
