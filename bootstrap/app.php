<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Sanctum: izinkan session-based auth untuk SPA/web request ────────
        // Ini yang membuat Sanctum bisa melayani dua jenis client:
        //   1. Browser (session cookie) → untuk semua role web
        //   2. Mobile / API consumer (Bearer token) → untuk karyawan mobile
        $middleware->statefulApi();

        // ── Register alias middleware 'role' ──────────────────────────────────
        // Penggunaan: Route::middleware('role:super_admin')
        //             Route::middleware('role:hr,admin_outsource')
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // ── Trust semua proxy (penting untuk produksi di balik load balancer) ─
        // Wajib include HEADER_X_FORWARDED_PROTO, bukan cuma FOR — kalau cuma FOR,
        // Laravel tidak percaya header X-Forwarded-Proto dari Railway dan akan
        // generate semua asset/redirect URL sebagai http://, bukan https:// (mixed content).
        $middleware->trustProxies(headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR
            | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST
            | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT
            | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO);

    })
    ->withSchedule(function ($schedule) {
        // ── Session Garbage Collection ────────────────────────────────────────
        // Cleanup expired sessions dari database setiap hari jam 2 pagi
        // Menghapus session yang sudah expired (last_activity > SESSION_LIFETIME)
        $schedule->command('session:gc')->daily()->at('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling dipusatkan di app/Exceptions/Handler.php
    })
    ->create();