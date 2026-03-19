/**
 * resources/js/admin-outsource/dashboard.js
 * Dashboard Admin Outsource — E-Outsourcing PBL-TRPL210
 *
 * Tanggung jawab file ini:
 *  1. Live date display
 *  2. fetchStats()           → isi 4 stat cards + update company banner
 *  3. fetchAbsensiPreview()  → render 5 baris tabel absensi pending hari ini
 *  4. fetchNotifikasi()      → render panel notifikasi (3 jenis)
 *  5. updateNavBadges()      → tampilkan badge angka di sidebar nav
 *  6. updateNotifDot()       → tampilkan/sembunyikan dot merah di topbar
 *
 * Endpoint yang akan dihubungkan saat backend siap:
 *   GET /api/admin/dashboard/stats
 *   GET /api/admin/validasi-absensi?limit=5&status=menunggu
 *   GET /api/admin/notifikasi?limit=6
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Live date ─────────────────────────────────────────────────────────────
    const dateEl = document.getElementById('live-date');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        });
    }

    // ── CSRF + fetch helper ───────────────────────────────────────────────────
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function apiFetch(url, options = {}) {
        return fetch(url, {
            ...options,
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers ?? {}),
            },
        });
    }

    // ── Helper: set nilai elemen via data-stat ────────────────────────────────
    function setStat(key, value) {
        document.querySelectorAll(`[data-stat="${key}"]`).forEach(el => {
            el.textContent = value ?? '—';
        });
    }

    // ── Helper: escape HTML ───────────────────────────────────────────────────
    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Helper: format waktu HH:MM dari datetime string ───────────────────────
    function fmtWaktu(dt) {
        if (!dt) return '—';
        return String(dt).slice(11, 16);
    }

    // ── Helper: badge status kehadiran ────────────────────────────────────────
    function badgeAbsensi(status) {
        const map = {
            hadir:   '<span class="badge badge--success">Hadir</span>',
            telat:   '<span class="badge badge--warning">Telat</span>',
            izin:    '<span class="badge badge--info">Izin</span>',
            alpa:    '<span class="badge badge--danger">Alpa</span>',
            pending: '<span class="badge badge--neutral">Pending</span>',
        };
        return map[status] ?? `<span class="badge badge--neutral">${esc(status)}</span>`;
    }

    // ── Helper: ikon notifikasi per jenis ─────────────────────────────────────
    function notifIcon(jenis) {
        const icons = {
            izin: {
                cls: 'notif-icon--izin',
                svg: `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>`,
            },
            dokumen: {
                cls: 'notif-icon--dokumen',
                svg: `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
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
        return icons[jenis] ?? icons.izin;
    }

    // ── Update badge angka di sidebar nav ─────────────────────────────────────
    function updateNavBadges(absensiPending, izinPending) {
        const badgeAbsensiEl = document.getElementById('badge-validasi-absensi');
        const badgeIzinEl    = document.getElementById('badge-izin');

        if (badgeAbsensiEl) {
            if (absensiPending > 0) {
                badgeAbsensiEl.textContent = absensiPending > 99 ? '99+' : absensiPending;
                badgeAbsensiEl.style.display = 'flex';
            } else {
                badgeAbsensiEl.style.display = 'none';
            }
        }

        if (badgeIzinEl) {
            if (izinPending > 0) {
                badgeIzinEl.textContent = izinPending > 99 ? '99+' : izinPending;
                badgeIzinEl.style.display = 'flex';
            } else {
                badgeIzinEl.style.display = 'none';
            }
        }
    }

    // ── Tampilkan/sembunyikan notif dot di topbar ─────────────────────────────
    function updateNotifDot(hasUnread) {
        const dot = document.getElementById('notif-dot');
        if (dot) dot.style.display = hasUnread ? 'block' : 'none';
    }

    // ── Render baris notifikasi ───────────────────────────────────────────────
    function renderNotifikasi(items) {
        const list        = document.getElementById('notif-list');
        const placeholder = document.getElementById('notif-placeholder');
        const countTag    = document.getElementById('notif-count-tag');

        if (!list) return;

        // Hapus placeholder
        if (placeholder) placeholder.remove();

        if (!items || items.length === 0) {
            list.insertAdjacentHTML('afterbegin', `
                <div class="notif-empty">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    Tidak ada notifikasi baru.
                </div>
            `);
            return;
        }

        const unreadCount = items.filter(n => !n.is_dibaca).length;

        if (countTag && unreadCount > 0) {
            countTag.textContent = `${unreadCount} baru`;
            countTag.style.display = 'inline-flex';
        }

        // Render item dari ujung atas
        const html = items.map(item => {
            const icon = notifIcon(item.jenis);
            return `
                <div class="notif-item">
                    <div class="notif-icon ${icon.cls}">${icon.svg}</div>
                    <div class="notif-content">
                        <span class="notif-title">${esc(item.judul)}</span>
                        <span class="notif-meta">${esc(item.isi)}</span>
                    </div>
                    ${!item.is_dibaca ? '<span class="notif-unread-dot"></span>' : ''}
                </div>
            `;
        }).join('');

        // Insert sebelum link "Lihat semua"
        list.insertAdjacentHTML('afterbegin', html);

        updateNotifDot(unreadCount > 0);
    }

    // ── Render preview absensi ────────────────────────────────────────────────
    function renderAbsensiPreview(rows) {
        const tbody = document.getElementById('tbody-absensi-preview');
        if (!tbody) return;

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">
                        Tidak ada absensi yang menunggu validasi hari ini.
                    </td>
                </tr>
            `;
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
                            <div style="font-weight:500;color:#0f172a;font-size:13px;">
                                ${esc(row.nama_karyawan)}
                            </div>
                            <div style="font-size:11px;color:#94a3b8;">
                                ${esc(row.nomor_karyawan)}
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:#475569;">${esc(row.nama_shift)}</td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#0f172a;">
                    ${fmtWaktu(row.waktu_check_in)}
                </td>
                <td style="font-family:'Syne',sans-serif;font-size:13px;color:#475569;">
                    ${fmtWaktu(row.waktu_check_out)}
                </td>
                <td>
                    ${row.menit_telat > 0
                        ? `<span style="font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#d97706;">
                               +${row.menit_telat} mnt
                           </span>`
                        : `<span style="color:#94a3b8;font-size:12px;">—</span>`
                    }
                </td>
                <td>${badgeAbsensi(row.status_kehadiran)}</td>
            </tr>
        `).join('');
    }

    // ════════════════════════════════════════════════════════════════════
    //  FETCH FUNCTIONS — uncomment saat endpoint backend siap
    // ════════════════════════════════════════════════════════════════════

    // async function fetchStats() {
    //     try {
    //         const res  = await apiFetch('/api/admin/dashboard/stats');
    //         const json = await res.json();
    //         if (!json.status) return;
    //
    //         const d = json.data;
    //
    //         // Stat cards
    //         setStat('total-karyawan',  d.karyawan_aktif);
    //         setStat('karyawan-total',  d.karyawan_total);
    //         setStat('absensi-pending', d.absensi_pending);
    //         setStat('planning-status', d.planning_status_label);  // 'Aktif' / 'Draft' / 'Belum Ada'
    //         setStat('planning-periode', d.planning_periode);       // 'Maret 2025'
    //         setStat('izin-pending',    d.izin_pending);
    //
    //         // Company banner
    //         const namaBanner = document.getElementById('nama-perusahaan');
    //         const metaBanner = document.getElementById('meta-perusahaan');
    //         if (namaBanner) namaBanner.textContent = d.nama_perusahaan ?? '—';
    //         if (metaBanner) metaBanner.textContent = `${d.karyawan_total} karyawan terdaftar`;
    //
    //         // Planning list
    //         setStat('planning-label-1',    d.planning_1?.periode ?? 'Bulan Ini');
    //         setStat('planning-versi-1',    d.planning_1?.versi ?? '—');
    //         setStat('planning-karyawan-1', d.planning_1?.jumlah_karyawan ?? '—');
    //         setStat('planning-label-2',    d.planning_2?.periode ?? 'Bulan Lalu');
    //         setStat('planning-label-3',    d.planning_3?.periode ?? 'Bulan Depan');
    //
    //         // Update badge planning badge ke-1 sesuai status
    //         const badge1 = document.getElementById('planning-badge-1');
    //         if (badge1 && d.planning_1) {
    //             const statusMap = {
    //                 aktif:      ['planning-badge--aktif',      'Aktif'],
    //                 draft:      ['planning-badge--draft',      'Draft'],
    //                 diperbarui: ['planning-badge--diperbarui', 'Diperbarui'],
    //             };
    //             const [cls, label] = statusMap[d.planning_1.status] ?? ['planning-badge--belum', 'Belum Ada'];
    //             badge1.className = `planning-badge ${cls}`;
    //             badge1.textContent = label;
    //         }
    //
    //         // Nav badges + notif dot
    //         updateNavBadges(d.absensi_pending, d.izin_pending);
    //
    //         const notifCountTag = document.getElementById('notif-count-tag');
    //         if (d.izin_pending > 0 || d.dokumen_kurang > 0) {
    //             updateNotifDot(true);
    //         }
    //
    //     } catch (err) {
    //         console.error('[Dashboard] fetchStats error:', err);
    //     }
    // }

    // async function fetchAbsensiPreview() {
    //     try {
    //         const res  = await apiFetch('/api/admin/validasi-absensi?limit=5&status=menunggu');
    //         const json = await res.json();
    //         if (!json.status) return;
    //         renderAbsensiPreview(json.data?.data ?? json.data ?? []);
    //     } catch (err) {
    //         console.error('[Dashboard] fetchAbsensiPreview error:', err);
    //     }
    // }

    // async function fetchNotifikasi() {
    //     try {
    //         const res  = await apiFetch('/api/admin/notifikasi?limit=6');
    //         const json = await res.json();
    //         if (!json.status) return;
    //         renderNotifikasi(json.data ?? []);
    //     } catch (err) {
    //         console.error('[Dashboard] fetchNotifikasi error:', err);
    //     }
    // }

    // ── Init — aktifkan saat endpoint siap ────────────────────────────────────
    // fetchStats();
    // fetchAbsensiPreview();
    // fetchNotifikasi();

});