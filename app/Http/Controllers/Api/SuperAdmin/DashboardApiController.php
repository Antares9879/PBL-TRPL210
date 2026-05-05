<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\PerusahaanOutsource;
use App\Models\Departemen;
use App\Models\KonfigurasiArea;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DashboardApiController — Super Admin
 *
 * Menyediakan data statistik dan ringkasan untuk dashboard Super Admin
 *
 * Endpoints:
 *   GET /api/super-admin/dashboard/stats      → stats()      — stat cards & distribusi role
 *   GET /api/super-admin/dashboard/audit-log  → auditLog()   — 10 entri audit log terbaru
 */
class DashboardApiController extends Controller
{
    /**
     * GET /api/super-admin/dashboard/stats
     *
     * Mengembalikan data untuk stat cards dan distribusi role pengguna
     */
    public function stats()
    {
        // Total pengguna
        $totalPengguna = Pengguna::count();

        // Pengguna baru bulan ini
        $penggunaBaru = Pengguna::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Total perusahaan outsource
        $totalPerusahaan = PerusahaanOutsource::count();
        $perusahaanAktif = PerusahaanOutsource::where('status', 'aktif')->count();

        // Total departemen
        $totalDepartemen = Departemen::count();
        $departemenAktif = Departemen::where('status', 'aktif')->count();

        // Konfigurasi area GPS terbaru
        $konfigArea = KonfigurasiArea::latest('updated_at')->first();
        $radiusMeter = $konfigArea ? $konfigArea->radius_meter : 0;
        $radiusUpdated = $konfigArea 
            ? $konfigArea->updated_at->locale('id')->diffForHumans() 
            : '—';

        // Distribusi role pengguna
        $roleDistribution = Pengguna::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        $countHr    = $roleDistribution['hr'] ?? 0;
        $countDept  = $roleDistribution['user_departemen'] ?? 0;
        $countAdmin = $roleDistribution['admin_outsource'] ?? 0;
        $countSuper = $roleDistribution['super_admin'] ?? 0;

        // Hitung persentase untuk bar chart
        $pctHr    = $totalPengguna > 0 ? round(($countHr / $totalPengguna) * 100, 1) : 0;
        $pctDept  = $totalPengguna > 0 ? round(($countDept / $totalPengguna) * 100, 1) : 0;
        $pctAdmin = $totalPengguna > 0 ? round(($countAdmin / $totalPengguna) * 100, 1) : 0;
        $pctSuper = $totalPengguna > 0 ? round(($countSuper / $totalPengguna) * 100, 1) : 0;

        return response()->json([
            'status'  => true,
            'message' => 'Statistik dashboard berhasil dimuat.',
            'data'    => [
                'total_pengguna'     => $totalPengguna,
                'pengguna_baru'      => $penggunaBaru,
                'total_perusahaan'   => $totalPerusahaan,
                'perusahaan_aktif'   => $perusahaanAktif,
                'total_departemen'   => $totalDepartemen,
                'departemen_aktif'   => $departemenAktif,
                'radius_meter'       => $radiusMeter,
                'radius_updated'     => $radiusUpdated,

                // Distribusi role
                'count_hr'    => $countHr,
                'count_dept'  => $countDept,
                'count_admin' => $countAdmin,
                'count_super' => $countSuper,
                'pct_hr'      => $pctHr,
                'pct_dept'    => $pctDept,
                'pct_admin'   => $pctAdmin,
                'pct_super'   => $pctSuper,
            ],
        ]);
    }

    /**
     * GET /api/super-admin/dashboard/audit-log
     *
     * Mengembalikan 10 entri audit log terbaru untuk ditampilkan di dashboard
     */
    public function auditLog(Request $request)
    {
        $limit = $request->input('limit', 10);

        $logs = AuditLog::with('pengguna:id_pengguna,nama_lengkap')
            ->latest('waktu_aksi')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                // Tentukan badge class berdasarkan aksi
                $badgeMap = [
                    'approve'     => 'success',
                    'reject'      => 'danger',
                    'create'      => 'info',
                    'update'      => 'warning',
                    'deactivate'  => 'danger',
                    'upload'      => 'info',
                ];
                $badgeClass = $badgeMap[$log->aksi] ?? 'neutral';

                // Format aksi untuk display
                $aksiLabel = [
                    'approve'     => 'Menyetujui',
                    'reject'      => 'Menolak',
                    'create'      => 'Membuat',
                    'update'      => 'Mengubah',
                    'deactivate'  => 'Menonaktifkan',
                    'upload'      => 'Mengunggah',
                ];

                return [
                    'id'             => $log->id_log,
                    'created_at'     => \Carbon\Carbon::parse($log->waktu_aksi)->locale('id')->format('d M Y, H:i'),
                    'pengguna_nama'  => $log->pengguna->nama_lengkap ?? 'Sistem',
                    'aksi'           => $aksiLabel[$log->aksi] ?? ucfirst($log->aksi),
                    'modul'          => ucfirst(str_replace('_', ' ', $log->jenis_data)),
                    'status'         => 'Berhasil',
                    'badge_class'    => $badgeClass,
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Audit log berhasil dimuat.',
            'data'    => $logs,
        ]);
    }
}
