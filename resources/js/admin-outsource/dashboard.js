/**
 * resources/js/admin-outsource/dashboard.js
 * Dashboard Admin Outsource — E-Outsourcing PBL-TRPL210
 *
 * Endpoint:
 *   GET /api/admin/dashboard/stats
 *   GET /api/admin/validasi-absensi?limit=5&status=menunggu
 *   GET /api/admin/notifikasi?limit=6
 */

import { apiFetch, esc, fmtWaktu, badgeKehadiran } from './_utils.js';

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
    function updateNavBadges(absensiPending, izinPending) {
        const bA = document.getElementById('badge-validasi-absensi');
        const bI = document.getElementById('badge-izin');
        if (bA) {
            bA.textContent = absensiPending > 99 ? '99+' : absensiPending;
            bA.style.display = absensiPending > 0 ? 'flex' : 'none';
        }
        if (bI) {
            bI.textContent = izinPending > 99 ? '99+' : izinPending;
            bI.style.display = izinPending > 0 ? 'flex' : 'none';
        }
    }

    // ── Helper: notif dot di topbar ───────────────────────────────────────────
    function updateNotifDot(hasUnread) {
        const dot = document.getElementById('notif-dot');
        if (dot) dot.style.display = hasUnread ? 'block' : 'none';
    }

    // ── fetchStats ────────────────────────────────────────────────────────────
    async function fetchStats() {
        try {
            const res  = await apiFetch('/api/admin/dashboard/stats');
            const json = await res.json();
            if (!json.status) return;

            const d = json.data;

            // Stat cards
            setStat('total-karyawan',  d.karyawan_aktif ?? 0);
            setStat('karyawan-total',  d.karyawan_total ?? 0);
            setStat('absensi-pending', d.absensi_pending ?? 0);
            setStat('izin-pending',    d.izin_pending ?? 0);

            // Planning card
            setStat('planning-status',  d.planning_status_label ?? 'Belum Ada');
            setStat('planning-periode', d.planning_periode ?? '—');

            // Company banner
            const namaBanner = document.getElementById('nama-perusahaan');
            const metaBanner = document.getElementById('meta-perusahaan');
            if (namaBanner) namaBanner.textContent = d.nama_perusahaan ?? '—';
            if (metaBanner) metaBanner.textContent = `${d.karyawan_total ?? 0} karyawan terdaftar`;

            // Planning list (3 periode)
            if (d.planning_1) {
                setStat('planning-label-1',    d.planning_1.periode ?? 'Bulan Ini');
                setStat('planning-versi-1',    d.planning_1.versi   ?? '—');
                setStat('planning-karyawan-1', d.planning_1.jumlah_karyawan ?? 0);

                const badge1 = document.getElementById('planning-badge-1');
                if (badge1) {
                    const statusMap = {
                        aktif:      ['planning-badge--aktif',      'Aktif'],
                        draft:      ['planning-badge--draft',      'Draft'],
                        diperbarui: ['planning-badge--diperbarui', 'Diperbarui'],
                    };
                    const [cls, label] = statusMap[d.planning_1.status] ?? ['planning-badge--belum', 'Belum Ada'];
                    badge1.className  = `planning-badge ${cls}`;
                    badge1.textContent = label;
                }
            }
            if (d.planning_2) setStat('planning-label-2', d.planning_2.periode ?? 'Bulan Lalu');
            if (d.planning_3) setStat('planning-label-3', d.planning_3.periode ?? 'Bulan Depan');

            // Badge card stat coloring
            const statAbsensi = document.querySelector('.stat-card--rose .stat-card-badge');
            if (statAbsensi && d.absensi_pending > 0) {
                statAbsensi.className = 'stat-card-badge stat-card-badge--danger';
            }

            // Nav badges
            updateNavBadges(d.absensi_pending ?? 0, d.izin_pending ?? 0);

            if ((d.absensi_pending ?? 0) > 0 || (d.izin_pending ?? 0) > 0) {
                updateNotifDot(true);
            }

        } catch (err) {
            console.error('[Dashboard] fetchStats error:', err);
        }
    }

    // ── fetchAbsensiPreview ───────────────────────────────────────────────────
    async function fetchAbsensiPreview() {
        const tbody = document.getElementById('tbody-absensi-preview');
        if (!tbody) return;

        try {
            const res  = await apiFetch('/api/admin/validasi-absensi?limit=5&status_validasi=menunggu');
            const json = await res.json();
            if (!json.status) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                    Tidak ada absensi pending hari ini.</td></tr>`;
                return;
            }

            const rows = json.data?.data ?? json.data ?? [];
            renderAbsensiPreview(rows, tbody);

        } catch (err) {
            console.error('[Dashboard] fetchAbsensiPreview error:', err);
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                Gagal memuat data absensi.</td></tr>`;
        }
    }

    function renderAbsensiPreview(rows, tbody) {
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                Tidak ada absensi yang menunggu validasi hari ini.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(row => `
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="karyawan-avatar">
                            ${esc(row.nama_karyawan?.charAt(0)?.toUpperCase() ?? '?')}
                        </div>
                        <div>
                            <div style="font-weight:500;color:#0f172a;font-size:13px;">${esc(row.nama_karyawan)}</div>
                            <div style="font-size:11px;color:#94a3b8;">${esc(row.nomor_karyawan ?? '')}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:#475569;">${esc(row.nama_shift ?? '—')}</td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                    ${fmtWaktu(row.waktu_check_in)}</td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                    ${fmtWaktu(row.waktu_check_out)}</td>
                <td>
                    ${(row.menit_telat ?? 0) > 0
                        ? `<span style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#d97706;">
                               +${row.menit_telat} mnt</span>`
                        : `<span style="color:#94a3b8;font-size:12px;">—</span>`
                    }
                </td>
                <td>${badgeKehadiran(row.status_kehadiran)}</td>
            </tr>
        `).join('');
    }

    // ── fetchNotifikasi ───────────────────────────────────────────────────────
    async function fetchNotifikasi() {
        const list        = document.getElementById('notif-list');
        const placeholder = document.getElementById('notif-placeholder');
        const countTag    = document.getElementById('notif-count-tag');
        if (!list) return;

        try {
            const res  = await apiFetch('/api/admin/notifikasi?limit=6');
            const json = await res.json();

            if (placeholder) placeholder.remove();

            if (!json.status || !json.data?.length) {
                list.insertAdjacentHTML('afterbegin', `
                    <div class="notif-empty">
                        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Tidak ada notifikasi baru.
                    </div>`);
                return;
            }

            const items      = json.data;
            const unreadCount= items.filter(n => !n.is_dibaca).length;

            if (countTag && unreadCount > 0) {
                countTag.textContent = `${unreadCount} baru`;
                countTag.style.display = 'inline-flex';
            }

            const iconMap = {
                izin: {
                    cls: 'notif-icon--izin',
                    svg: `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                          </svg>`,
                },
                absensi: {
                    cls: 'notif-icon--absensi',
                    svg: `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                          </svg>`,
                },
                planning: {
                    cls: 'notif-icon--planning',
                    svg: `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
                          </svg>`,
                },
            };

            const html = items.map(item => {
                const icon = iconMap[item.jenis] ?? iconMap.izin;
                return `
                    <div class="notif-item">
                        <div class="notif-icon ${icon.cls}">${icon.svg}</div>
                        <div class="notif-content">
                            <span class="notif-title">${esc(item.judul)}</span>
                            <span class="notif-meta">${esc(item.isi)}</span>
                        </div>
                        ${!item.is_dibaca ? '<span class="notif-unread-dot"></span>' : ''}
                    </div>`;
            }).join('');

            list.insertAdjacentHTML('afterbegin', html);
            updateNotifDot(unreadCount > 0);

        } catch (err) {
            console.error('[Dashboard] fetchNotifikasi error:', err);
        }
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    fetchStats();
    fetchAbsensiPreview();
    fetchNotifikasi();
});