<?php

namespace App\Http\Controllers\Karyawan;
use App\Http\Controllers\Controller;

/****
 * Karyawan\PageController
 *
 * Hanya bertugas serve halaman HTML (Blade shell).
 * Tidak ada logika bisnis — semua data dimuat via AJAX
 * dari App\Http\Controllers\Api\Karyawan\*.
 *
 * Scope fungsional:
 *   F01–F02    — Lihat jadwal kerja & absensi harian   
 *  F03        — Ajukan izin (dengan upload dokumen pendukung)
 *  F04–F05 ↔  — Cek status pengajuan izin + riwayat izin
 * F06        — Kelola akun (ubah password, dsb.)
 */

class PageController extends Controller
{
    /** Dashboard — jadwal kerja hari ini, absensi harian, notifikasi, quick actions */
    public function dashboard()
    {
        return view('karyawan.dashboard');
    }

    /** F01–F02 — Lihat jadwal kerja & absensi harian */
    public function jadwalKerja()
    {
        return view('karyawan.jadwal');
    }

    public function absensi()
    {
        return view('karyawan.absensi');
    }

    /** Halaman hub pengajuan — pilihan lembur atau izin */
    public function pengajuan()
    {
        return view('karyawan.pengajuan');
    }
    
    /** F03 — Ajukan lembur */
    public function ajukanLembur()
    {
        return view('karyawan.lembur');
    }

    /** F04–F05  —  pengajuan izin dan upload dokumen pendukung */
    public function ajukanIzin()
    {
        return view('karyawan.izin');
    }

    /** F06 - lihat absensi pribadi */
    public function lihatAbsensi()
    {
        return view('karyawan.riwayat');
    }

    /** Halaman semua notifikasi karyawan */
    public function notifikasi()
    {
        return view('karyawan.notifikasi');
    }

}


