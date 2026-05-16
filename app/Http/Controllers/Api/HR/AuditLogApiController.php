<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Departemen;
use App\Models\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuditLogApiController — HR Ecogreen
 *
 * Memungkinkan HR melihat riwayat audit trail seluruh aksi approve/reject
 * yang dilakukan oleh Admin Outsource maupun User Departemen pada data absensi karyawan.
 *
 * Perbedaan dengan SuperAdmin\AuditLogApiController:
 *   - HR fokus pada aksi yang berkaitan dengan data absensi (approve/reject kehadiran, lembur, izin).
 *   - HR bisa filter berdasarkan nama karyawan dan departemen.
 *   - Tidak memiliki akses ke audit akun atau konfigurasi sistem.
 *   - Endpoint tambahan: ringkasan statistik aksi per periode.
 *
 * Endpoints:
 *   GET /api/hr/audit                    → index()      — daftar audit log (paginasi + filter)
 *   GET /api/hr/audit/{id}               → show()       — detail satu entri audit log
 *   GET /api/hr/audit/ringkasan          → ringkasan()  — statistik aksi per periode
 */
class AuditLogApiController extends Controller
{
    // Jenis data yang relevan untuk HR (fokus pada validasi absensi, bukan konfigurasi sistem)
    private const JENIS_RELEVAN_HR = [
        AuditLog::JENIS_ABSENSI,
        AuditLog::JENIS_LEMBUR,
        AuditLog::JENIS_IZIN,
    ];

    // ════════════════════════════════════════════════════════════════════════
    //  INDEX — Daftar audit log dengan filter
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/audit
     *
     * Menampilkan riwayat aksi approve/reject dari Admin Outsource dan User Departemen.
     * HR bisa filter berdasarkan periode, nama karyawan, departemen, pelaku, dan jenis aksi.
     *
     * Query params:
     *   - tanggal_dari    : filter tanggal mulai (Y-m-d)
     *   - tanggal_sampai  : filter tanggal akhir (Y-m-d)
     *   - aksi            : approve|reject|create|update (filter jenis aksi)
     *   - jenis_data      : absensi|lembur|izin (default: semua yang relevan HR)
     *   - role_pelaku     : admin_outsource|user_departemen|hr (filter berdasarkan role pelaku)
     *   - search          : cari berdasarkan nama pelaku atau catatan
     *   - per_page        : jumlah data per halaman (default: 25)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $query = AuditLog::with('pengguna:id_pengguna,nama_lengkap,role')
            // HR hanya melihat jenis data yang relevan (absensi, lembur, izin)
            ->whereIn('jenis_data', self::JENIS_RELEVAN_HR)
            ->latest('waktu_aksi');

        // Filter tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('waktu_aksi', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('waktu_aksi', '<=', $request->tanggal_sampai);
        }

        // Filter jenis aksi
        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        // Filter jenis data (override default — tapi tetap dalam scope HR)
        if ($request->filled('jenis_data')) {
            $jenis = $request->jenis_data;
            if (in_array($jenis, self::JENIS_RELEVAN_HR, true)) {
                $query->where('jenis_data', $jenis);
            }
        }

        // Filter role pelaku
        if ($request->filled('role_pelaku')) {
            $query->where('role_pelaku', $request->role_pelaku);
        }

        // Search nama pelaku atau catatan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pengguna', fn($q2) => $q2->where('nama_lengkap', 'like', "%{$search}%"))
                  ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($perPage);

        $logs->getCollection()->transform(fn($log) => $this->formatLog($log));

        return response()->json([
            'status'  => true,
            'message' => 'Audit log berhasil dimuat.',
            'data'    => $logs,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SHOW — Detail satu entri audit log
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/audit/{id}
     *
     * Detail lengkap satu entri audit log termasuk data sebelum dan sesudah perubahan.
     * Digunakan saat HR ingin melihat rincian satu aksi approve/reject tertentu.
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::with('pengguna:id_pengguna,nama_lengkap,role,email')
            ->whereIn('jenis_data', self::JENIS_RELEVAN_HR)
            ->find($id);

        if (! $log) {
            return response()->json([
                'status'  => false,
                'message' => 'Data audit log tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail audit log berhasil dimuat.',
            'data'    => $this->formatLog($log, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RINGKASAN — Statistik aksi per periode
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/audit/ringkasan
     *
     * Mengembalikan statistik agregat aksi approve/reject untuk periode tertentu.
     * Digunakan sebagai widget di halaman audit HR:
     *   - Total approve/reject per jenis data (absensi, lembur, izin)
     *   - Breakdown per role pelaku (Admin Outsource vs User Departemen)
     *   - Tren harian dalam periode
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Agregat per jenis data dan aksi
        $agregatJenis = AuditLog::whereIn('jenis_data', self::JENIS_RELEVAN_HR)
            ->whereIn('aksi', [AuditLog::AKSI_APPROVE, AuditLog::AKSI_REJECT])
            ->whereMonth('waktu_aksi', $bulan)
            ->whereYear('waktu_aksi', $tahun)
            ->selectRaw('jenis_data, aksi, COUNT(*) as jumlah')
            ->groupBy('jenis_data', 'aksi')
            ->get();

        // Susun ke format nested
        $statsJenis = [];
        foreach (self::JENIS_RELEVAN_HR as $jenis) {
            $approve = $agregatJenis->where('jenis_data', $jenis)->where('aksi', 'approve')->first()?->jumlah ?? 0;
            $reject  = $agregatJenis->where('jenis_data', $jenis)->where('aksi', 'reject')->first()?->jumlah ?? 0;

            $statsJenis[$jenis] = [
                'approve' => (int) $approve,
                'reject'  => (int) $reject,
                'total'   => (int) $approve + (int) $reject,
            ];
        }

        // Breakdown per role pelaku
        $agregatRole = AuditLog::whereIn('jenis_data', self::JENIS_RELEVAN_HR)
            ->whereIn('aksi', [AuditLog::AKSI_APPROVE, AuditLog::AKSI_REJECT])
            ->whereMonth('waktu_aksi', $bulan)
            ->whereYear('waktu_aksi', $tahun)
            ->selectRaw('role_pelaku, aksi, COUNT(*) as jumlah')
            ->groupBy('role_pelaku', 'aksi')
            ->get();

        $statsRole = [];
        foreach (['admin_outsource', 'user_departemen'] as $role) {
            $approve = $agregatRole->where('role_pelaku', $role)->where('aksi', 'approve')->first()?->jumlah ?? 0;
            $reject  = $agregatRole->where('role_pelaku', $role)->where('aksi', 'reject')->first()?->jumlah ?? 0;

            $statsRole[$role] = [
                'approve' => (int) $approve,
                'reject'  => (int) $reject,
                'total'   => (int) $approve + (int) $reject,
            ];
        }

        // Total keseluruhan
        $totalApprove = array_sum(array_column($statsJenis, 'approve'));
        $totalReject  = array_sum(array_column($statsJenis, 'reject'));

        // Aksi terbaru (5 entri)
        $aksiTerbaru = AuditLog::with('pengguna:id_pengguna,nama_lengkap,role')
            ->whereIn('jenis_data', self::JENIS_RELEVAN_HR)
            ->whereIn('aksi', [AuditLog::AKSI_APPROVE, AuditLog::AKSI_REJECT])
            ->whereMonth('waktu_aksi', $bulan)
            ->whereYear('waktu_aksi', $tahun)
            ->latest('waktu_aksi')
            ->limit(5)
            ->get()
            ->map(fn($log) => $this->formatLog($log));

        return response()->json([
            'status'  => true,
            'message' => 'Ringkasan audit log berhasil dimuat.',
            'data'    => [
                'periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                'total' => [
                    'approve' => $totalApprove,
                    'reject'  => $totalReject,
                    'semua'   => $totalApprove + $totalReject,
                ],
                'per_jenis_data' => $statsJenis,
                'per_role'       => $statsRole,
                'aksi_terbaru'   => $aksiTerbaru,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    private function formatLog(AuditLog $log, bool $detail = false): array
    {
        $base = [
            'id'             => $log->id_log,
            'waktu_aksi'     => $log->waktu_aksi?->format('d M Y, H:i'),
            'waktu_relative' => $log->waktu_aksi?->locale('id')->diffForHumans(),
            'pengguna_nama'  => $log->pengguna?->nama_lengkap ?? 'Sistem',
            'pengguna_id'    => $log->pengguna?->id_pengguna,
            'role_pelaku'    => $log->role_pelaku,
            'role_label'     => $this->getRoleLabel($log->role_pelaku),
            'aksi'           => $log->aksi,
            'aksi_label'     => $this->getAksiLabel($log->aksi),
            'jenis_data'     => $log->jenis_data,
            'jenis_label'    => $this->getJenisLabel($log->jenis_data),
            'id_referensi'   => $log->id_referensi,
            'catatan'        => $log->catatan ?? '—',
            'badge_class'    => $this->getBadgeClass($log->aksi),
            'has_changes'    => ! empty($log->data_sebelum) || ! empty($log->data_sesudah),
        ];

        if ($detail) {
            $base['waktu_aksi_lengkap'] = $log->waktu_aksi?->format('d F Y, H:i:s');
            $base['ip_address']         = $log->ip_address;
            $base['data_sebelum']       = $log->data_sebelum;
            $base['data_sesudah']       = $log->data_sesudah;
            $base['pengguna_email']     = $log->pengguna?->email;
        }

        return $base;
    }

    private function getBadgeClass(string $aksi): string
    {
        return match ($aksi) {
            'approve'    => 'success',
            'reject'     => 'danger',
            'create'     => 'info',
            'update'     => 'warning',
            'deactivate' => 'danger',
            'upload'     => 'info',
            default      => 'neutral',
        };
    }

    private function getAksiLabel(string $aksi): string
    {
        return match ($aksi) {
            'approve'    => 'Menyetujui',
            'reject'     => 'Menolak',
            'create'     => 'Membuat',
            'update'     => 'Mengubah',
            'deactivate' => 'Menonaktifkan',
            'upload'     => 'Mengunggah',
            default      => ucfirst($aksi),
        };
    }

    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'super_admin'     => 'Super Admin',
            'hr'              => 'HR',
            'user_departemen' => 'User Departemen',
            'admin_outsource' => 'Admin Outsource',
            'karyawan'        => 'Karyawan',
            'sistem'          => 'Sistem',
            default           => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    private function getJenisLabel(string $jenis): string
    {
        return match ($jenis) {
            'absensi'     => 'Absensi',
            'lembur'      => 'Lembur',
            'izin'        => 'Izin',
            'planning'    => 'Planning Kerja',
            'akun'        => 'Akun',
            'master_data' => 'Master Data',
            'konfigurasi' => 'Konfigurasi',
            'auth'        => 'Autentikasi',
            default       => ucfirst(str_replace('_', ' ', $jenis)),
        };
    }
}
