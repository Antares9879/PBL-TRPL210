<?php

namespace App\Http\Controllers\UserDepartemen;

use App\Http\Controllers\Controller;

/**
 * UserDepartemen\PageController
 *
 * Hanya bertugas serve halaman HTML (Blade shell).
 * Tidak ada logika bisnis di sini — semua data dimuat via AJAX
 * dari App\Http\Controllers\Api\UserDepartemen\*.
 *
 * Scope fungsional:
 *   F12       — Validasi pengajuan lembur karyawan outsource di departemennya
 *   Dashboard — Monitoring kehadiran karyawan di departemennya (read-only)
 *   Notifikasi— Halaman notifikasi in-app
 */
class PageController extends Controller
{
    /**
     * Dashboard — ringkasan kehadiran hari ini + stat bulan + badge lembur menunggu.
     */
    public function dashboard()
    {
        return view('user-departemen.dashboard');
    }

    /**
     * F12 — Halaman validasi pengajuan lembur.
     * Data dimuat via AJAX dari ValidasiLemburApiController.
     */
    public function validasiLembur()
    {
        return view('user-departemen.validasi-lembur');
    }

    /**
     * Monitoring absensi harian — read-only, scope ke departemen sendiri.
     * Data dimuat via AJAX dari DashboardApiController.
     */
    public function monitoringAbsensi()
    {
        return view('user-departemen.monitoring-absensi');
    }

    /**
     * Halaman notifikasi in-app.
     * Data dimuat via AJAX dari NotifikasiApiController.
     */
    public function notifikasi()
    {
        return view('user-departemen.notifikasi');
    }
}