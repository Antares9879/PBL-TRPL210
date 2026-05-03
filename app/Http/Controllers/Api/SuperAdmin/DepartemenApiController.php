<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreDepartemenRequest;
use App\Http\Requests\SuperAdmin\UpdateDepartemenRequest;
use App\Models\Departemen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * DepartemenApiController — F18
 *
 * Endpoints:
 *   GET    /api/super-admin/departemen        → index()
 *   POST   /api/super-admin/departemen        → store()
 *   GET    /api/super-admin/departemen/{id}   → show()
 *   PUT    /api/super-admin/departemen/{id}   → update()
 *   DELETE /api/super-admin/departemen/{id}   → destroy()
 */
class DepartemenApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Departemen::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_departemen', 'like', "%{$search}%")
                  ->orWhere('kode_departemen', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->withCount('karyawan')
            ->orderBy('nama_departemen')
            ->paginate(20);

        return response()->json([
            'status'  => true,
            'message' => 'Data departemen berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function store(StoreDepartemenRequest $request): JsonResponse
    {
        $departemen = Departemen::create([
            'nama_departemen' => $request->nama_departemen,
            'kode_departemen' => strtoupper($request->kode_departemen),
            'status'          => $request->status ?? Departemen::STATUS_AKTIF,
        ]);

        Log::info('Departemen baru dibuat', [
            'id_departemen' => $departemen->id_departemen,
            'dibuat_oleh'   => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Departemen {$departemen->nama_departemen} berhasil ditambahkan.",
            'data'    => $departemen,
        ], 201);
    }

    public function show(int $departemen): JsonResponse
    {
        $data = Departemen::withCount('karyawan')->find($departemen);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Departemen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail departemen berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function update(UpdateDepartemenRequest $request, int $departemen): JsonResponse
    {
        $data = Departemen::find($departemen);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Departemen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $data->update([
            'nama_departemen' => $request->nama_departemen,
            'kode_departemen' => strtoupper($request->kode_departemen),
            'status'          => $request->status,
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Departemen {$data->nama_departemen} berhasil diperbarui.",
            'data'    => $data->fresh(),
        ]);
    }

    public function destroy(int $departemen): JsonResponse
    {
        $data = Departemen::find($departemen);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Departemen tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        try {
            $nama = $data->nama_departemen;
            $data->delete();

            return response()->json([
                'status'  => true,
                'message' => "Departemen {$nama} berhasil dihapus.",
                'data'    => null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Departemen tidak dapat dihapus karena masih memiliki karyawan atau akun terkait.',
                'data'    => null,
            ], 409);
        }
    }
}
