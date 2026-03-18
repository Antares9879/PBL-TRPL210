<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StorePerusahaanRequest;
use App\Http\Requests\SuperAdmin\UpdatePerusahaanRequest;
use App\Models\PerusahaanOutsource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PerusahaanApiController — F18
 *
 * Endpoints:
 *   GET    /api/super-admin/perusahaan        → index()
 *   POST   /api/super-admin/perusahaan        → store()
 *   GET    /api/super-admin/perusahaan/{id}   → show()
 *   PUT    /api/super-admin/perusahaan/{id}   → update()
 *   DELETE /api/super-admin/perusahaan/{id}   → destroy()
 */
class PerusahaanApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PerusahaanOutsource::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('nama_perusahaan', 'like', "%{$request->search}%");
        }

        $data = $query
            ->withCount('karyawan')  // jumlah karyawan aktif
            ->orderBy('nama_perusahaan')
            ->paginate(20);

        return response()->json([
            'status'  => true,
            'message' => 'Data perusahaan berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function store(StorePerusahaanRequest $request): JsonResponse
    {
        $perusahaan = PerusahaanOutsource::create([
            'nama_perusahaan' => $request->nama_perusahaan,
            'alamat'          => $request->alamat,
            'no_telepon'      => $request->no_telepon,
            'email'           => $request->email,
            'status'          => $request->status ?? PerusahaanOutsource::STATUS_AKTIF,
        ]);

        Log::info('Perusahaan baru dibuat', [
            'id_perusahaan' => $perusahaan->id_perusahaan,
            'dibuat_oleh'   => auth()->id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Perusahaan {$perusahaan->nama_perusahaan} berhasil ditambahkan.",
            'data'    => $perusahaan,
        ], 201);
    }

    public function show(int $perusahaan): JsonResponse
    {
        $data = PerusahaanOutsource::with([
            'adminProfiles.pengguna:id_pengguna,nama_lengkap,email,status',
        ])->find($perusahaan);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Perusahaan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail perusahaan berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function update(UpdatePerusahaanRequest $request, int $perusahaan): JsonResponse
    {
        $data = PerusahaanOutsource::find($perusahaan);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Perusahaan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $data->update($request->only([
            'nama_perusahaan', 'alamat', 'no_telepon', 'email', 'status',
        ]));

        return response()->json([
            'status'  => true,
            'message' => "Perusahaan {$data->nama_perusahaan} berhasil diperbarui.",
            'data'    => $data->fresh(),
        ]);
    }

    public function destroy(int $perusahaan): JsonResponse
    {
        $data = PerusahaanOutsource::find($perusahaan);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Perusahaan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        try {
            $nama = $data->nama_perusahaan;
            $data->delete();

            return response()->json([
                'status'  => true,
                'message' => "Perusahaan {$nama} berhasil dihapus.",
                'data'    => null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Perusahaan tidak dapat dihapus karena masih memiliki karyawan atau akun terkait.',
                'data'    => null,
            ], 409);
        }
    }
}
