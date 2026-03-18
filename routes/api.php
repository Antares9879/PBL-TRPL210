<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes — E-Outsourcing PBL-TRPL210
|--------------------------------------------------------------------------
|
| Semua route di sini mengembalikan JSON dengan format:
|   { "status": bool, "message": string, "data": mixed }
|
| Autentikasi menggunakan Sanctum dengan dua mode:
|   - Session (web browser) → stateful via sanctum middleware
|   - Token (mobile/API)    → Bearer token di Authorization header
|
*/

// ── Auth (public, tidak perlu login) ──────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login',  [AuthApiController::class, 'login'])->name('login');
});

// ── Protected routes (wajib login) ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth utilities
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('me',      [AuthApiController::class, 'me'])->name('me');
    });

    // ── Super Admin (F17, F18, F19) ───────────────────────────────────────────
    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('api.super-admin.')
        ->group(function () {
            // Placeholder — akan diisi saat implementasi fitur F17, F18, F19
            // Route::apiResource('akun',             Api\SuperAdmin\AkunApiController::class);
            // Route::apiResource('master-data',      Api\SuperAdmin\MasterDataApiController::class);
            // Route::apiResource('konfigurasi-area', Api\SuperAdmin\KonfigurasiAreaApiController::class);
        });

    // ── Admin Outsource (F07–F11) ─────────────────────────────────────────────
    Route::middleware('role:admin_outsource')
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function () {
            // Placeholder — akan diisi saat implementasi fitur F07–F11
            // Route::apiResource('karyawan',         Api\AdminOutsource\KaryawanApiController::class);
            // Route::apiResource('planning',         Api\AdminOutsource\PlanningKerjaApiController::class);
            // Route::apiResource('validasi-absensi', Api\AdminOutsource\ValidasiAbsensiApiController::class);
        });

    // ── User Departemen (F12) ─────────────────────────────────────────────────
    Route::middleware('role:user_departemen')
        ->prefix('departemen')
        ->name('api.departemen.')
        ->group(function () {
            // Placeholder — akan diisi saat implementasi fitur F12
            // Route::apiResource('validasi-lembur', Api\UserDepartemen\ValidasiLemburApiController::class);
        });

    // ── HR (F13–F16) ──────────────────────────────────────────────────────────
    Route::middleware('role:hr')
        ->prefix('hr')
        ->name('api.hr.')
        ->group(function () {
            // Placeholder — akan diisi saat implementasi fitur F13–F16
            // Route::apiResource('rekap',   Api\HR\RekapApiController::class);
            // Route::apiResource('dokumen', Api\HR\DokumenApiController::class);
        });

    // ── Karyawan (F01–F06) ────────────────────────────────────────────────────
    Route::middleware('role:karyawan')
        ->prefix('karyawan')
        ->name('api.karyawan.')
        ->group(function () {
            // Placeholder — akan diisi saat implementasi fitur F01–F06
            // Route::post('check-in',  [Api\Karyawan\AbsensiApiController::class, 'checkIn']);
            // Route::post('check-out', [Api\Karyawan\AbsensiApiController::class, 'checkOut']);
            // Route::apiResource('lembur',  Api\Karyawan\LemburApiController::class);
            // Route::apiResource('izin',    Api\Karyawan\IzinApiController::class);
            // Route::get('riwayat',    [Api\Karyawan\RiwayatAbsensiApiController::class, 'index']);
            // Route::get('jadwal',     [Api\Karyawan\JadwalApiController::class, 'index']);
        });

});
