<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\KonfigurasiArea;
use Illuminate\Http\JsonResponse;

/**
 * AreaApiController
 *
 * Menyediakan data area absensi aktif untuk keperluan frontend karyawan.
 * Digunakan oleh absensi.js untuk menampilkan radius area di peta Leaflet
 * sebelum karyawan melakukan check-in / check-out.
 *
 * Endpoint:
 *   GET /api/karyawan/area-aktif  → index()
 */
class AreaApiController extends Controller
{
    /**
     * Kembalikan data area GPS aktif.
     * Hanya field yang diperlukan frontend — tidak expose diubah_oleh.
     */
    public function index(): JsonResponse
    {
        $area = KonfigurasiArea::where('is_aktif', true)
            ->first(['id_konfigurasi', 'nama_area', 'latitude_pusat', 'longitude_pusat', 'radius_meter']);

        if (! $area) {
            return response()->json([
                'status'  => false,
                'message' => 'Konfigurasi area belum diatur.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data area aktif berhasil dimuat.',
            'data'    => [
                'id_konfigurasi'  => $area->id_konfigurasi,
                'nama_area'       => $area->nama_area,
                'latitude_pusat'  => (float) $area->latitude_pusat,
                'longitude_pusat' => (float) $area->longitude_pusat,
                'radius_meter'    => (int)   $area->radius_meter,
            ],
        ]);
    }
}