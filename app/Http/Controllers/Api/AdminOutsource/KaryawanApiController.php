<?php

namespace App\Http\Controllers\Api\AdminOutsource;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOutsource\StoreKaryawanRequest;
use App\Http\Requests\AdminOutsource\UpdateKaryawanRequest;
use App\Http\Requests\AdminOutsource\ResetPasswordKaryawanRequest;
use App\Models\Karyawan;
use App\Models\Pengguna;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * KaryawanApiController — F07
 *
 * Mengelola karyawan outsource di bawah naungan Admin Outsource yang sedang login.
 * Mengikuti pola AkunApiController Super Admin: CRUD lengkap + aktif/nonaktif + reset password.
 *
 * Perbedaan scope dari Super Admin:
 *   - Admin Outsource HANYA bisa mengelola karyawan milik perusahaannya sendiri.
 *   - Admin Outsource TIDAK bisa mengubah id_perusahaan karyawan.
 *   - id_perusahaan selalu diambil otomatis dari profil Admin yang login.
 *
 * Endpoints:
 *   GET    /api/admin/karyawan                   → index()
 *   POST   /api/admin/karyawan                   → store()
 *   GET    /api/admin/karyawan/{id}              → show()
 *   PUT    /api/admin/karyawan/{id}              → update()
 *   DELETE /api/admin/karyawan/{id}              → destroy()  (nonaktifkan)
 *   PUT    /api/admin/karyawan/{id}/aktifkan     → aktifkan()
 *   PUT    /api/admin/karyawan/{id}/reset-password → resetPassword()
 */
class KaryawanApiController extends Controller
{
    // ── INDEX ─────────────────────────────────────────────────────────────────

    /**
     * Daftar karyawan perusahaan Admin yang login, dengan paginasi.
     * Filter: status, search (nama / NIK / nomor_karyawan).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Karyawan::with([
            'departemen:id_departemen,nama_departemen,kode_departemen',
            'pengguna:id_pengguna,email,status,last_login',
        ])
        ->where('id_perusahaan', $this->getIdPerusahaan());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('id_departemen')) {
            $query->where('id_departemen', $request->id_departemen);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap',    'like', "%{$search}%")
                  ->orWhere('nik',           'like', "%{$search}%")
                  ->orWhere('nomor_karyawan','like', "%{$search}%");
            });
        }

        $data = $query
            ->orderBy('nama_lengkap')
            ->paginate(20);

        $data->getCollection()->transform(fn($k) => $this->formatKaryawan($k));

        return response()->json([
            'status'  => true,
            'message' => 'Data karyawan berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────────────────────

    /**
     * Daftarkan karyawan baru sekaligus buat akun pengguna dalam satu transaction.
     *
     * Flow (mengikuti pola store() AkunApiController Super Admin):
     *   1. Buat akun pengguna (tabel pengguna, role = karyawan)
     *   2. Buat profil karyawan (tabel karyawan, 1:1 dengan pengguna)
     *   3. Catat audit log
     *   4. Return data karyawan yang baru dibuat
     */
    public function store(StoreKaryawanRequest $request): JsonResponse
    {
        $admin        = auth()->user();
        $idPerusahaan = $this->getIdPerusahaan();

        try {
            $karyawan = DB::transaction(function () use ($request, $idPerusahaan, $admin) {

                // 1. Buat akun pengguna
                $pengguna = Pengguna::create([
                    'nama_lengkap'  => $request->nama_lengkap,
                    'email'         => $request->email,
                    'password_hash' => Hash::make($request->password),
                    'role'          => Pengguna::ROLE_KARYAWAN,
                    'status'        => Pengguna::STATUS_AKTIF,
                ]);

                // 2. Buat profil karyawan — id_perusahaan otomatis dari Admin login
                $karyawan = Karyawan::create([
                    'id_pengguna'       => $pengguna->id_pengguna,
                    'nik'               => $request->nik,
                    'nomor_karyawan'    => $request->nomor_karyawan,
                    'nama_lengkap'      => $request->nama_lengkap,
                    'posisi'            => $request->posisi,
                    'id_perusahaan'     => $idPerusahaan,
                    'id_departemen'     => $request->id_departemen,
                    'tanggal_bergabung' => $request->tanggal_bergabung,
                    'status'            => 'aktif',
                    'created_by'        => $admin->id_pengguna,
                ]);

                return $karyawan;
            });

            // 3. Audit log
            AuditLogService::catat(
                pengguna:    $admin,
                jenis:       AuditLog::JENIS_AKUN,
                idReferensi: $karyawan->id_karyawan,
                aksi:        AuditLog::AKSI_CREATE,
                catatan:     "Karyawan baru didaftarkan: {$karyawan->nama_lengkap} (NIK: {$karyawan->nik})",
            );

            $karyawan->load([
                'departemen:id_departemen,nama_departemen,kode_departemen',
                'pengguna:id_pengguna,email,status',
            ]);

            Log::info('Karyawan baru didaftarkan', [
                'id_karyawan' => $karyawan->id_karyawan,
                'dibuat_oleh' => $admin->id_pengguna,
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Karyawan {$karyawan->nama_lengkap} berhasil didaftarkan.",
                'data'    => $this->formatKaryawan($karyawan),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal mendaftarkan karyawan', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mendaftarkan karyawan. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    /**
     * Detail satu karyawan.
     * Scope ke perusahaan Admin yang login untuk mencegah akses lintas perusahaan.
     */
    public function show(int $karyawan): JsonResponse
    {
        $data = $this->findKaryawan($karyawan);

        if (! $data) {
            return $this->notFound();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail karyawan berhasil dimuat.',
            'data'    => $this->formatKaryawan($data),
        ]);
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    /**
     * Update data karyawan — profil + email akun.
     *
     * Yang bisa diubah: nama, email, NIK, nomor_karyawan, posisi, departemen,
     *                   tanggal bergabung, status.
     * Yang TIDAK bisa diubah via endpoint ini: password (gunakan reset-password),
     *                                          id_perusahaan (fixed ke perusahaan Admin).
     */
    public function update(UpdateKaryawanRequest $request, int $karyawan): JsonResponse
    {
        $data = $this->findKaryawan($karyawan);

        if (! $data) {
            return $this->notFound();
        }

        $sebelum = $this->formatKaryawan($data);

        try {
            DB::transaction(function () use ($request, $data) {

                // Sync email di tabel pengguna
                $data->pengguna->update([
                    'email'  => $request->email,
                    'status' => $request->status,   // sync status akun dengan status karyawan
                ]);

                // Update profil karyawan
                $data->update([
                    'nama_lengkap'      => $request->nama_lengkap,
                    'nik'               => $request->nik,
                    'nomor_karyawan'    => $request->nomor_karyawan,
                    'posisi'            => $request->posisi,
                    'id_departemen'     => $request->id_departemen,
                    'tanggal_bergabung' => $request->tanggal_bergabung,
                    'status'            => $request->status,
                ]);
            });

            $data->refresh()->load([
                'departemen:id_departemen,nama_departemen,kode_departemen',
                'pengguna:id_pengguna,email,status,last_login',
            ]);

            AuditLogService::catat(
                pengguna:    auth()->user(),
                jenis:       AuditLog::JENIS_AKUN,
                idReferensi: $data->id_karyawan,
                aksi:        AuditLog::AKSI_UPDATE,
                catatan:     "Data karyawan diperbarui: {$data->nama_lengkap}",
                sebelum:     $sebelum,
                sesudah:     $this->formatKaryawan($data),
            );

            return response()->json([
                'status'  => true,
                'message' => "Data karyawan {$data->nama_lengkap} berhasil diperbarui.",
                'data'    => $this->formatKaryawan($data),
            ]);

        } catch (\Throwable $e) {
            Log::error('Gagal update karyawan', ['id' => $karyawan, 'error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui data karyawan.',
                'data'    => null,
            ], 500);
        }
    }

    // ── DESTROY (nonaktifkan) ─────────────────────────────────────────────────

    /**
     * Nonaktifkan karyawan dan akunnya.
     *
     * Tidak melakukan hard-delete karena karyawan yang dinonaktifkan
     * masih memiliki histori absensi yang perlu dipertahankan.
     *
     * Guard: tidak bisa nonaktifkan diri sendiri.
     */
    public function destroy(int $karyawan): JsonResponse
    {
        $data = $this->findKaryawan($karyawan);

        if (! $data) {
            return $this->notFound();
        }

        if ($data->status === 'nonaktif') {
            return response()->json([
                'status'  => false,
                'message' => 'Karyawan sudah dalam status nonaktif.',
                'data'    => null,
            ], 422);
        }

        // Cek apakah karyawan masih punya jadwal aktif ke depan (informatif saja)
        $adaJadwalAktif = $data->jadwal()
            ->where('tanggal_kerja', '>=', today())
            ->exists();

        DB::transaction(function () use ($data) {
            $data->update(['status' => 'nonaktif']);
            $data->pengguna->update(['status' => Pengguna::STATUS_NONAKTIF]);
        });

        AuditLogService::catat(
            pengguna:    auth()->user(),
            jenis:       AuditLog::JENIS_AKUN,
            idReferensi: $data->id_karyawan,
            aksi:        AuditLog::AKSI_DEACTIVATE,
            catatan:     "Karyawan dinonaktifkan: {$data->nama_lengkap}",
        );

        $pesan = "Karyawan {$data->nama_lengkap} berhasil dinonaktifkan.";
        if ($adaJadwalAktif) {
            $pesan .= ' Catatan: masih terdapat jadwal kerja aktif ke depan untuk karyawan ini.';
        }

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => ['ada_jadwal_aktif' => $adaJadwalAktif],
        ]);
    }

    // ── AKTIFKAN (re-aktifkan akun yang nonaktif) ─────────────────────────────

    /**
     * Aktifkan kembali karyawan yang sebelumnya dinonaktifkan.
     * Endpoint terpisah untuk mencegah kesalahan: aktifkan vs nonaktifkan
     * adalah aksi berbeda yang tidak bisa salah eksekusi.
     */
    public function aktifkan(int $karyawan): JsonResponse
    {
        $data = $this->findKaryawan($karyawan);

        if (! $data) {
            return $this->notFound();
        }

        if ($data->status === 'aktif') {
            return response()->json([
                'status'  => false,
                'message' => 'Karyawan sudah dalam status aktif.',
                'data'    => null,
            ], 422);
        }

        DB::transaction(function () use ($data) {
            $data->update(['status' => 'aktif']);
            $data->pengguna->update(['status' => Pengguna::STATUS_AKTIF]);
        });

        AuditLogService::catat(
            pengguna:    auth()->user(),
            jenis:       AuditLog::JENIS_AKUN,
            idReferensi: $data->id_karyawan,
            aksi:        AuditLog::AKSI_UPDATE,
            catatan:     "Karyawan diaktifkan kembali: {$data->nama_lengkap}",
        );

        return response()->json([
            'status'  => true,
            'message' => "Karyawan {$data->nama_lengkap} berhasil diaktifkan kembali.",
            'data'    => $this->formatKaryawan($data->refresh()->load([
                'departemen:id_departemen,nama_departemen,kode_departemen',
                'pengguna:id_pengguna,email,status',
            ])),
        ]);
    }

    // ── RESET PASSWORD ────────────────────────────────────────────────────────

    /**
     * Reset password akun karyawan oleh Admin Outsource.
     * Mengikuti pola resetPassword() di AkunApiController Super Admin.
     */
    public function resetPassword(ResetPasswordKaryawanRequest $request, int $karyawan): JsonResponse
    {
        $data = $this->findKaryawan($karyawan);

        if (! $data) {
            return $this->notFound();
        }

        $data->pengguna->update([
            'password_hash' => Hash::make($request->password),
        ]);

        AuditLogService::catat(
            pengguna:    auth()->user(),
            jenis:       AuditLog::JENIS_AKUN,
            idReferensi: $data->id_karyawan,
            aksi:        AuditLog::AKSI_UPDATE,
            catatan:     "Password karyawan di-reset oleh Admin Outsource: {$data->nama_lengkap}",
        );

        Log::info('Password karyawan di-reset', [
            'id_karyawan' => $data->id_karyawan,
            'di_reset_oleh' => auth()->id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Password karyawan {$data->nama_lengkap} berhasil di-reset.",
            'data'    => null,
        ]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    /**
     * Ambil id_perusahaan Admin yang sedang login dari profil Admin Outsource.
     * Digunakan di setiap method untuk scope data ke perusahaan Admin.
     */
    private function getIdPerusahaan(): int
    {
        return auth()->user()->adminOutsourceProfile->id_perusahaan;
    }

    /**
     * Cari karyawan berdasarkan id + validasi kepemilikan perusahaan.
     * Memastikan Admin Outsource tidak bisa mengakses karyawan perusahaan lain.
     */
    private function findKaryawan(int $id): ?Karyawan
    {
        return Karyawan::with([
            'departemen:id_departemen,nama_departemen,kode_departemen',
            'pengguna:id_pengguna,email,status,last_login',
        ])
        ->where('id_karyawan', $id)
        ->where('id_perusahaan', $this->getIdPerusahaan())
        ->first();
    }

    /**
     * Format output karyawan — konsisten, tidak expose password_hash.
     */
    private function formatKaryawan(Karyawan $k): array
    {
        return [
            'id_karyawan'       => $k->id_karyawan,
            'nama_lengkap'      => $k->nama_lengkap,
            'nik'               => $k->nik,
            'nomor_karyawan'    => $k->nomor_karyawan,
            'posisi'            => $k->posisi,
            'departemen'        => $k->departemen ? [
                'id_departemen'   => $k->departemen->id_departemen,
                'nama_departemen' => $k->departemen->nama_departemen,
                'kode_departemen' => $k->departemen->kode_departemen,
            ] : null,
            'tanggal_bergabung' => $k->tanggal_bergabung?->format('Y-m-d'),
            'status'            => $k->status,
            'akun'              => $k->pengguna ? [
                'email'      => $k->pengguna->email,
                'status'     => $k->pengguna->status,
                'last_login' => $k->pengguna->last_login?->toDateTimeString(),
            ] : null,
            'created_at'        => $k->created_at->toDateTimeString(),
            'updated_at'        => $k->updated_at->toDateTimeString(),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Karyawan tidak ditemukan.',
            'data'    => null,
        ], 404);
    }
}