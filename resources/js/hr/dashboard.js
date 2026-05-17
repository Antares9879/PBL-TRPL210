/**
 * resources/js/hr/dashboard.js
 * F13 — Dashboard Monitoring HR
 *
 * Fitur:
 *  - Load stats cards (7 kartu)
 *  - Load ringkasan per departemen
 *  - Load absensi terbaru (7 hari terakhir) dengan paginasi
 *  - Filter periode (bulan & tahun)
 *  - Filter departemen & perusahaan untuk tabel absensi
 *  - Animasi counter untuk nilai stat cards
 */

// ── State ─────────────────────────────────────────────────────────────────────
let currentPage = 1;
let filterBulan = new Date().getMonth() + 1;
let filterTahun = new Date().getFullYear();
let filterDepartemen = '';
let filterPerusahaan = '';

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initFilterPeriode();
    initLiveDate();
    loadFilterOptions();
    loadStats();
    loadRingkasan();
    loadAbsensi();
    bindEvents();
});

// ── Init Filter Periode ───────────────────────────────────────────────────────
function initFilterPeriode() {
    // Set bulan default
    document.getElementById('filter-bulan').value = filterBulan;

    // Isi dropdown tahun (5 tahun terakhir)
    const selectTahun = document.getElementById('filter-tahun');
    const tahunSekarang = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
        const tahun = tahunSekarang - i;
        const option = document.createElement('option');
        option.value = tahun;
        option.textContent = tahun;
        if (tahun === filterTahun) option.selected = true;
        selectTahun.appendChild(option);
    }
}

// ── Init Live Date ────────────────────────────────────────────────────────────
function initLiveDate() {
    const updateDate = () => {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString('id-ID', options);
        document.getElementById('live-date').textContent = dateStr;
        document.getElementById('tanggal-hari-ini').textContent = dateStr;
    };
    updateDate();
    setInterval(updateDate, 60000); // Update setiap menit
}

// ── Bind Events ───────────────────────────────────────────────────────────────
function bindEvents() {
    // Tombol terapkan filter periode
    document.getElementById('btn-terapkan-filter')?.addEventListener('click', () => {
        filterBulan = parseInt(document.getElementById('filter-bulan').value);
        filterTahun = parseInt(document.getElementById('filter-tahun').value);
        loadStats();
        loadRingkasan();
    });

    // Filter departemen & perusahaan untuk tabel absensi
    document.getElementById('filter-departemen-absensi')?.addEventListener('change', (e) => {
        filterDepartemen = e.target.value;
        loadAbsensi(1);
    });

    document.getElementById('filter-perusahaan-absensi')?.addEventListener('change', (e) => {
        filterPerusahaan = e.target.value;
        loadAbsensi(1);
    });
}

// ── Load Filter Options ───────────────────────────────────────────────────────
async function loadFilterOptions() {
    try {
        const res = await fetch('/api/hr/dashboard/filter-options', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const json = await res.json();

        if (json.status) {
            // Populate dropdown departemen
            const selectDept = document.getElementById('filter-departemen-absensi');
            json.data.departemen.forEach(d => {
                const option = document.createElement('option');
                option.value = d.id_departemen;
                option.textContent = d.nama_departemen;
                selectDept.appendChild(option);
            });

            // Populate dropdown perusahaan
            const selectPerusahaan = document.getElementById('filter-perusahaan-absensi');
            json.data.perusahaan.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id_perusahaan;
                option.textContent = p.nama_perusahaan;
                selectPerusahaan.appendChild(option);
            });
        }
    } catch (err) {
        console.error('[Dashboard HR] Load filter options error:', err);
    }
}

// ── Load Stats ────────────────────────────────────────────────────────────────
async function loadStats() {
    try {
        const params = new URLSearchParams({ bulan: filterBulan, tahun: filterTahun });
        const res = await fetch(`/api/hr/dashboard/stats?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const json = await res.json();

        if (json.status) {
            renderStats(json.data);
        } else {
            showToast(json.message, 'error');
        }
    } catch (err) {
        console.error('[Dashboard HR] Load stats error:', err);
        showToast('Gagal memuat statistik.', 'error');
    }
}

// ── Render Stats ──────────────────────────────────────────────────────────────
function renderStats(data) {
    // Stat cards baris 1
    animateCounter('[data-stat="total-karyawan"]', data.total_karyawan_aktif);
    animateCounter('[data-stat="total-perusahaan"]', data.total_perusahaan);
    animateCounter('[data-stat="total-departemen"]', data.total_departemen);
    animateCounter('[data-stat="hadir-hari-ini"]', data.hari_ini.hadir);

    // Stat cards baris 2
    animateCounter('[data-stat="menunggu-absensi"]', data.menunggu.absensi);
    animateCounter('[data-stat="menunggu-lembur"]', data.menunggu.lembur);
    animateCounter('[data-stat="menunggu-izin"]', data.menunggu.izin);
}

// ── Animate Counter ───────────────────────────────────────────────────────────
function animateCounter(selector, targetValue) {
    const el = document.querySelector(selector);
    if (!el) return;

    const duration = 1000; // 1 detik
    const startValue = 0;
    const startTime = performance.now();

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 3); // Easing cubic-out
        const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOut);

        el.textContent = currentValue.toLocaleString('id-ID');

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            el.textContent = targetValue.toLocaleString('id-ID');
        }
    };

    requestAnimationFrame(animate);
}

// ── Load Ringkasan Per Departemen ─────────────────────────────────────────────
async function loadRingkasan() {
    const tbody = document.getElementById('tbody-ringkasan-departemen');
    tbody.innerHTML = '<tr class="table-skeleton"><td colspan="7"><div class="skeleton-wrap"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line--medium"></div></div></td></tr>';

    try {
        const params = new URLSearchParams({ bulan: filterBulan, tahun: filterTahun });
        const res = await fetch(`/api/hr/dashboard/ringkasan?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const json = await res.json();

        if (json.status) {
            renderRingkasan(json.data.departemen);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">${escHtml(json.message)}</td></tr>`;
        }
    } catch (err) {
        console.error('[Dashboard HR] Load ringkasan error:', err);
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:#ef4444;">Gagal memuat data ringkasan.</td></tr>';
    }
}

// ── Render Ringkasan ──────────────────────────────────────────────────────────
function renderRingkasan(departemen) {
    const tbody = document.getElementById('tbody-ringkasan-departemen');

    if (!departemen.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">Tidak ada data departemen.</td></tr>';
        return;
    }

    tbody.innerHTML = departemen.map(d => {
        const pctClass = d.persentase_kehadiran < 70 ? 'hr-row-warning' : '';
        const progressClass = d.persentase_kehadiran < 70 ? 'hr-progress-bar-fill--danger' :
                              d.persentase_kehadiran < 85 ? 'hr-progress-bar-fill--warning' : '';

        return `
            <tr class="${pctClass}">
                <td>
                    <div style="font-weight:500;color:#0f172a;">${escHtml(d.nama_departemen)}</div>
                    <div style="font-size:11px;color:#94a3b8;">${escHtml(d.kode_departemen)}</div>
                </td>
                <td style="text-align:center;">${d.jumlah_karyawan}</td>
                <td style="text-align:center;">${d.total_hadir}</td>
                <td style="text-align:center;">${d.total_izin}</td>
                <td style="text-align:center;">${d.total_alpa}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="hr-progress-bar" style="flex:1;">
                            <div class="hr-progress-bar-fill ${progressClass}" style="width:${d.persentase_kehadiran}%;"></div>
                        </div>
                        <span style="font-size:12px;font-weight:500;color:#0f172a;min-width:40px;text-align:right;">${d.persentase_kehadiran}%</span>
                    </div>
                </td>
                <td style="text-align:right;">${formatMenit(d.total_menit_lembur)}</td>
            </tr>
        `;
    }).join('');
}

// ── Load Absensi Terbaru ──────────────────────────────────────────────────────
async function loadAbsensi(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbody-absensi-terbaru');
    tbody.innerHTML = '<tr class="table-skeleton"><td colspan="9"><div class="skeleton-wrap"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line--medium"></div></div></td></tr>';

    try {
        const params = new URLSearchParams({ page });
        if (filterDepartemen) params.set('id_departemen', filterDepartemen);
        if (filterPerusahaan) params.set('id_perusahaan', filterPerusahaan);

        const res = await fetch(`/api/hr/dashboard/absensi?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const json = await res.json();

        if (json.status) {
            renderAbsensi(json.data.data);
            renderPaginasi(json.data);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:32px;color:#94a3b8;">${escHtml(json.message)}</td></tr>`;
        }
    } catch (err) {
        console.error('[Dashboard HR] Load absensi error:', err);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:#ef4444;">Gagal memuat data absensi.</td></tr>';
    }
}

// ── Render Absensi ────────────────────────────────────────────────────────────
function renderAbsensi(rows) {
    const tbody = document.getElementById('tbody-absensi-terbaru');

    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:#94a3b8;">Tidak ada data absensi.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(a => `
        <tr>
            <td style="font-size:12px;color:#64748b;">${formatTanggal(a.tanggal_absensi)}</td>
            <td>
                <div style="font-weight:500;color:#0f172a;font-size:13px;">${escHtml(a.karyawan?.nama_lengkap ?? '—')}</div>
                <div style="font-size:11px;color:#94a3b8;">${escHtml(a.karyawan?.nomor_karyawan ?? '—')}</div>
            </td>
            <td style="font-size:12px;color:#475569;">${escHtml(a.karyawan?.departemen ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${escHtml(a.karyawan?.perusahaan ?? '—')}</td>
            <td style="font-size:12px;color:#475569;">${escHtml(a.shift?.nama_shift ?? '—')}</td>
            <td style="font-size:12px;color:#64748b;">${a.waktu_check_in ?? '—'}</td>
            <td style="font-size:12px;color:#64748b;">${a.waktu_check_out ?? '—'}</td>
            <td>${badgeStatusKehadiran(a.status_kehadiran)}</td>
            <td>${badgeStatusValidasi(a.status_validasi)}</td>
        </tr>
    `).join('');
}

// ── Render Paginasi ───────────────────────────────────────────────────────────
function renderPaginasi(meta) {
    const container = document.getElementById('paginasi-absensi');
    if (!container) return;

    const { current_page, last_page, from, to, total } = meta;

    if (last_page <= 1) {
        container.innerHTML = '';
        return;
    }

    const prevDisabled = current_page <= 1;
    const nextDisabled = current_page >= last_page;

    container.innerHTML = `
        <div class="hr-paginasi-info">
            Menampilkan ${from ?? 0} - ${to ?? 0} dari ${total ?? 0} data
        </div>
        <div class="hr-paginasi-buttons">
            <button class="hr-paginasi-btn" ${prevDisabled ? 'disabled' : ''} onclick="loadAbsensi(${current_page - 1})">
                ← Sebelumnya
            </button>
            <span style="padding:6px 12px;font-size:12px;color:#64748b;">
                Halaman ${current_page} dari ${last_page}
            </span>
            <button class="hr-paginasi-btn" ${nextDisabled ? 'disabled' : ''} onclick="loadAbsensi(${current_page + 1})">
                Selanjutnya →
            </button>
        </div>
    `;
}

// ── Badge Helpers ─────────────────────────────────────────────────────────────
function badgeStatusKehadiran(status) {
    const badges = {
        'hadir':   '<span class="hr-badge-hadir">Hadir</span>',
        'izin':    '<span class="hr-badge-izin">Izin</span>',
        'alpa':    '<span class="hr-badge-alpa">Alpa</span>',
        'pending': '<span class="hr-badge-pending">Pending</span>',
    };
    return badges[status] || '<span class="hr-badge-pending">—</span>';
}

function badgeStatusValidasi(status) {
    const badges = {
        'menunggu':  '<span class="hr-badge-menunggu">Menunggu</span>',
        'disetujui': '<span class="hr-badge-disetujui">Disetujui</span>',
        'ditolak':   '<span class="hr-badge-ditolak">Ditolak</span>',
    };
    return badges[status] || '<span class="hr-badge-menunggu">—</span>';
}

// ── Format Helpers ────────────────────────────────────────────────────────────
function formatTanggal(dateStr) {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    const options = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

function formatMenit(menit) {
    if (!menit || menit === 0) return '—';
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;
    if (jam === 0) return `${sisa}m`;
    if (sisa === 0) return `${jam}j`;
    return `${jam}j ${sisa}m`;
}

function escHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ── Toast Notification ────────────────────────────────────────────────────────
function showToast(message, type = 'sukses') {
    const toast = document.createElement('div');
    toast.className = `hr-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Expose loadAbsensi ke global scope untuk paginasi
window.loadAbsensi = loadAbsensi;
