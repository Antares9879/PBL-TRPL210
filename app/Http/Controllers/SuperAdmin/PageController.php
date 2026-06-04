<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * SuperAdmin\PageController
 *
 * Hanya bertugas serve halaman HTML (Blade shell).
 * Tidak ada logika bisnis di sini — semua data dimuat via AJAX
 * dari App\Http\Controllers\Api\SuperAdmin\*.
 */
class PageController extends Controller
{
    public function dashboard()
    {
        return view('super-admin.dashboard');
    }

    public function akun()
    {
        return view('super-admin.akun');
    }

    public function masterDataPerusahaan()
    {
        return view('super-admin.master-data-perusahaan');
    }

    public function masterDataDepartemen()
    {
        return view('super-admin.master-data-departemen');
    }

    public function masterDataShift()
    {
        return view('super-admin.master-data-shift');
    }

    public function konfigurasiArea()
    {
        return view('super-admin.konfigurasi-area');
    }

    public function auditLog()
    {
        return view('super-admin.audit-log');
    }

    /** Halaman semua notifikasi Super Admin */
    public function notifikasi()
    {
        return view('super-admin.notifikasi');
    }
}