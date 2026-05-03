<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreAkunRequest;
use App\Http\Requests\SuperAdmin\UpdateAkunRequest;
use App\Http\Requests\SuperAdmin\ResetPasswordAkunRequest;
use App\Models\Pengguna;
use App\Models\AdminOutsourceProfile;
use App\Models\UserDepartemenProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * AkunApiController — F17
 *
 * Mengelola akun seluruh pengguna kecuali karyawan (dikelola Admin Outsource).
 *
 * Endpoints:
 *   GET    /api/super-admin/akun              → index()
 *   POST   /api/super-admin/akun              → store()
 *   GET    /api/super-admin/akun/{id}         → show()
 *   PUT    /api/super-admin/akun/{id}         → update()
 *   DELETE /api/super-admin/akun/{id}         → destroy()
 *   PUT    /api/super-admin/akun/{id}/reset-password → resetPassword()
 */
class AkunApiController extends Controller
{
    /**
     * Daftar semua akun kecuali karyawan, dengan paginasi.
     * Bisa difilter by role dan status via query string.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pengguna::query()
            ->where('role', '!=', Pengguna::ROLE_KARYAWAN)
            ->with([
                'adminOutsourceProfile.perusahaan:id_perusahaan,nama_perusahaan',
                'userDepartemenProfile.departemen:id_departemen,nama_departemen',
            ]);

        // Filter opsional
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->orderBy('role')
            ->orderBy('nama_lengkap')
            ->paginate(20);

        // Transform: jangan expose password_hash
        $data->getCollection()->transform(fn($p) => $this->formatPengguna($p));

        return response()->json([
            'status'  => true,
            'message' => 'Data akun berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    /**
     * Buat akun baru sekaligus profil role-specific dalam satu transaction.
     */
    public function store(StoreAkunRequest $request): JsonResponse
    {
        try {
            $pengguna = DB::transaction(function () use ($request) {

                // 1. Buat akun pengguna
                $pengguna = Pengguna::create([
                    'nama_lengkap'  => $request->nama_lengkap,
                    'email'         => $request->email,
                    'password_hash' => Hash::make($request->password),
                    'role'          => $request->role,
                    'status'        => Pengguna::STATUS_AKTIF,
                ]);

                // 2. Buat profil role-specific jika diperlukan
                match ($request->role) {
                    Pengguna::ROLE_ADMIN_OUTSOURCE => AdminOutsourceProfile::create([
                        'id_pengguna'   => $pengguna->id_pengguna,
                        'id_perusahaan' => $request->id_perusahaan,
                    ]),
                    Pengguna::ROLE_USER_DEPARTEMEN => UserDepartemenProfile::create([
                        'id_pengguna'   => $pengguna->id_pengguna,
                        'id_departemen' => $request->id_departemen,
                    ]),
                    default => null,
                };

                return $pengguna;
            });

            // Load relasi setelah transaction
            $pengguna->load([
                'adminOutsourceProfile.perusahaan:id_perusahaan,nama_perusahaan',
                'userDepartemenProfile.departemen:id_departemen,nama_departemen',
            ]);

            Log::info('Akun baru dibuat', [
                'id_pengguna'  => $pengguna->id_pengguna,
                'role'         => $pengguna->role,
                'dibuat_oleh'  => Auth::id(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Akun {$pengguna->nama_lengkap} berhasil dibuat.",
                'data'    => $this->formatPengguna($pengguna),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal membuat akun', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal membuat akun. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Detail satu akun.
     */
    public function show(int $akun): JsonResponse
    {
        $pengguna = $this->findPengguna($akun);
        if (! $pengguna) {
            return $this->notFound();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail akun berhasil dimuat.',
            'data'    => $this->formatPengguna($pengguna),
        ]);
    }

    /**
     * Update data akun + profil role-specific.
     * Jika role berubah, profil lama dihapus dan profil baru dibuat.
     */
    public function update(UpdateAkunRequest $request, int $akun): JsonResponse
    {
        $pengguna = $this->findPengguna($akun);
        if (! $pengguna) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($request, $pengguna) {

                $roleSebelumnya = $pengguna->role;
                $roleBaru       = $request->role;

                // Update data dasar
                $pengguna->update([
                    'nama_lengkap' => $request->nama_lengkap,
                    'email'        => $request->email,
                    'role'         => $roleBaru,
                    'status'       => $request->status,
                ]);

                // Jika role berubah, hapus profil lama
                if ($roleSebelumnya !== $roleBaru) {
                    match ($roleSebelumnya) {
                        Pengguna::ROLE_ADMIN_OUTSOURCE => $pengguna->adminOutsourceProfile?->delete(),
                        Pengguna::ROLE_USER_DEPARTEMEN => $pengguna->userDepartemenProfile?->delete(),
                        default                        => null,
                    };
                }

                // Upsert profil baru
                match ($roleBaru) {
                    Pengguna::ROLE_ADMIN_OUTSOURCE => AdminOutsourceProfile::updateOrCreate(
                        ['id_pengguna'   => $pengguna->id_pengguna],
                        ['id_perusahaan' => $request->id_perusahaan]
                    ),
                    Pengguna::ROLE_USER_DEPARTEMEN => UserDepartemenProfile::updateOrCreate(
                        ['id_pengguna'   => $pengguna->id_pengguna],
                        ['id_departemen' => $request->id_departemen]
                    ),
                    default => null,
                };
            });

            $pengguna->refresh()->load([
                'adminOutsourceProfile.perusahaan:id_perusahaan,nama_perusahaan',
                'userDepartemenProfile.departemen:id_departemen,nama_departemen',
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Akun {$pengguna->nama_lengkap} berhasil diperbarui.",
                'data'    => $this->formatPengguna($pengguna),
            ]);

        } catch (\Throwable $e) {
            Log::error('Gagal update akun', ['id' => $akun, 'error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui akun.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Hapus akun beserta profil (CASCADE sudah di-handle FK database).
     * Guard: tidak bisa hapus akun sendiri.
     */
    public function destroy(int $akun): JsonResponse
    {
        $pengguna = $this->findPengguna($akun);
        if (! $pengguna) {
            return $this->notFound();
        }

        // Guard: Super Admin tidak bisa hapus akunnya sendiri
        if ($pengguna->id_pengguna === Auth::id()) {
            return response()->json([
                'status'  => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
                'data'    => null,
            ], 403);
        }

        try {
            $nama = $pengguna->nama_lengkap;
            $pengguna->delete(); // FK CASCADE di DB akan hapus profil otomatis

            return response()->json([
                'status'  => true,
                'message' => "Akun {$nama} berhasil dihapus.",
                'data'    => null,
            ]);

        } catch (\Throwable $e) {
            Log::error('Gagal hapus akun', ['id' => $akun, 'error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Akun tidak dapat dihapus karena masih memiliki data terkait.',
                'data'    => null,
            ], 409);
        }
    }

    /**
     * Reset password akun oleh Super Admin.
     */
    public function resetPassword(ResetPasswordAkunRequest $request, int $akun): JsonResponse
    {
        $pengguna = $this->findPengguna($akun);
        if (! $pengguna) {
            return $this->notFound();
        }

        $pengguna->update([
            'password_hash' => Hash::make($request->password),
        ]);

        Log::info('Password di-reset', [
            'id_pengguna' => $pengguna->id_pengguna,
            'di_reset_oleh' => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Password akun {$pengguna->nama_lengkap} berhasil di-reset.",
            'data'    => null,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function findPengguna(int $id): ?Pengguna
    {
        return Pengguna::where('id_pengguna', $id)
            ->where('role', '!=', Pengguna::ROLE_KARYAWAN)
            ->with([
                'adminOutsourceProfile.perusahaan:id_perusahaan,nama_perusahaan',
                'userDepartemenProfile.departemen:id_departemen,nama_departemen',
            ])
            ->first();
    }

    private function formatPengguna(Pengguna $p): array
    {
        return [
            'id_pengguna'  => $p->id_pengguna,
            'nama_lengkap' => $p->nama_lengkap,
            'email'        => $p->email,
            'role'         => $p->role,
            'status'       => $p->status,
            'last_login'   => $p->last_login?->toDateTimeString(),
            'created_at'   => $p->created_at->toDateTimeString(),
            // Data profil role-specific
            'profil'       => match ($p->role) {
                Pengguna::ROLE_ADMIN_OUTSOURCE => $p->adminOutsourceProfile ? [
                    'id_perusahaan'   => $p->adminOutsourceProfile->id_perusahaan,
                    'nama_perusahaan' => $p->adminOutsourceProfile->perusahaan?->nama_perusahaan,
                ] : null,
                Pengguna::ROLE_USER_DEPARTEMEN => $p->userDepartemenProfile ? [
                    'id_departemen'   => $p->userDepartemenProfile->id_departemen,
                    'nama_departemen' => $p->userDepartemenProfile->departemen?->nama_departemen,
                ] : null,
                default => null,
            },
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Akun tidak ditemukan.',
            'data'    => null,
        ], 404);
    }
}
