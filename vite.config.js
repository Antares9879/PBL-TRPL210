import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS global
                'resources/css/app.css',

                // CSS per halaman (di-load hanya di halaman yang relevan)
                'resources/css/login.css',

                // JS global & per halaman
                'resources/js/app.js',
                'resources/js/auth/login.js',
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