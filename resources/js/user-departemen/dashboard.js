/**
 * resources/js/user-departemen/dashboard.js
 * Dashboard User Departemen — E-Outsourcing PBL-TRPL210
 *
 * Endpoint:
 *   GET /api/departemen/dashboard/ringkasan
 *   GET /api/departemen/dashboard/absensi?hari_ini=true&limit=5
 */

import { apiFetch, esc, fmtWaktu, fmtTanggal, badgeKehadiran, toast } from './_utils.js';

document.addEventListener('DOMContentLoaded', () => {

    // ── Live date ─────────────────────────────────────────────────────────────
    const dateEl = document.getElementById('live-date');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        });
    }

    // ── Helper: set data-stat elements ────────────────────────────────────────
    function setStat(key, value) {
        document.querySelectorAll(`[data-stat="${key}"]`).forEach(el => {
            el.textContent = (value !== null && value !== undefined) ? value : '—';
        });
    }

    // ── Helper: update badge angka di sidebar nav ─────────────────────────────
    function updateNavBadges(lemburPending) {
        const badge = document.getElementById('badge-lembur-pending');
        if (badge) {
            badge.textContent = lemburPending > 99 ? '99+' : lemburPending;
            badge.style.display = lemburPending > 0 ? 'flex' : 'none';
        }
    }

    // ── fetchRingkasan ────────────────────────────────────────────────────────
    async function fetchRingkasan() {
        try {
            const res  = await apiFetch('/api/departemen/dashboard/ringkasan');
            const json = await res.json();
            if (!json.status) return;

            const d = json.data;

            // Stat cards
            setStat('karyawan-aktif',  d.karyawan_aktif ?? 0);
            setStat('hadir-hari-ini',  d.hari_ini?.hadir ?? 0);
            setStat('belum-absen',     d.hari_ini?.belum_absen ?? 0);
            setStat('lembur-pending',  d.lembur_menunggu_proses ?? 0);

            // Statistik bulan ini
            setStat('total-menit-lembur', d.bulan_ini?.total_menit_lembur_disetujui ?? 0);
            setStat('izin-hari-ini',      d.hari_ini?.izin ?? 0);
            setStat('alpa-hari-ini',      d.hari_ini?.alpa ?? 0);

            // Periode bulan
            const bulanNama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const bulanPeriode = document.getElementById('bulan-periode');
            if (bulanPeriode && d.periode) {
                bulanPeriode.textContent = `${bulanNama[d.periode.bulan - 1]} ${d.periode.tahun}`;
            }

            // Nav badges
            updateNavBadges(d.lembur_menunggu_proses ?? 0);

        } catch (err) {
            console.error('[Dashboard] fetchRingkasan error:', err);
        }
    }

    // ── fetchAbsensiPreview ───────────────────────────────────────────────────
    async function fetchAbsensiPreview() {
        const tbody = document.getElementById('tbody-absensi-preview');
        if (!tbody) return;

        try {
            const res  = await apiFetch('/api/departemen/dashboard/absensi?hari_ini=true&limit=5');
            const json = await res.json();
            if (!json.status) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                    Tidak ada data absensi hari ini.</td></tr>`;
                return;
            }

            const rows = json.data?.data ?? json.data ?? [];
            renderAbsensiPreview(rows, tbody);

        } catch (err) {
            console.error('[Dashboard] fetchAbsensiPreview error:', err);
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                Gagal memuat data absensi.</td></tr>`;
        }
    }

    function renderAbsensiPreview(rows, tbody) {
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                Tidak ada absensi hari ini.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(row => {
            const namaKaryawan = row.karyawan?.nama_lengkap ?? row.nama_karyawan ?? '—';
            const namaShift = row.shift?.nama_shift ?? row.nama_shift ?? '—';
            
            return `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:30px;height:30px;border-radius:7px;flex-shrink:0;
                            background:linear-gradient(135deg,#0f766e,#042f2e);
                            display:flex;align-items:center;justify-content:center;
                            font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:#5eead4;">
                            ${esc(namaKaryawan?.charAt(0)?.toUpperCase() ?? '?')}
                        </div>
                        <div>
                            <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(namaKaryawan)}</div>
                            <div style="font-size:11px;color:#94a3b8;">${esc(row.karyawan?.nomor_karyawan ?? '')}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:#475569;">${esc(namaShift)}</td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                    ${fmtWaktu(row.waktu_check_in)}</td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                    ${fmtWaktu(row.waktu_check_out)}</td>
                <td>${badgeKehadiran(row.status_kehadiran)}</td>
            </tr>
        `;
        }).join('');
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    fetchRingkasan();
    fetchAbsensiPreview();
});
