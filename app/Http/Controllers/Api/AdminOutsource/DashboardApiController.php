<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\PengajuanIzin;
use App\Models\PlanningKerja;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardApiController — Admin Outsource
 *
 * Menyediakan data statistik dan ringkasan untuk dashboard Admin Outsource
 *
 * Endpoints:
 *   GET /api/admin/dashboard/stats       → stats()       — stat cards
 *   GET /api/admin/validasi-absensi      → sudah ada di ValidasiAbsensiApiController
 *   GET /api/admin/notifikasi            → notifikasi()  — notifikasi terbaru
 */
class DashboardApiController extends Controller
{
    /**
     * GET /api/admin/dashboard/stats
     *
     * Mengembalikan data untuk stat cards dashboard Admin Outsource
     */
    public function stats()
    {
        $user = Auth::user();

        // Ambil ID perusahaan dari profil admin outsource
        $idPerusahaan = $user->adminOutsourceProfile->id_perusahaan ?? null;

        if (!$idPerusahaan) {
            return response()->json([
                'status'  => false,
                'message' => 'Profil admin outsource tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Nama perusahaan
        $namaPerusahaan = $user->adminOutsourceProfile->perusahaan->nama_perusahaan ?? '—';

        // Total karyawan aktif
        $karyawanAktif = Karyawan::where('id_perusahaan', $idPerusahaan)
            ->where('status', 'aktif')
            ->count();

        // Total karyawan (termasuk non-aktif)
        $karyawanTotal = Karyawan::where('id_perusahaan', $idPerusahaan)->count();

        // Absensi pending validasi (status_validasi = 'menunggu')
        $absensiPending = Absensi::whereHas('karyawan', function ($q) use ($idPerusahaan) {
                $q->where('id_perusahaan', $idPerusahaan);
            })
            ->where('status_validasi', 'menunggu')
            ->whereDate('tanggal_absensi', today())
            ->count();

        // Izin pending yang actionable untuk Admin:
        // - jenis non-wajib dokumen: tetap dihitung
        // - jenis wajib dokumen: hanya jika status_dokumen sudah_upload + ada file dokumen
        $izinPending = PengajuanIzin::whereHas('karyawan', function ($q) use ($idPerusahaan) {
                $q->where('id_perusahaan', $idPerusahaan);
            })
            ->where('status', PengajuanIzin::STATUS_MENUNGGU)
            ->where(function ($q) {
                $q->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', false))
                    ->orWhere(function ($wajib) {
                        $wajib->whereHas('jenisIzin', fn($jenis) => $jenis->where('wajib_dokumen', true))
                            ->where('status_dokumen', PengajuanIzin::DOKUMEN_SUDAH_UPLOAD)
                            ->whereHas('dokumen');
                    });
            })
            ->count();

        // Planning kerja bulan ini
        $planningBulanIni = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', now()->month)
            ->where('periode_tahun', now()->year)
            ->latest('versi')
            ->first();

        $planningStatusLabel = $planningBulanIni ? 'Sudah Ada' : 'Belum Ada';
        $planningPeriode = $planningBulanIni 
            ? $planningBulanIni->periode_label
            : '—';

        // Planning 3 periode (bulan ini, bulan lalu, bulan depan)
        $planning1 = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', now()->month)
            ->where('periode_tahun', now()->year)
            ->latest('versi')
            ->first();

        $bulanLalu = now()->subMonth();
        $planning2 = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $bulanLalu->month)
            ->where('periode_tahun', $bulanLalu->year)
            ->latest('versi')
            ->first();

        $bulanDepan = now()->addMonth();
        $planning3 = PlanningKerja::where('id_perusahaan', $idPerusahaan)
            ->where('periode_bulan', $bulanDepan->month)
            ->where('periode_tahun', $bulanDepan->year)
            ->latest('versi')
            ->first();

        return response()->json([
            'status'  => true,
            'message' => 'Statistik dashboard berhasil dimuat.',
            'data'    => [
                'nama_perusahaan'      => $namaPerusahaan,
                'karyawan_aktif'       => $karyawanAktif,
                'karyawan_total'       => $karyawanTotal,
                'absensi_pending'      => $absensiPending,
                'izin_pending'         => $izinPending,
                'planning_status_label'=> $planningStatusLabel,
                'planning_periode'     => $planningPeriode,

                // Planning 3 periode
                'planning_1' => $planning1 ? [
                    'periode'         => $planning1->periode_label,
                    'versi'           => $planning1->versi,
                    'jumlah_karyawan' => $planning1->jadwalKerja()->distinct('id_karyawan')->count('id_karyawan'),
                    'status'          => $planning1->status ?? 'aktif',
                ] : null,

                'planning_2' => $planning2 ? [
                    'periode' => $planning2->periode_label,
                ] : null,

                'planning_3' => $planning3 ? [
                    'periode' => $planning3->periode_label,
                ] : [
                    'periode' => now()->addMonth()->locale('id')->isoFormat('MMMM YYYY'),
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/notifikasi
     *
     * Mengembalikan notifikasi terbaru untuk dashboard Admin Outsource
     */
    public function notifikasi(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 6);

        $notifikasi = Notifikasi::where('id_penerima', $user->id_pengguna)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($notif) {
                return [
                    'id'        => $notif->id_notifikasi,
                    'jenis'     => $notif->jenis ?? 'izin',
                    'judul'     => $notif->judul,
                    'isi'       => $notif->isi,
                    'is_dibaca' => $notif->is_dibaca,
                    'created_at'=> $notif->created_at->locale('id')->diffForHumans(),
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Notifikasi berhasil dimuat.',
            'data'    => $notifikasi,
        ]);
    }
}
