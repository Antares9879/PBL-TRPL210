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

                // ── JS global ───────────────────────────────────────────────
                'resources/js/app.js',

                // ── JS auth ─────────────────────────────────────────────────
                'resources/js/auth/login.js',

                // ── JS Super Admin ──────────────────────────────────────────
                'resources/js/super-admin/dashboard.js',
                'resources/js/super-admin/akun.js',
                'resources/js/super-admin/master-data.js',
                'resources/js/super-admin/konfigurasi-area.js',

                // ── JS Admin Outsource ───────────────────────────────────────────────
                'resources/js/admin/dashboard.js',
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
