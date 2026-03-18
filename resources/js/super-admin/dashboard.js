/**
 * resources/js/super-admin/dashboard.js
 * Dashboard Super Admin — E-Outsourcing PBL-TRPL210
 *
 * File ini HANYA berisi:
 * 1. Live date display
 * 2. Skeleton → placeholder data (nanti diganti fetch ke API nyata)
 *
 * Endpoint yang akan dihubungkan saat backend siap:
 *   GET /api/super-admin/dashboard/stats  → stat cards
 *   GET /api/super-admin/audit-log?limit=10 → tabel audit
 */

document.addEventListener('DOMContentLoaded', () => {

    // ─── Live date ────────────────────────────────────────────────────────────
    const dateEl = document.getElementById('live-date');
    if (dateEl) {
        const now = new Date();
        dateEl.textContent = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    // ─── TODO: Fetch stats ────────────────────────────────────────────────────
    // Uncomment dan akan sesuaikan saat endpoint backeding siap. Sementara ini, data stat cards dan audit log masih hardcoded di blade template.
    // fetchStats();
    // fetchAuditLog();

    // async function fetchStats() {
    //     try {
    //         const res = await fetch('/api/super-admin/dashboard/stats', {
    //             headers: {
    //                 'Accept': 'application/json',
    //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    //             }
    //         });
    //         const { status, data } = await res.json();
    //         if (!status) return;
    //
    //         // Isi stat cards
    //         setStatValue('total-pengguna',   data.total_pengguna);
    //         setStatValue('pengguna-baru',    `${data.pengguna_baru} baru bulan ini`);
    //         setStatValue('total-perusahaan', data.total_perusahaan);
    //         setStatValue('total-departemen', data.total_departemen);
    //         setStatValue('radius-meter',     data.radius_meter);
    //         setStatValue('radius-updated',   data.radius_updated);
    //
    //         // Role bar chart
    //         setRoleBar('hr',    data.pct_hr,    data.count_hr);
    //         setRoleBar('dept',  data.pct_dept,  data.count_dept);
    //         setRoleBar('admin', data.pct_admin, data.count_admin);
    //         setRoleBar('super', data.pct_super, data.count_super);
    //
    //     } catch (err) {
    //         console.error('[Dashboard] Stats fetch gagal:', err);
    //     }
    // }
    //
    // async function fetchAuditLog() {
    //     try {
    //         const res = await fetch('/api/super-admin/audit-log?limit=10', {
    //             headers: { 'Accept': 'application/json' }
    //         });
    //         const { status, data } = await res.json();
    //         if (!status || !data.length) return;
    //
    //         const tbody = document.getElementById('audit-table-body');
    //         tbody.innerHTML = data.map(row => `
    //             <tr>
    //                 <td>${row.created_at}</td>
    //                 <td>${row.pengguna_nama}</td>
    //                 <td>${row.aksi}</td>
    //                 <td>${row.modul}</td>
    //                 <td><span class="badge badge--${row.badge_class}">${row.status}</span></td>
    //             </tr>
    //         `).join('');
    //     } catch (err) {
    //         console.error('[Dashboard] Audit log fetch gagal:', err);
    //     }
    // }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    // function setStatValue(key, value) {
    //     const el = document.querySelector(`[data-stat="${key}"]`);
    //     if (el) el.textContent = value;
    // }
    //
    // function setRoleBar(role, pct, count) {
    //     const bar   = document.querySelector(`.role-bar--${role}`);
    //     const badge = document.querySelector(`[data-stat="count-${role}"]`);
    //     if (bar)   bar.style.width = `${pct}%`;
    //     if (badge) badge.textContent = count;
    // }

});