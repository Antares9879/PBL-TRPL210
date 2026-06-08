<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthApiController;
use App\Http\Controllers\Api\SuperAdmin\AkunApiController;
use App\Http\Controllers\Api\SuperAdmin\PerusahaanApiController;
use App\Http\Controllers\Api\SuperAdmin\DepartemenApiController;
use App\Http\Controllers\Api\SuperAdmin\ShiftApiController;
use App\Http\Controllers\Api\SuperAdmin\KonfigurasiAreaApiController;
use App\Http\Controllers\Api\SuperAdmin\DashboardApiController as SuperAdminDashboardApiController;
use App\Http\Controllers\Api\SuperAdmin\AuditLogApiController as SuperAdminAuditLogApiController;
use App\Http\Controllers\Api\AdminOutsource\KaryawanApiController;
use App\Http\Controllers\Api\AdminOutsource\PlanningKerjaApiController;
use App\Http\Controllers\Api\AdminOutsource\ValidasiAbsensiApiController;
use App\Http\Controllers\Api\AdminOutsource\DashboardApiController as AdminDashboardApiController;
use App\Http\Controllers\Api\HR\DashboardApiController as HRDashboardApiController;
use App\Http\Controllers\Api\HR\DokumenApiController as HRDokumenApiController;
use App\Http\Controllers\Api\HR\RekapApiController as HRRekapApiController;
use App\Http\Controllers\Api\HR\AuditLogApiController as HRAuditLogApiController;
use App\Http\Controllers\Api\Karyawan\AbsensiApiController;
use App\Http\Controllers\Api\Karyawan\JadwalApiController;
use App\Http\Controllers\Api\Karyawan\LemburApiController;
use App\Http\Controllers\Api\Karyawan\IzinApiController;
use App\Http\Controllers\Api\Karyawan\RiwayatAbsensiApiController;
use App\Http\Controllers\Api\Karyawan\AreaApiController;
use App\Http\Controllers\Api\UserDepartemen\DashboardApiController;
use App\Http\Controllers\Api\UserDepartemen\ValidasiLemburApiController;
use App\Http\Controllers\Api\NotifikasiApiController;

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

    // ── Notifikasi (shared untuk semua role) ──────────────────────────────────
    Route::prefix('notifikasi')->name('api.notifikasi.')->group(function () {
        Route::get('/',             [NotifikasiApiController::class, 'index'])       ->name('index');
        Route::get('/jumlah-baru',  [NotifikasiApiController::class, 'jumlahBaru']) ->name('jumlah-baru');
        Route::patch('/{id}/baca',  [NotifikasiApiController::class, 'tandaiBaca']) ->name('tandai-baca');
        Route::patch('/baca-semua', [NotifikasiApiController::class, 'bacaSemua'])  ->name('baca-semua');
    });

    // ── F17 + F18 + F19 — Super Admin ─────────────────────────────────────────
    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('api.super-admin.')
        ->group(function () {

            // Dashboard
            Route::get('dashboard/stats',     [SuperAdminDashboardApiController::class, 'stats'])    ->name('dashboard.stats');
            Route::get('dashboard/audit-log', [SuperAdminDashboardApiController::class, 'auditLog']) ->name('dashboard.audit-log');

            // Audit Log lengkap
            Route::get('audit-log',      [SuperAdminAuditLogApiController::class, 'index'])->name('audit-log.index');
            Route::get('audit-log/{id}', [SuperAdminAuditLogApiController::class, 'show']) ->name('audit-log.show');

            // F17 — Manajemen Akun
            Route::apiResource('akun', AkunApiController::class);
            Route::put('akun/{akun}/reset-password', [AkunApiController::class, 'resetPassword'])->name('akun.reset-password');

            // F18 — Master Data
            Route::apiResource('perusahaan', PerusahaanApiController::class);
            Route::apiResource('departemen', DepartemenApiController::class);
            Route::apiResource('shift',      ShiftApiController::class);

            // F19 — Konfigurasi Area GPS
            Route::apiResource('konfigurasi-area', KonfigurasiAreaApiController::class);
        });

    // ── F13–F16 — HR Ecogreen ──────────────────────────────────────────────────
    Route::middleware('role:hr')
        ->prefix('hr')
        ->name('api.hr.')
        ->group(function () {

            // ── F13 — Dashboard & Monitoring Keseluruhan ──────────────────────

            /** Stat cards utama dashboard HR */
            Route::get('dashboard/stats', [HRDashboardApiController::class, 'stats'])
                ->name('dashboard.stats');

            /** Ringkasan kehadiran per departemen */
            Route::get('dashboard/ringkasan', [HRDashboardApiController::class, 'ringkasan'])
                ->name('dashboard.ringkasan');

            /** Daftar absensi lintas departemen (paginasi + filter) */
            Route::get('dashboard/absensi', [HRDashboardApiController::class, 'absensi'])
                ->name('dashboard.absensi');

            /** Detail satu record absensi */
            Route::get('dashboard/absensi/{id}', [HRDashboardApiController::class, 'detailAbsensi'])
                ->name('dashboard.absensi.show');

            /** Opsi dropdown filter (departemen & perusahaan) */
            Route::get('dashboard/filter-options', [HRDashboardApiController::class, 'filterOptions'])
                ->name('dashboard.filter-options');

            // ── F14 — Verifikasi Kelengkapan Dokumen ──────────────────────────

            /** Daftar pengajuan izin beserta status dokumen */
            Route::get('dokumen', [HRDokumenApiController::class, 'index'])
                ->name('dokumen.index');

            /** Detail satu pengajuan izin */
            Route::get('dokumen/{id}', [HRDokumenApiController::class, 'show'])
                ->name('dokumen.show');

            /** Tandai dokumen lengkap atau tidak lengkap */
            Route::post('dokumen/{id}/verifikasi', [HRDokumenApiController::class, 'verifikasi'])
                ->name('dokumen.verifikasi');

            /** Tandai dokumen secara bulk (lengkap/tidak lengkap) */
            Route::post('dokumen/bulk-verifikasi', [HRDokumenApiController::class, 'bulkVerifikasi'])
                ->name('dokumen.bulk-verifikasi');

            /** Stream / preview file dokumen */
            Route::get('dokumen/{id}/stream/{docId}', [HRDokumenApiController::class, 'streamDokumen'])
                ->name('dokumen.stream');

            // ── F15 — Rekap Absensi Bulanan ───────────────────────────────────

            /** Daftar rekap tersimpan di DB (paginasi) */
            Route::get('rekap', [HRRekapApiController::class, 'index'])
                ->name('rekap.index');

            /** Preview data real-time sebelum generate / unduh */
            Route::get('rekap/preview', [HRRekapApiController::class, 'preview'])
                ->name('rekap.preview');

            /** Cek status dokumen sebelum penetapan Final */
            Route::get('rekap/cek-dokumen', [HRRekapApiController::class, 'cekDokumen'])
                ->name('rekap.cek-dokumen');

            /** Download rekap dalam format Excel */
            Route::get('rekap/unduh', [HRRekapApiController::class, 'unduh'])
                ->name('rekap.unduh');

            /** Generate & simpan rekap ke DB */
            Route::post('rekap/generate', [HRRekapApiController::class, 'generate'])
                ->name('rekap.generate');

            /** Tetapkan satu rekap sebagai Final */
            Route::post('rekap/{id}/final', [HRRekapApiController::class, 'tetapkanFinal'])
                ->name('rekap.final');

            // ── F16 — Audit Log Approval ──────────────────────────────────────

            /** Daftar audit log (absensi, lembur, izin) dengan paginasi & filter */
            Route::get('audit', [HRAuditLogApiController::class, 'index'])
                ->name('audit.index');

            /** Ringkasan statistik aksi per periode */
            Route::get('audit/ringkasan', [HRAuditLogApiController::class, 'ringkasan'])
                ->name('audit.ringkasan');

            /** Detail satu entri audit log */
            Route::get('audit/{id}', [HRAuditLogApiController::class, 'show'])
                ->name('audit.show');
        });

    // ── F07–F11 — Admin Outsource ──────────────────────────────────────────────
    Route::middleware('role:admin_outsource')
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function () {

            // Dashboard
            Route::get('dashboard/stats', [AdminDashboardApiController::class, 'stats'])
                ->name('dashboard.stats');
            Route::get('notifikasi', [AdminDashboardApiController::class, 'notifikasi'])
                ->name('notifikasi');

            // Lookup (read-only, untuk populate dropdown)
            Route::get('lookup/departemen', [DepartemenApiController::class, 'index'])->name('lookup.departemen');
            Route::get('lookup/shift',      [ShiftApiController::class, 'index'])     ->name('lookup.shift');

            // F08 — Planning
            Route::get('planning/download-template', [PlanningKerjaApiController::class, 'downloadTemplate'])->name('planning.download-template');
            Route::post('planning/upload-excel',     [PlanningKerjaApiController::class, 'uploadExcel'])     ->name('planning.upload-excel');
            Route::post('planning/preview-diff',     [PlanningKerjaApiController::class, 'previewDiff'])     ->name('planning.preview-diff');
            Route::post('planning',                  [PlanningKerjaApiController::class, 'store'])            ->name('planning.store');
            Route::get('planning',                   [PlanningKerjaApiController::class, 'index'])            ->name('planning.index');
            Route::get('planning/{id}',              [PlanningKerjaApiController::class, 'show'])             ->name('planning.show');
            Route::post('planning/{planning}/upload-ulang',   [PlanningKerjaApiController::class, 'uploadUlang'])  ->name('planning.upload-ulang');
            Route::put('planning/{planning}/update-jadwal',   [PlanningKerjaApiController::class, 'updateJadwal']) ->name('planning.update-jadwal');

            // F07 — Manajemen Karyawan
            Route::apiResource('karyawan', KaryawanApiController::class);
            Route::put('karyawan/{karyawan}/aktifkan',      [KaryawanApiController::class, 'aktifkan'])     ->name('karyawan.aktifkan');
            Route::put('karyawan/{karyawan}/reset-password',[KaryawanApiController::class, 'resetPassword'])->name('karyawan.reset-password');

            // F10–F11 — Validasi Absensi & Izin
            Route::get('validasi-absensi',       [ValidasiAbsensiApiController::class, 'index'])       ->name('validasi-absensi.index');
            Route::post('validasi-absensi/bulk-approve', [ValidasiAbsensiApiController::class, 'bulkApprove'])->name('validasi-absensi.bulk-approve');
            Route::post('validasi-absensi/bulk-reject',  [ValidasiAbsensiApiController::class, 'bulkReject']) ->name('validasi-absensi.bulk-reject');
            
            // F10 — Single validation dengan modal konfirmasi
            Route::post('validasi-absensi/{id}/approve', [ValidasiAbsensiApiController::class, 'approve'])->name('validasi-absensi.approve');
            Route::post('validasi-absensi/{id}/reject',  [ValidasiAbsensiApiController::class, 'reject']) ->name('validasi-absensi.reject');
            
            // F10 — Bulk validation
            Route::post('validasi-absensi/bulk-approve', [ValidasiAbsensiApiController::class, 'bulkApprove'])->name('validasi-absensi.bulk-approve');
            Route::post('validasi-absensi/bulk-reject',  [ValidasiAbsensiApiController::class, 'bulkReject']) ->name('validasi-absensi.bulk-reject');
            
            Route::get('validasi-izin',          [ValidasiAbsensiApiController::class, 'indexIzin'])   ->name('validasi-izin.index');
            Route::get('validasi-izin/{id}',     [ValidasiAbsensiApiController::class, 'showIzin'])    ->name('validasi-izin.show');
            Route::post('validasi-izin/{id}',    [ValidasiAbsensiApiController::class, 'validasiIzin'])->name('validasi-izin.validasi');

            Route::get('/izin/{id}/dokumen/{docId}',
                [\App\Http\Controllers\Api\AdminOutsource\DokumenIzinAdminController::class, 'stream']
            );
        });

    // ── F12 + Dashboard — User Departemen ─────────────────────────────────────
    Route::middleware('role:user_departemen')
        ->prefix('departemen')
        ->name('api.departemen.')
        ->group(function () {

            Route::get('dashboard/ringkasan',    [DashboardApiController::class, 'ringkasan'])    ->name('dashboard.ringkasan');
            Route::get('dashboard/absensi',      [DashboardApiController::class, 'absensi'])      ->name('dashboard.absensi');
            Route::get('dashboard/absensi/{id}', [DashboardApiController::class, 'detailAbsensi'])->name('dashboard.absensi.show');
            Route::get('dashboard/karyawan',     [DashboardApiController::class, 'daftarKaryawan'])->name('dashboard.karyawan');

            Route::get('validasi-lembur',              [ValidasiLemburApiController::class, 'index'])->name('validasi-lembur.index');
            Route::get('validasi-lembur/{id}',         [ValidasiLemburApiController::class, 'show']) ->name('validasi-lembur.show');
            Route::post('validasi-lembur/{id}/proses', [ValidasiLemburApiController::class, 'proses'])->name('validasi-lembur.proses');
        });

    // ── F01–F06 — Karyawan ────────────────────────────────────────────────────
    Route::middleware('role:karyawan')
        ->prefix('karyawan')
        ->name('api.karyawan.')
        ->group(function () {

            Route::get('area-aktif', [AreaApiController::class, 'index'])->name('area-aktif');

            Route::post('check-in',  [AbsensiApiController::class, 'checkIn']) ->name('check-in');
            Route::post('check-out', [AbsensiApiController::class, 'checkOut'])->name('check-out');

            Route::get('jadwal',      [JadwalApiController::class, 'index'])->name('jadwal.index');
            Route::get('jadwal/{id}', [JadwalApiController::class, 'show']) ->name('jadwal.show');

            Route::get('lembur',      [LemburApiController::class, 'index'])->name('lembur.index');
            Route::post('lembur',     [LemburApiController::class, 'store'])->name('lembur.store');
            Route::get('lembur/{id}', [LemburApiController::class, 'show']) ->name('lembur.show');

            Route::get('izin',      [IzinApiController::class, 'index'])->name('izin.index');
            Route::post('izin',     [IzinApiController::class, 'store'])->name('izin.store');
            Route::get('izin/{id}', [IzinApiController::class, 'show']) ->name('izin.show');

            Route::get('jenis-izin', [IzinApiController::class, 'jenisIzin'])->name('jenis-izin');

            Route::post('izin/{id}/dokumen',        [IzinApiController::class, 'uploadDokumen'])  ->name('izin.dokumen.upload');
            Route::get('izin/{id}/dokumen/{docId}', [IzinApiController::class, 'downloadDokumen'])->name('izin.dokumen.download');

            Route::get('riwayat',           [RiwayatAbsensiApiController::class, 'index'])    ->name('riwayat.index');
            Route::get('riwayat/ringkasan', [RiwayatAbsensiApiController::class, 'ringkasan'])->name('riwayat.ringkasan');
        });
});
