import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // ── CSS global ──────────────────────────────────────────────
                'resources/css/app.css',

                // ── CSS per role ────────────────────────────────────────────
                'resources/css/login.css',
                'resources/css/super-admin.css',
                'resources/css/admin.css',
                'resources/css/departemen.css',
                'resources/css/karyawan.css',

                // ── JS global ───────────────────────────────────────────────
                'resources/js/app.js',

                // ── JS auth ─────────────────────────────────────────────────
                'resources/js/auth/login.js',

                // ── JS Super Admin ──────────────────────────────────────────
                'resources/js/super-admin/dashboard.js',
                'resources/js/super-admin/akun.js',
                'resources/js/super-admin/master-data.js',
                'resources/js/super-admin/konfigurasi-area.js',
                'resources/js/super-admin/audit-log.js',

                // ── JS Admin Outsource ───────────────────────────────────────────────
                'resources/js/admin-outsource/dashboard.js',
                'resources/js/admin-outsource/karyawan.js',
                'resources/js/admin-outsource/planning.js',
                'resources/js/admin-outsource/validasi-absensi.js',
                'resources/js/admin-outsource/kelola-izin.js',

                // ── JS User Departemen ────────────────────────────────────────
                'resources/js/user-departemen/dashboard.js',
                'resources/js/user-departemen/validasi-lembur.js',
                'resources/js/user-departemen/monitoring-absensi.js',

                // ── JS Karyawan ───────────────────────────────────────────────
                'resources/js/karyawan/dashboard.js',
                'resources/js/karyawan/absensi.js',
                'resources/js/karyawan/izin.js',
                'resources/js/karyawan/lembur.js',
                'resources/js/karyawan/jadwal.js',
                'resources/js/karyawan/riwayat.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
