<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreKonfigurasiAreaRequest;
use App\Http\Requests\SuperAdmin\UpdateKonfigurasiAreaRequest;
use App\Models\KonfigurasiArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;    

/**
 * KonfigurasiAreaApiController — F19
 *
 * Business rule utama: hanya satu area boleh is_aktif = true.
 * Saat area baru diset aktif, area lain otomatis dinonaktifkan.
 *
 * Endpoints:
 *   GET    /api/super-admin/konfigurasi-area        → index()
 *   POST   /api/super-admin/konfigurasi-area        → store()
 *   GET    /api/super-admin/konfigurasi-area/{id}   → show()
 *   PUT    /api/super-admin/konfigurasi-area/{id}   → update()
 *   DELETE /api/super-admin/konfigurasi-area/{id}   → destroy()
 */
class KonfigurasiAreaApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Tidak perlu paginasi — jumlah area sangat terbatas
        $data = KonfigurasiArea::with('diubahOleh:id_pengguna,nama_lengkap')
            ->orderByDesc('is_aktif')
            ->orderBy('nama_area')
            ->get()
            ->map(fn($area) => $this->formatArea($area));

        return response()->json([
            'status'  => true,
            'message' => 'Data konfigurasi area berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    public function store(StoreKonfigurasiAreaRequest $request): JsonResponse
    {
        try {
            $area = DB::transaction(function () use ($request) {

                $isAktif = $request->boolean('is_aktif', false);

                // Jika area baru langsung diset aktif, nonaktifkan semua area lain
                if ($isAktif) {
                    KonfigurasiArea::where('is_aktif', true)->update(['is_aktif' => false]);
                }

                return KonfigurasiArea::create([
                    'nama_area'       => $request->nama_area,
                    'latitude_pusat'  => $request->latitude_pusat,
                    'longitude_pusat' => $request->longitude_pusat,
                    'radius_meter'    => $request->radius_meter,
                    'is_aktif'        => $isAktif,
                    'diubah_oleh'     => Auth::id(),
                ]);
            });

            Log::info('Konfigurasi area baru dibuat', [
                'id_konfigurasi' => $area->id_konfigurasi,
                'dibuat_oleh'    => Auth::id(),
            ]);

            $area->load('diubahOleh:id_pengguna,nama_lengkap');

            return response()->json([
                'status'  => true,
                'message' => "Area {$area->nama_area} berhasil ditambahkan.",
                'data'    => $this->formatArea($area),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal membuat konfigurasi area', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menambahkan area. Silakan coba lagi.',
                'data'    => null,
            ], 500);
        }
    }

    public function show(int $konfigurasiArea): JsonResponse
    {
        $data = KonfigurasiArea::with('diubahOleh:id_pengguna,nama_lengkap')
            ->find($konfigurasiArea);

        if (! $data) {
            return $this->notFound();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail konfigurasi area berhasil dimuat.',
            'data'    => $this->formatArea($data),
        ]);
    }

    public function update(UpdateKonfigurasiAreaRequest $request, int $konfigurasiArea): JsonResponse
    {
        $area = KonfigurasiArea::find($konfigurasiArea);

        if (! $area) {
            return $this->notFound();
        }

        try {
            DB::transaction(function () use ($request, $area) {

                $isAktif = $request->boolean('is_aktif');

                // Jika diset aktif, nonaktifkan semua area lain kecuali diri sendiri
                if ($isAktif) {
                    KonfigurasiArea::where('is_aktif', true)
                        ->where('id_konfigurasi', '!=', $area->id_konfigurasi)
                        ->update(['is_aktif' => false]);
                }

                $area->update([
                    'nama_area'       => $request->nama_area,
                    'latitude_pusat'  => $request->latitude_pusat,
                    'longitude_pusat' => $request->longitude_pusat,
                    'radius_meter'    => $request->radius_meter,
                    'is_aktif'        => $isAktif,
                    'diubah_oleh'     => Auth::id(),
                ]);
            });

            $area->refresh()->load('diubahOleh:id_pengguna,nama_lengkap');

            return response()->json([
                'status'  => true,
                'message' => "Area {$area->nama_area} berhasil diperbarui.",
                'data'    => $this->formatArea($area),
            ]);

        } catch (\Throwable $e) {
            Log::error('Gagal update konfigurasi area', ['id' => $konfigurasiArea, 'error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui area.',
                'data'    => null,
            ], 500);
        }
    }

    public function destroy(int $konfigurasiArea): JsonResponse
    {
        $area = KonfigurasiArea::find($konfigurasiArea);

        if (! $area) {
            return $this->notFound();
        }

        // Guard: area aktif tidak boleh dihapus
        if ($area->is_aktif) {
            return response()->json([
                'status'  => false,
                'message' => 'Area yang sedang aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.',
                'data'    => null,
            ], 422);
        }

        $nama = $area->nama_area;
        $area->delete();

        return response()->json([
            'status'  => true,
            'message' => "Area {$nama} berhasil dihapus.",
            'data'    => null,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formatArea(KonfigurasiArea $area): array
    {
        return [
            'id_konfigurasi'  => $area->id_konfigurasi,
            'nama_area'       => $area->nama_area,
            'latitude_pusat'  => $area->latitude_pusat,
            'longitude_pusat' => $area->longitude_pusat,
            'radius_meter'    => $area->radius_meter,
            'is_aktif'        => $area->is_aktif,
            'diubah_oleh'     => $area->diubahOleh?->nama_lengkap,
            'updated_at'      => $area->updated_at->toDateTimeString(),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Konfigurasi area tidak ditemukan.',
            'data'    => null,
        ], 404);
    }
}
