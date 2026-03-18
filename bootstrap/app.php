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
        $middleware->trustProxies(headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling dipusatkan di app/Exceptions/Handler.php
    })
    ->create();
