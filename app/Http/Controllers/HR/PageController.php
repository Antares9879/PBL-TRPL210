<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * PageController — HR Ecogreen
 *
 * Controller untuk serve halaman-halaman HR (HTML shell).
 * Tidak ada logika bisnis di sini — hanya return Blade view.
 * Data dimuat via AJAX dari DashboardApiController, DokumenApiController, dll.
 *
 * Scope: F13–F16
 */
class PageController extends Controller
{
    /**
     * F13 — Dashboard Monitoring Keseluruhan
     */
    public function dashboard(): View
    {
        return view('hr.dashboard');
    }

    /**
     * F14 — Verifikasi Dokumen Izin
     */
    public function dokumen(): View
    {
        return view('hr.dokumen');
    }

    /**
     * F15 — Rekap Absensi Bulanan (Halaman A — Daftar Per Bulan)
     */
    public function rekap(): View
    {
        return view('hr.rekap');
    }

    /**
     * F15 — Rekap Absensi Bulanan (Halaman B — Detail Per Bulan)
     */
    public function rekapDetail(): View
    {
        return view('hr.rekap-detail');
    }

    /**
     * F16 — Audit Log Approval
     */
    public function audit(): View
    {
        return view('hr.audit');
    }

    /**
     * Halaman semua notifikasi HR
     */
    public function notifikasi(): View
    {
        return view('hr.notifikasi');
    }
}
