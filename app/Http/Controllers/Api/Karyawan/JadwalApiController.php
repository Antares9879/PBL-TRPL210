<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\JadwalKerja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JadwalApiController — F02
 *
 * Menampilkan jadwal kerja karyawan yang sudah dibuat Admin Outsource.
 * Data di-scope ke karyawan yang sedang login.
 *
 * Endpoints:
 *   GET /api/karyawan/jadwal         → index() — daftar jadwal (filter bulan/tahun)
 *   GET /api/karyawan/jadwal/{id}    → show()  — detail satu hari
 */
class JadwalApiController extends Controller
{
    /**
     * Daftar jadwal kerja karyawan per bulan.
     * Default: bulan dan tahun saat ini.
     */
    public function index(Request $request): JsonResponse
    {
        $karyawan = auth()->user()->karyawan;

        if (! $karyawan) {
            return response()->json([
                'status'  => false,
                'message' => 'Data karyawan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        if ($bulan < 1 || $bulan > 12) {
            return response()->json([
                'status'  => false,
                'message' => 'Bulan harus antara 1 dan 12.',
                'data'    => null,
            ], 422);
        }

        $jadwal = JadwalKerja::with([
            'shift:id_shift,nama_shift,jam_masuk,jam_pulang,durasi_normal_menit',
            'absensi:id_absensi,id_jadwal,waktu_check_in,waktu_check_out,status_kehadiran,status_validasi,menit_telat,menit_kerja_normal',
        ])
        ->where('id_karyawan', $karyawan->id_karyawan)
        ->whereMonth('tanggal_kerja', $bulan)
        ->whereYear('tanggal_kerja', $tahun)
        // ── FIX: hanya ambil jadwal dari planning yang aktif ──────────────
        ->whereHas('planning', fn($q) => $q->where('status', 'aktif'))
        // ─────────────────────────────────────────────────────────────────
        ->orderBy('tanggal_kerja')
        ->get()
        ->map(fn($j) => $this->formatJadwal($j));

        return response()->json([
            'status'  => true,
            'message' => 'Data jadwal berhasil dimuat.',
            'data'    => [
                'periode' => ['bulan' => $bulan, 'tahun' => $tahun],
                'jadwal'  => $jadwal,
            ],
        ]);
    }

    /**
     * Detail jadwal satu hari tertentu.
     */
    public function show(int $id): JsonResponse
    {
        $karyawan = auth()->user()->karyawan;

        $jadwal = JadwalKerja::with([
            'shift:id_shift,nama_shift,jam_masuk,jam_pulang,durasi_normal_menit',
            'absensi',
        ])
        ->where('id_jadwal', $id)
        ->where('id_karyawan', $karyawan?->id_karyawan)
        ->first();

        if (! $jadwal) {
            return response()->json([
                'status'  => false,
                'message' => 'Jadwal tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail jadwal berhasil dimuat.',
            'data'    => $this->formatJadwal($jadwal),
        ]);
    }

    private function formatJadwal(JadwalKerja $j): array
    {
        return [
            'id_jadwal'      => $j->id_jadwal,
            'tanggal_kerja'  => $j->tanggal_kerja->format('Y-m-d'),
            'hari'           => $j->tanggal_kerja->translatedFormat('l'), // nama hari
            'is_hari_libur'  => $j->is_hari_libur,
            'shift'          => $j->shift ? [
                'nama_shift'          => $j->shift->nama_shift,
                'jam_masuk'           => substr($j->shift->jam_masuk, 0, 5),
                'jam_pulang'          => substr($j->shift->jam_pulang, 0, 5),
                'durasi_normal_menit' => $j->shift->durasi_normal_menit,
            ] : null,
            'absensi'        => $j->absensi ? [
                'waktu_check_in'     => $j->absensi->waktu_check_in?->format('H:i'),
                'waktu_check_out'    => $j->absensi->waktu_check_out?->format('H:i'),
                'status_kehadiran'   => $j->absensi->status_kehadiran,
                'status_validasi'    => $j->absensi->status_validasi,
                'menit_telat'        => $j->absensi->menit_telat,
                'menit_kerja_normal' => $j->absensi->menit_kerja_normal,
            ] : null,
        ];
    }
}