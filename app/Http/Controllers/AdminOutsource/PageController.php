<?php

namespace App\Http\Controllers\AdminOutsource;

use App\Http\Controllers\Controller;

/**
 * AdminOutsource\PageController
 *
 * Hanya bertugas serve halaman HTML (Blade shell).
 * Tidak ada logika bisnis — semua data dimuat via AJAX
 * dari App\Http\Controllers\Api\AdminOutsource\*.
 *
 * Scope fungsional:
 *   F07        — Kelola karyawan outsource
 *                (CRUD, aktif/nonaktif akun, reset password)
 *   F08–F09    — Input & upload planning kerja bulanan
 *   F10        — Validasi absensi harian (approve/reject)
 *   F11        — Riwayat & rekap absensi
 *   F04–F05 ↔  — Persetujuan izin + verifikasi dokumen karyawan
 *                (dikelola Admin Outsource untuk karyawannya sendiri)
 */
class PageController extends Controller
{
    /** Dashboard — stat cards, preview absensi, notifikasi, quick actions */
    public function dashboard()
    {
        return view('admin-outsource.dashboard');
    }

    /** F07 — Manajemen karyawan outsource (CRUD + akun + reset password) */
    public function karyawan()
    {
        return view('admin-outsource.karyawan');
    }

    /** F08–F09 — Planning kerja bulanan & upload jadwal */
    public function planning()
    {
        return view('admin-outsource.planning');
    }

    /** F10 — Validasi absensi harian (approve / reject) */
    public function validasiAbsensi()
    {
        return view('admin-outsource.validasi-absensi');
    }

    /** F11 — Riwayat & rekap absensi seluruh karyawan */
    public function riwayatAbsensi()
    {
        return view('admin-outsource.riwayat-absensi');
    }

    /** F04–F05 ↔ — Persetujuan izin + verifikasi dokumen karyawan */
    public function kelolaIzin()
    {
        return view('admin-outsource.kelola-izin');
    }
}