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
     * F14 — Verifikasi Dokumen Izin (Daftar Per Bulan)
     */
    public function dokumen(): View
    {
        return view('hr.dokumen');
    }

    /**
     * F14 — Verifikasi Dokumen Izin (Detail Per Bulan)
     */
    public function dokumenDetail(): View
    {
        return view('hr.dokumen-detail');
    }

    /**
     * F15 — Rekap Absensi Bulanan
     */
    public function rekap(): View
    {
        return view('hr.rekap');
    }

    /**
     * F16 — Audit Log Approval
     */
    public function audit(): View
    {
        return view('hr.audit');
    }
}
