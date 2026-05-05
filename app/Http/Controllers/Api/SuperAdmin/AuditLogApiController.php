<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogApiController — Super Admin
 *
 * Menyediakan endpoint untuk halaman audit log lengkap dengan pagination dan filter
 *
 * Endpoints:
 *   GET /api/super-admin/audit-log  → index()  — list audit log dengan pagination & filter
 *   GET /api/super-admin/audit-log/{id}  → show()  — detail audit log
 */
class AuditLogApiController extends Controller
{
    /**
     * GET /api/super-admin/audit-log
     *
     * Mengembalikan daftar audit log dengan pagination dan filter
     *
     * Query params:
     *   - page: nomor halaman (default: 1)
     *   - per_page: jumlah data per halaman (default: 25)
     *   - tanggal_dari: filter tanggal mulai (format: Y-m-d)
     *   - tanggal_sampai: filter tanggal akhir (format: Y-m-d)
     *   - aksi: filter berdasarkan aksi (approve, reject, create, update, dll)
     *   - role: filter berdasarkan role pelaku
     *   - jenis_data: filter berdasarkan jenis data (absensi, lembur, izin, dll)
     *   - search: pencarian berdasarkan nama pengguna atau catatan
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        
        $query = AuditLog::with('pengguna:id_pengguna,nama_lengkap,role')
            ->latest('waktu_aksi');

        // Filter tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('waktu_aksi', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('waktu_aksi', '<=', $request->tanggal_sampai);
        }

        // Filter aksi
        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        // Filter role
        if ($request->filled('role')) {
            $query->where('role_pelaku', $request->role);
        }

        // Filter jenis data
        if ($request->filled('jenis_data')) {
            $query->where('jenis_data', $request->jenis_data);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('pengguna', function ($q2) use ($search) {
                    $q2->where('nama_lengkap', 'like', "%{$search}%");
                })
                ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($perPage);

        // Transform data untuk response
        $data = $logs->map(function ($log) {
            return $this->transformLog($log);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Audit log berhasil dimuat.',
            'data'    => $data,
            'pagination' => [
                'current_page'  => $logs->currentPage(),
                'last_page'     => $logs->lastPage(),
                'per_page'      => $logs->perPage(),
                'total'         => $logs->total(),
                'from'          => $logs->firstItem(),
                'to'            => $logs->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/super-admin/audit-log/{id}
     *
     * Mengembalikan detail audit log termasuk data sebelum dan sesudah
     */
    public function show($id)
    {
        $log = AuditLog::with('pengguna:id_pengguna,nama_lengkap,role,email')
            ->findOrFail($id);

        return response()->json([
            'status'  => true,
            'message' => 'Detail audit log berhasil dimuat.',
            'data'    => [
                'id'             => $log->id_log,
                'waktu_aksi'     => \Carbon\Carbon::parse($log->waktu_aksi)->locale('id')->format('d F Y, H:i:s'),
                'waktu_relative' => \Carbon\Carbon::parse($log->waktu_aksi)->locale('id')->diffForHumans(),
                'pengguna'       => [
                    'id'    => $log->pengguna->id_pengguna ?? null,
                    'nama'  => $log->pengguna->nama_lengkap ?? 'Sistem',
                    'email' => $log->pengguna->email ?? null,
                    'role'  => $log->pengguna->role ?? $log->role_pelaku,
                ],
                'role_pelaku'    => $log->role_pelaku,
                'role_label'     => $this->getRoleLabel($log->role_pelaku),
                'aksi'           => $log->aksi,
                'aksi_label'     => $this->getAksiLabel($log->aksi),
                'jenis_data'     => $log->jenis_data,
                'jenis_label'    => $this->getJenisLabel($log->jenis_data),
                'id_referensi'   => $log->id_referensi,
                'catatan'        => $log->catatan,
                'ip_address'     => $log->ip_address,
                'data_sebelum'   => $log->data_sebelum,
                'data_sesudah'   => $log->data_sesudah,
                'badge_class'    => $this->getBadgeClass($log->aksi),
            ],
        ]);
    }

    /**
     * Transform log untuk list view
     */
    private function transformLog($log)
    {
        return [
            'id'             => $log->id_log,
            'waktu_aksi'     => \Carbon\Carbon::parse($log->waktu_aksi)->locale('id')->format('d M Y, H:i'),
            'waktu_relative' => \Carbon\Carbon::parse($log->waktu_aksi)->locale('id')->diffForHumans(),
            'pengguna_nama'  => $log->pengguna->nama_lengkap ?? 'Sistem',
            'pengguna_id'    => $log->pengguna->id_pengguna ?? null,
            'role_pelaku'    => $log->role_pelaku,
            'role_label'     => $this->getRoleLabel($log->role_pelaku),
            'aksi'           => $log->aksi,
            'aksi_label'     => $this->getAksiLabel($log->aksi),
            'jenis_data'     => $log->jenis_data,
            'jenis_label'    => $this->getJenisLabel($log->jenis_data),
            'catatan'        => $log->catatan ?? '—',
            'badge_class'    => $this->getBadgeClass($log->aksi),
            'has_changes'    => !empty($log->data_sebelum) || !empty($log->data_sesudah),
        ];
    }

    /**
     * Get badge class berdasarkan aksi
     */
    private function getBadgeClass($aksi)
    {
        $badgeMap = [
            'login'       => 'info',
            'logout'      => 'neutral',
            'approve'     => 'success',
            'reject'      => 'danger',
            'create'      => 'info',
            'update'      => 'warning',
            'deactivate'  => 'danger',
            'activate'    => 'success',
            'upload'      => 'info',
        ];
        return $badgeMap[$aksi] ?? 'neutral';
    }

    /**
     * Get label aksi untuk display
     */
    private function getAksiLabel($aksi)
    {
        $aksiLabel = [
            'login'       => 'Login',
            'logout'      => 'Logout',
            'approve'     => 'Menyetujui',
            'reject'      => 'Menolak',
            'create'      => 'Membuat',
            'update'      => 'Mengubah',
            'deactivate'  => 'Menonaktifkan',
            'activate'    => 'Mengaktifkan',
            'upload'      => 'Mengunggah',
        ];
        return $aksiLabel[$aksi] ?? ucfirst($aksi);
    }

    /**
     * Get label role untuk display
     */
    private function getRoleLabel($role)
    {
        $roleLabel = [
            'super_admin'     => 'Super Admin',
            'admin_outsource' => 'Admin Outsource',
            'user_departemen' => 'User Departemen',
            'hr'              => 'HR',
            'karyawan'        => 'Karyawan',
            'sistem'          => 'Sistem',
        ];
        return $roleLabel[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Get label jenis data untuk display
     */
    private function getJenisLabel($jenis)
    {
        $jenisLabel = [
            'absensi'      => 'Absensi',
            'lembur'       => 'Lembur',
            'izin'         => 'Izin',
            'planning'     => 'Planning Kerja',
            'akun'         => 'Akun',
            'master_data'  => 'Master Data',
            'konfigurasi'  => 'Konfigurasi',
            'auth'         => 'Autentikasi',
        ];
        return $jenisLabel[$jenis] ?? ucfirst(str_replace('_', ' ', $jenis));
    }
}
