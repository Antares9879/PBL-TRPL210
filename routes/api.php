<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthApiController;
use App\Http\Controllers\Api\SuperAdmin\AkunApiController;
use App\Http\Controllers\Api\SuperAdmin\PerusahaanApiController;
use App\Http\Controllers\Api\SuperAdmin\DepartemenApiController;
use App\Http\Controllers\Api\SuperAdmin\ShiftApiController;
use App\Http\Controllers\Api\SuperAdmin\KonfigurasiAreaApiController;
use App\Http\Controllers\Api\AdminOutsource\KaryawanApiController;
use App\Http\Controllers\Api\AdminOutsource\PlanningKerjaApiController;
use App\Http\Controllers\Api\AdminOutsource\ValidasiAbsensiApiController;

// ── Auth (public) ──────────────────────────────────────────────────────────────
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login', [AuthApiController::class, 'login'])->name('login');
});

// ── Protected routes ───────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('me',      [AuthApiController::class, 'me'])->name('me');
    });

    // F17 + F18 + F19 — Super Admin
    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('api.super-admin.')
        ->group(function () {

            // F17 — Manajemen Akun
            Route::apiResource('akun', AkunApiController::class);
            Route::put('akun/{akun}/reset-password', [AkunApiController::class, 'resetPassword'])
                ->name('akun.reset-password');

            // F18 — Master Data
            Route::apiResource('perusahaan', PerusahaanApiController::class);
            Route::apiResource('departemen', DepartemenApiController::class);
            Route::apiResource('shift',      ShiftApiController::class);

            // F19 — Konfigurasi Area GPS
            Route::apiResource('konfigurasi-area', KonfigurasiAreaApiController::class);
        });

    // Placeholder role lain
    Route::middleware('role:admin_outsource')
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
 
        // ── F07 — Manajemen Karyawan (CRUD + aktif/nonaktif + reset password) ──
        Route::apiResource('karyawan', KaryawanApiController::class);
 
        // Endpoint tambahan di luar apiResource standar
        Route::put('karyawan/{karyawan}/aktifkan',
            [KaryawanApiController::class, 'aktifkan']
        )->name('karyawan.aktifkan');
 
        Route::put('karyawan/{karyawan}/reset-password',
            [KaryawanApiController::class, 'resetPassword']
        )->name('karyawan.reset-password');
 
        // ── F08–F09 — Planning Kerja ──────────────────────────────────────────
        Route::apiResource('planning', PlanningKerjaApiController::class)
            ->only(['index', 'store', 'show']);
 
        Route::post('planning/{planning}/upload-ulang',
            [PlanningKerjaApiController::class, 'uploadUlang']
        )->name('planning.upload-ulang');
 
        // ── F10–F11 — Validasi & Pantau Absensi ──────────────────────────────
        // Absensi
        Route::get('validasi-absensi',
            [ValidasiAbsensiApiController::class, 'index']
        )->name('validasi-absensi.index');
 
        Route::post('validasi-absensi/{id}',
            [ValidasiAbsensiApiController::class, 'validasi']
        )->name('validasi-absensi.validasi');
 
        // Izin
        Route::get('validasi-izin',
            [ValidasiAbsensiApiController::class, 'indexIzin']
        )->name('validasi-izin.index');
 
        Route::post('validasi-izin/{id}',
            [ValidasiAbsensiApiController::class, 'validasiIzin']
        )->name('validasi-izin.validasi');
    });

    Route::middleware('role:user_departemen')->prefix('departemen')->name('api.departemen.')->group(function () {});
    Route::middleware('role:hr')->prefix('hr')->name('api.hr.')->group(function () {});
    Route::middleware('role:karyawan')->prefix('karyawan')->name('api.karyawan.')->group(function () {});

});
