<?php

namespace App\Services;

use App\Models\KonfigurasiArea;

/**
 * GpsValidationService
 *
 * Memvalidasi apakah koordinat GPS karyawan berada dalam radius area
 * PT Ecogreen yang dikonfigurasi Super Admin (F19).
 *
 * Menggunakan formula Haversine untuk menghitung jarak antar dua
 * titik di permukaan bumi dalam satuan meter.
 */
class GpsValidationService
{
    private const RADIUS_BUMI_METER = 6_371_000;

    /**
     * Validasi apakah (lat, lng) berada dalam radius area aktif.
     *
     * @return array{valid: bool, jarak_meter: float, area: KonfigurasiArea|null}
     */
    public static function validasi(float $lat, float $lng): array
    {
        $area = KonfigurasiArea::where('is_aktif', true)->first();

        if (! $area) {
            // Tidak ada area aktif — tolak absensi
            return [
                'valid'        => false,
                'jarak_meter'  => 0.0,
                'area'         => null,
                'pesan'        => 'Konfigurasi area absensi belum diatur. Hubungi administrator.',
            ];
        }

        $jarak = static::hitungJarak(
            lat1: (float) $area->latitude_pusat,
            lng1: (float) $area->longitude_pusat,
            lat2: $lat,
            lng2: $lng,
        );

        $dalamRadius = $jarak <= $area->radius_meter;

        return [
            'valid'       => $dalamRadius,
            'jarak_meter' => round($jarak, 2),
            'radius_meter'=> $area->radius_meter,
            'area'        => $area,
            'pesan'       => $dalamRadius
                ? 'Lokasi valid dalam radius area.'
                : "Lokasi tidak valid. Anda berada {$jarak} m dari area (radius: {$area->radius_meter} m).",
        ];
    }

    /**
     * Hitung jarak antara dua koordinat menggunakan formula Haversine.
     * Mengembalikan jarak dalam satuan meter.
     *
     * @link https://en.wikipedia.org/wiki/Haversine_formula
     */
    public static function hitungJarak(
        float $lat1, float $lng1,
        float $lat2, float $lng2,
    ): float {
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::RADIUS_BUMI_METER * $c;
    }
}