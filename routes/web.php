<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SuperAdmin\PageController as SuperAdminPageController;

// ── Guest routes ──────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect('/login'));
Route::get('/login', fn() => view('auth.login'))->name('login');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Super Admin ──────────────────────────────────────────────────────────
    Route::middleware('role:super_admin')
        ->prefix('super-admin')
        ->name('super-admin.')
        ->group(function () {
            Route::get('dashboard',                   [SuperAdminPageController::class, 'dashboard'])           ->name('dashboard');
            Route::get('akun',                        [SuperAdminPageController::class, 'akun'])                ->name('akun');
            Route::get('master-data/perusahaan',      [SuperAdminPageController::class, 'masterDataPerusahaan'])->name('master-data.perusahaan');
            Route::get('master-data/departemen',      [SuperAdminPageController::class, 'masterDataDepartemen'])->name('master-data.departemen');
            Route::get('master-data/shift',           [SuperAdminPageController::class, 'masterDataShift'])     ->name('master-data.shift');
            Route::get('konfigurasi-area',            [SuperAdminPageController::class, 'konfigurasiArea'])     ->name('konfigurasi-area');
            Route::get('audit-log',                   [SuperAdminPageController::class, 'auditLog'])            ->name('audit-log');
        });

    // ── Logout ───────────────────────────────────────────────────────────────
    Route::post('logout', function () {
        auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});

