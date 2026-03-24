<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Karyawan\CheckInRequest;
use App\Http\Requests\Karyawan\CheckOutRequest;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Services\AbsensiService;
use App\Services\GpsValidationService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;

/**
 * AbsensiApiController — F01
 *
 * Menangani proses check-in dan check-out berbasis GPS untuk karyawan.
 *
 * Alur check-in:
 *   1. Validasi request (koordinat GPS wajib ada)
 *   2. Validasi lokasi via GpsValidationService (Haversine)
 *   3. Proses check-in via AbsensiService (hitung menit telat, simpan record)
 *   4. Kirim notifikasi ke Admin Outsource
 *
 * Alur check-out:
 *   1. Validasi request (koordinat GPS wajib ada)
 *   2. Validasi lokasi via GpsValidationService
 *   3. Proses check-out via AbsensiService (hitung menit kerja, kelebihan)
 *   4. Jika ada kelebihan → notifikasi Pending Lembur ke karyawan
 *
 * Endpoints:
 *   POST /api/karyawan/check-in   → checkIn()
 *   POST /api/karyawan/check-out  → checkOut()
 */
class AbsensiApiController extends Controller
{
    public function __construct(
        private readonly AbsensiService $absensiService,
    ) {}

    // ── CHECK-IN ──────────────────────────────────────────────────────────────

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        /** @var \App\Models\Pengguna $pengguna */
        $pengguna = auth()->user();
        $karyawan = $pengguna->karyawan;

        if (! $karyawan || $karyawan->status === 'nonaktif') {
            return response()->json([
                'status'  => false,
                'message' => 'Akun karyawan tidak ditemukan atau tidak aktif.',
                'data'    => null,
            ], 403);
        }

        // 1. Validasi GPS
        $gps = GpsValidationService::validasi(
            lat: (float) $request->latitude,
            lng: (float) $request->longitude,
        );

        if (! $gps['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $gps['pesan'],
                'data'    => [
                    'jarak_meter'  => $gps['jarak_meter'],
                    'radius_meter' => $gps['radius_meter'],
                ],
            ], 422);
        }

        // 2. Proses check-in
        try {
            $hasil = $this->absensiService->checkIn(
                karyawan:     $karyawan,
                lat:          (float) $request->latitude,
                lng:          (float) $request->longitude,
                lokasiValid:  true,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }

        // 3. Notifikasi ke Admin Outsource perusahaan ini
        $adminPengguna = $karyawan->perusahaan
            ->adminProfiles()
            ->with('pengguna:id_pengguna')
            ->get()
            ->pluck('pengguna.id_pengguna');

        foreach ($adminPengguna as $idAdmin) {
            NotifikasiService::absensiBaruKeAdmin(
                idAdmin:       $idAdmin,
                namaKaryawan:  $karyawan->nama_lengkap,
                tanggal:       now()->format('d M Y'),
                idAbsensi:     $hasil['absensi']->id_absensi,
            );
        }

        $absensi = $hasil['absensi'];

        return response()->json([
            'status'  => true,
            'message' => 'Check-in berhasil dicatat.' . ($hasil['menit_telat'] > 0
                ? " Anda terlambat {$hasil['menit_telat']} menit."
                : ''),
            'data'    => [
                'id_absensi'     => $absensi->id_absensi,
                'waktu_check_in' => $absensi->waktu_check_in->format('H:i'),
                'tanggal'        => $absensi->tanggal_absensi->format('d M Y'),
                'menit_telat'    => $hasil['menit_telat'],
                'jarak_meter'    => $gps['jarak_meter'],
            ],
        ], 201);
    }

    // ── CHECK-OUT ─────────────────────────────────────────────────────────────

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        /** @var \App\Models\Pengguna $pengguna */
        $pengguna = auth()->user();
        $karyawan = $pengguna->karyawan;

        if (! $karyawan || $karyawan->status === 'nonaktif') {
            return response()->json([
                'status'  => false,
                'message' => 'Akun karyawan tidak ditemukan atau tidak aktif.',
                'data'    => null,
            ], 403);
        }

        // 1. Validasi GPS
        $gps = GpsValidationService::validasi(
            lat: (float) $request->latitude,
            lng: (float) $request->longitude,
        );

        if (! $gps['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $gps['pesan'],
                'data'    => [
                    'jarak_meter'  => $gps['jarak_meter'],
                    'radius_meter' => $gps['radius_meter'],
                ],
            ], 422);
        }

        // 2. Proses check-out
        try {
            $hasil = $this->absensiService->checkOut(
                karyawan:    $karyawan,
                lat:         (float) $request->latitude,
                lng:         (float) $request->longitude,
                lokasiValid: true,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }

        $absensi = $hasil['absensi'];

        // 3. Susun pesan dan notifikasi Pending Lembur
        $pesan = 'Check-out berhasil dicatat.';
        $info  = [
            'id_absensi'         => $absensi->id_absensi,
            'waktu_check_out'    => $absensi->waktu_check_out->format('H:i'),
            'menit_kerja_normal' => $absensi->menit_kerja_normal,
            'menit_telat'        => $absensi->menit_telat,
            'menit_pulang_cepat' => $absensi->menit_pulang_cepat,
            'pending_lembur'     => $hasil['pending_lembur'],
            'menit_kelebihan'    => $hasil['menit_kelebihan'],
            'batas_lembur'       => $hasil['batas_lembur'],
        ];

        if ($hasil['pending_lembur']) {
            $pesan .= " Anda memiliki {$hasil['menit_kelebihan']} menit kelebihan waktu kerja."
                    . " Ajukan form lembur paling lambat {$hasil['batas_lembur']} (H+1).";
        } elseif ($absensi->menit_pulang_cepat > 0) {
            $pesan .= " Anda pulang {$absensi->menit_pulang_cepat} menit lebih awal dari jadwal.";
        }

        return response()->json([
            'status'  => true,
            'message' => $pesan,
            'data'    => $info,
        ]);
    }
}