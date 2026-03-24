<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;  
use App\Http\Controllers\SuperAdmin\PageController as SuperAdminPageController;
use App\Http\Controllers\AdminOutsource\PageController as AdminOutsourcePageController;
use App\Http\Controllers\Karyawan\PageController as KaryawanPageController;

/*
|--------------------------------------------------------------------------
| Web Routes — E-Outsourcing PBL-TRPL210
|--------------------------------------------------------------------------
|
| Web routes hanya bertugas serve halaman HTML (Blade shell).
| Tidak ada logika bisnis di sini — semua data dimuat via AJAX
| dari routes/api.php.
|
*/

// ── Guest routes ──────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect('/login'));
Route::get('/login', fn() => view('auth.login'))->name('login');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Super Admin (F17, F18, F19) ───────────────────────────────────────────
    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            Route::get('dashboard',              [SuperAdminPageController::class, 'dashboard'])           ->name('dashboard');
            Route::get('akun',                   [SuperAdminPageController::class, 'akun'])                ->name('akun');
            Route::get('master-data/perusahaan', [SuperAdminPageController::class, 'masterDataPerusahaan'])->name('master-data.perusahaan');
            Route::get('master-data/departemen', [SuperAdminPageController::class, 'masterDataDepartemen'])->name('master-data.departemen');
            Route::get('master-data/shift',      [SuperAdminPageController::class, 'masterDataShift'])     ->name('master-data.shift');
            Route::get('konfigurasi-area',       [SuperAdminPageController::class, 'konfigurasiArea'])     ->name('konfigurasi-area');
            Route::get('audit-log',              [SuperAdminPageController::class, 'auditLog'])            ->name('audit-log');
        });

    // ── HR (F13–F16) ──────────────────────────────────────────────────────────
    // Placeholder — PageController HR akan dibuat saat implementasi fitur HR
    // Route::middleware('role:hr')
    //     ->prefix('hr')
    //     ->name('hr.')
    //     ->group(function () {
    //         Route::get('dashboard', [HR\PageController::class, 'dashboard'])->name('dashboard');
    //     });

    // ── User Departemen (F12) ─────────────────────────────────────────────────
    // Placeholder — PageController User Departemen akan dibuat saat implementasi
    // Route::middleware('role:user_departemen')
    //     ->prefix('departemen')
    //     ->name('departemen.')
    //     ->group(function () {
    //         Route::get('dashboard', [UserDepartemen\PageController::class, 'dashboard'])->name('dashboard');
    //     });

    // ── Admin Outsource (F07–F11) ─────────────────────────────────────────────
    Route::middleware('role:admin_outsource')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard',        [AdminOutsourcePageController::class, 'dashboard'])      ->name('dashboard');
        Route::get('karyawan',         [AdminOutsourcePageController::class, 'karyawan'])       ->name('karyawan');
        Route::get('planning',         [AdminOutsourcePageController::class, 'planning'])       ->name('planning');
        Route::get('validasi-absensi', [AdminOutsourcePageController::class, 'validasiAbsensi'])->name('validasi-absensi');
        Route::get('riwayat-absensi',  [AdminOutsourcePageController::class, 'riwayatAbsensi'] )->name('riwayat-absensi');
        Route::get('kelola-izin',      [AdminOutsourcePageController::class, 'kelolaIzin']     )->name('kelola-izin');
    });

    // ── Karyawan (F01–F06) ────────────────────────────────────────────────────
    Route::middleware('role:karyawan')
        ->prefix('karyawan')
        ->name('karyawan.')
        ->group(function () {
            Route::get('dashboard', [KaryawanPageController::class, 'dashboard'])->name('dashboard');
            Route::get('jadwal',    [KaryawanPageController::class, 'jadwalKerja'])->name('jadwal');
            Route::get('absensi',   [KaryawanPageController::class, 'absensi'])     ->name('absensi');
            Route::get('lembur',    [KaryawanPageController::class, 'ajukanLembur'])->name('lembur');
            Route::get('izin',      [KaryawanPageController::class, 'ajukanIzin'])     ->name('izin');
            Route::get('riwayat',   [KaryawanPageController::class, 'lihatAbsensi'])   ->name('riwayat');
         });

    // ── Logout ────────────────────────────────────────────────────────────────
    // Logout tetap di web route karena diproses dari form Blade (non-AJAX).
    // JS juga bisa hit POST /api/auth/logout untuk logout via AJAX.
    Route::post('logout', function () {
        auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});
