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
use App\Http\Controllers\Api\Karyawan\AbsensiApiController;
use App\Http\Controllers\Api\Karyawan\JadwalApiController;
use App\Http\Controllers\Api\Karyawan\LemburApiController;
use App\Http\Controllers\Api\Karyawan\IzinApiController;
use App\Http\Controllers\Api\Karyawan\RiwayatAbsensiApiController;
use App\Http\Controllers\Api\Karyawan\AreaApiController;
use App\Http\Controllers\Api\UserDepartemen\DashboardApiController;
use App\Http\Controllers\Api\UserDepartemen\ValidasiLemburApiController;


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

    // Placeholder role admin outsource
    Route::middleware('role:admin_outsource')
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function () {

            // ── Lookup endpoints (read-only, untuk populate dropdown) ─────────────
            Route::get('lookup/departemen', [DepartemenApiController::class, 'index'])
                ->name('lookup.departemen');
            Route::get('lookup/shift', [ShiftApiController::class, 'index'])
                ->name('lookup.shift');
 
            // F08 — Download template Excel
            Route::get('planning/download-template',
                [PlanningKerjaApiController::class, 'downloadTemplate']
            )->name('planning.download-template');
            
            // F08 — Validasi data dari Excel (belum simpan, return preview + errors)
            Route::post('planning/upload-excel',
                [PlanningKerjaApiController::class, 'uploadExcel']
            )->name('planning.upload-excel');
            
            // F09 — Preview diff sebelum upload ulang
            Route::post('planning/preview-diff',
                [PlanningKerjaApiController::class, 'previewDiff']
            )->name('planning.preview-diff');
            
            // F08 — Simpan planning baru (setelah konfirmasi preview)
            Route::post('planning',
                [PlanningKerjaApiController::class, 'store']
            )->name('planning.store');
            
            // F08 — Index & show
            Route::get('planning',          [PlanningKerjaApiController::class, 'index'])->name('planning.index');
            Route::get('planning/{id}',     [PlanningKerjaApiController::class, 'show'])->name('planning.show');
            
            // F09 — Upload ulang (simpan versi baru setelah konfirmasi diff)
            Route::post('planning/{planning}/upload-ulang',
                [PlanningKerjaApiController::class, 'uploadUlang']
            )->name('planning.upload-ulang');
            
            // Grid interaktif — update satu sel
            Route::put('planning/{planning}/update-jadwal',
                [PlanningKerjaApiController::class, 'updateJadwal']
            )->name('planning.update-jadwal');

            // ── F07 — Manajemen Karyawan (CRUD + aktif/nonaktif + reset password) ──
            Route::apiResource('karyawan', KaryawanApiController::class);
    
            // Endpoint tambahan di luar apiResource standar
            Route::put('karyawan/{karyawan}/aktifkan',
                [KaryawanApiController::class, 'aktifkan']
            )->name('karyawan.aktifkan');
    
            Route::put('karyawan/{karyawan}/reset-password',
                [KaryawanApiController::class, 'resetPassword']
            )->name('karyawan.reset-password');
    
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

    // ── F12 + Dashboard — User Departemen ─────────────────────────────────
    Route::middleware('role:user_departemen')
        ->prefix('departemen')
        ->name('api.departemen.')
        ->group(function () {
 
            // ── Dashboard & Monitoring Absensi (read-only) ────────────────
 
            /** Ringkasan stat cards dashboard */
            Route::get('dashboard/ringkasan',
                [DashboardApiController::class, 'ringkasan']
            )->name('dashboard.ringkasan');
 
            /** Daftar absensi karyawan departemen — paginasi */
            Route::get('dashboard/absensi',
                [DashboardApiController::class, 'absensi']
            )->name('dashboard.absensi');
 
            /** Detail satu record absensi */
            Route::get('dashboard/absensi/{id}',
                [DashboardApiController::class, 'detailAbsensi']
            )->name('dashboard.absensi.show');
 
            /** Daftar karyawan aktif di departemen (untuk filter dropdown) */
            Route::get('dashboard/karyawan',
                [DashboardApiController::class, 'daftarKaryawan']
            )->name('dashboard.karyawan');
 
            // ── F12 — Validasi Pengajuan Lembur ──────────────────────────
 
            /** Daftar pengajuan lembur di departemen */
            Route::get('validasi-lembur',
                [ValidasiLemburApiController::class, 'index']
            )->name('validasi-lembur.index');
 
            /** Detail satu pengajuan lembur */
            Route::get('validasi-lembur/{id}',
                [ValidasiLemburApiController::class, 'show']
            )->name('validasi-lembur.show');
 
            /** Proses approve atau reject */
            Route::post('validasi-lembur/{id}/proses',
                [ValidasiLemburApiController::class, 'proses']
            )->name('validasi-lembur.proses');
        });

    Route::middleware('role:hr')->prefix('hr')->name('api.hr.')->group(function () {});
    
    Route::middleware('role:karyawan')
        ->prefix('karyawan')
        ->name('api.karyawan.')
        ->group(function () {

            // ── Area GPS aktif (untuk peta Leaflet di halaman absensi) ────────
            Route::get('area-aktif', [AreaApiController::class, 'index'])->name('area-aktif');
 
            // F01 — Absensi GPS
            Route::post('check-in',  [AbsensiApiController::class, 'checkIn'])->name('check-in');
            Route::post('check-out', [AbsensiApiController::class, 'checkOut'])->name('check-out');
    
            // F02 — Jadwal Kerja
            Route::get('jadwal',      [JadwalApiController::class, 'index'])->name('jadwal.index');
            Route::get('jadwal/{id}', [JadwalApiController::class, 'show'])->name('jadwal.show');
    
            // F03 — Pengajuan Lembur
            Route::get('lembur',      [LemburApiController::class, 'index'])->name('lembur.index');
            Route::post('lembur',     [LemburApiController::class, 'store'])->name('lembur.store');
            Route::get('lembur/{id}', [LemburApiController::class, 'show'])->name('lembur.show');
    
            // F04 — Pengajuan Izin
            Route::get('izin',      [IzinApiController::class, 'index'])->name('izin.index');
            Route::post('izin',     [IzinApiController::class, 'store'])->name('izin.store');
            Route::get('izin/{id}', [IzinApiController::class, 'show'])->name('izin.show');
    
            // Lookup jenis izin (untuk dropdown form)
            Route::get('jenis-izin', [IzinApiController::class, 'jenisIzin'])->name('jenis-izin');
    
            // F05 — Upload & Download Dokumen Izin
            Route::post('izin/{id}/dokumen',            [IzinApiController::class, 'uploadDokumen'])->name('izin.dokumen.upload');
            Route::get('izin/{id}/dokumen/{docId}',     [IzinApiController::class, 'downloadDokumen'])->name('izin.dokumen.download');
    
            // F06 — Riwayat Absensi Pribadi
            Route::get('riwayat',          [RiwayatAbsensiApiController::class, 'index'])->name('riwayat.index');
            Route::get('riwayat/ringkasan',[RiwayatAbsensiApiController::class, 'ringkasan'])->name('riwayat.ringkasan');
        });

});
