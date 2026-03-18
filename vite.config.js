import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // ── CSS global ──────────────────────────────────────────────
                'resources/css/app.css',

                // ── CSS per halaman ─────────────────────────────────────────
                'resources/css/login.css',
                'resources/css/super-admin.css',     // Layout utama + semua dashboard

                // ── JS global ───────────────────────────────────────────────
                'resources/js/app.js',

                // ── JS per halaman ──────────────────────────────────────────
                'resources/js/auth/login.js',
                'resources/js/super-admin/dashboard.js',
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