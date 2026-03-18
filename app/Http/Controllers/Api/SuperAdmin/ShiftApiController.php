<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreShiftRequest;
use App\Http\Requests\SuperAdmin\UpdateShiftRequest;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShiftApiController — F18
 *
 * Endpoints:
 *   GET    /api/super-admin/shift        → index()
 *   POST   /api/super-admin/shift        → store()
 *   GET    /api/super-admin/shift/{id}   → show()
 *   PUT    /api/super-admin/shift/{id}   → update()
 *   DELETE /api/super-admin/shift/{id}   → destroy()
 */
class ShiftApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shift::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Shift tidak perlu paginasi — jumlahnya terbatas
        $data = $query->orderBy('jam_masuk')->get();

        return response()->json([
            'status'  => true,
            'message' => 'Data shift berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = Shift::create([
            'nama_shift'          => $request->nama_shift,
            'jam_masuk'           => $request->jam_masuk . ':00', // pastikan format HH:MM:SS
            'jam_pulang'          => $request->jam_pulang . ':00',
            'durasi_normal_menit' => $request->durasi_normal_menit ?? Shift::DURASI_DEFAULT,
            'status'              => $request->status ?? Shift::STATUS_AKTIF,
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Shift {$shift->nama_shift} berhasil ditambahkan.",
            'data'    => $shift,
        ], 201);
    }

    public function show(int $shift): JsonResponse
    {
        $data = Shift::find($shift);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Shift tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail shift berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function update(UpdateShiftRequest $request, int $shift): JsonResponse
    {
        $data = Shift::find($shift);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Shift tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $data->update([
            'nama_shift'          => $request->nama_shift,
            'jam_masuk'           => $request->jam_masuk . ':00',
            'jam_pulang'          => $request->jam_pulang . ':00',
            'durasi_normal_menit' => $request->durasi_normal_menit,
            'status'              => $request->status,
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Shift {$data->nama_shift} berhasil diperbarui.",
            'data'    => $data->fresh(),
        ]);
    }

    public function destroy(int $shift): JsonResponse
    {
        $data = Shift::find($shift);

        if (! $data) {
            return response()->json([
                'status'  => false,
                'message' => 'Shift tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        try {
            $nama = $data->nama_shift;
            $data->delete();

            return response()->json([
                'status'  => true,
                'message' => "Shift {$nama} berhasil dihapus.",
                'data'    => null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Shift tidak dapat dihapus karena sudah digunakan di jadwal kerja.',
                'data'    => null,
            ], 409);
        }
    }
}
