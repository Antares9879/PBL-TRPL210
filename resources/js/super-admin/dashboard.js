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

    // ─── Fetch stats ──────────────────────────────────────────────────────────
    fetchStats();
    fetchAuditLog();

    async function fetchStats() {
        try {
            const res = await fetch('/api/super-admin/dashboard/stats', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });
            const { status, data } = await res.json();
            if (!status) return;

            // Isi stat cards
            setStatValue('total-pengguna',   data.total_pengguna);
            setStatValue('pengguna-baru',    `${data.pengguna_baru} baru bulan ini`);
            setStatValue('total-perusahaan', data.total_perusahaan);
            setStatValue('perusahaan-aktif', `${data.perusahaan_aktif} aktif terdaftar`);
            setStatValue('total-departemen', data.total_departemen);
            setStatValue('departemen-aktif', `${data.departemen_aktif} aktif beroperasi`);
            setStatValue('radius-meter',     data.radius_meter);
            setStatValue('radius-updated',   data.radius_updated);

            // Role bar chart
            setRoleBar('hr',    data.pct_hr,    data.count_hr);
            setRoleBar('dept',  data.pct_dept,  data.count_dept);
            setRoleBar('admin', data.pct_admin, data.count_admin);
            setRoleBar('super', data.pct_super, data.count_super);

        } catch (err) {
            console.error('[Dashboard] Stats fetch gagal:', err);
        }
    }

    async function fetchAuditLog() {
        try {
            const res = await fetch('/api/super-admin/dashboard/audit-log?limit=10', {
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });
            const { status, data } = await res.json();
            if (!status || !data.length) {
                const tbody = document.getElementById('audit-table-body');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;font-size:13px;">Belum ada aktivitas sistem.</td></tr>';
                }
                return;
            }

            const tbody = document.getElementById('audit-table-body');
            tbody.innerHTML = data.map(row => `
                <tr>
                    <td style="font-size:12px;color:#475569;">${row.created_at}</td>
                    <td style="font-weight:500;color:#0f172a;font-size:13px;">${row.pengguna_nama}</td>
                    <td style="font-size:13px;color:#0f172a;">${row.aksi}</td>
                    <td style="font-size:12px;color:#64748b;">${row.modul}</td>
                    <td><span class="badge badge--${row.badge_class}">${row.status}</span></td>
                </tr>
            `).join('');
        } catch (err) {
            console.error('[Dashboard] Audit log fetch gagal:', err);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function setStatValue(key, value) {
        const el = document.querySelector(`[data-stat="${key}"]`);
        if (el) el.textContent = value;
    }

    function setRoleBar(role, pct, count) {
        const bar   = document.querySelector(`.role-bar--${role}`);
        const badge = document.querySelector(`[data-stat="count-${role}"]`);
        if (bar)   bar.style.width = `${pct}%`;
        if (badge) badge.textContent = count;
    }

});