<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\PageController as SuperAdminPageController;

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
    // Placeholder — PageController Admin Outsource akan dibuat saat implementasi
    // Route::middleware('role:admin_outsource')
    //     ->prefix('admin')
    //     ->name('admin.')
    //     ->group(function () {
    //         Route::get('dashboard', [AdminOutsource\PageController::class, 'dashboard'])->name('dashboard');
    //     });

    // ── Karyawan (F01–F06) ────────────────────────────────────────────────────
    // Placeholder — PageController Karyawan akan dibuat saat implementasi
    // Route::middleware('role:karyawan')
    //     ->prefix('karyawan')
    //     ->name('karyawan.')
    //     ->group(function () {
    //         Route::get('dashboard', [Karyawan\PageController::class, 'dashboard'])->name('dashboard');
    //     });

    // ── Logout ────────────────────────────────────────────────────────────────
    // Logout tetap di web route karena diproses dari form Blade (non-AJAX).
    // JS juga bisa hit POST /api/auth/logout untuk logout via AJAX.
    Route::post('logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});
