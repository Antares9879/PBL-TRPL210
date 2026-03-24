<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\PengajuanIzin;
use App\Models\PengajuanLembur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RiwayatAbsensiApiController — F06
 *
 * Memberikan transparansi kepada karyawan untuk memantau riwayat
 * kehadiran dan status izin/lembur milik sendiri.
 *
 * Data yang ditampilkan per periode:
 *   - Tanggal, jam masuk/pulang, total menit kerja, menit telat, menit lembur
 *   - Status kehadiran dan status validasi per hari
 *   - Ringkasan: total hari hadir, total menit normal, total menit lembur resmi
 *
 * Endpoints:
 *   GET /api/karyawan/riwayat          → index()    — tabel rekap per periode
 *   GET /api/karyawan/riwayat/ringkasan → ringkasan() — agregasi bulan ini
 */
class RiwayatAbsensiApiController extends Controller
{
    /**
     * Rekap absensi harian karyawan per periode (bulan & tahun).
     * Default: bulan dan tahun saat ini.
     * Mendukung paginasi — wajib karena data tumbuh setiap hari.
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

        $absensi = Absensi::with([
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
            'pengajuanLembur:id_lembur,id_absensi,menit_lembur_resmi,status',
        ])
        ->where('id_karyawan', $karyawan->id_karyawan)
        ->whereMonth('tanggal_absensi', $bulan)
        ->whereYear('tanggal_absensi', $tahun)
        ->orderByDesc('tanggal_absensi')
        ->paginate(20);

        $absensi->getCollection()->transform(fn($a) => $this->formatAbsensiDetail($a));

        return response()->json([
            'status'  => true,
            'message' => 'Riwayat absensi berhasil dimuat.',
            'data'    => $absensi,
        ]);
    }

    /**
     * Ringkasan agregasi absensi karyawan untuk bulan dan tahun yang dipilih.
     * Menampilkan total hari, total menit, dan rekapitulasi per status.
     */
    public function ringkasan(Request $request): JsonResponse
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

        $absensiList = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->whereMonth('tanggal_absensi', $bulan)
            ->whereYear('tanggal_absensi', $tahun)
            ->get();

        // Total izin bulan ini
        $totalIzin = PengajuanIzin::where('id_karyawan', $karyawan->id_karyawan)
            ->whereMonth('tanggal_izin', $bulan)
            ->whereYear('tanggal_izin', $tahun)
            ->where('status', PengajuanIzin::STATUS_DISETUJUI)
            ->count();

        // Total lembur resmi bulan ini
        $totalMenitLembur = PengajuanLembur::where('id_karyawan', $karyawan->id_karyawan)
            ->whereMonth('tanggal_lembur', $bulan)
            ->whereYear('tanggal_lembur', $tahun)
            ->where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->sum('menit_lembur_resmi');

        $ringkasan = [
            'periode'                 => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            'total_hari_hadir'        => $absensiList->whereIn('status_kehadiran', ['hadir', 'pending'])->count(),
            'total_hari_izin'         => $totalIzin,
            'total_hari_alpa'         => $absensiList->where('status_kehadiran', 'alpa')->count(),
            'total_menit_kerja_normal'=> $absensiList->sum('menit_kerja_normal'),
            'total_menit_telat'       => $absensiList->sum('menit_telat'),
            'total_menit_pulang_cepat'=> $absensiList->sum('menit_pulang_cepat'),
            'total_menit_lembur_resmi'=> (int) $totalMenitLembur,
            'total_pending_validasi'  => $absensiList->where('status_validasi', 'menunggu')->count(),
        ];

        return response()->json([
            'status'  => true,
            'message' => 'Ringkasan absensi berhasil dimuat.',
            'data'    => $ringkasan,
        ]);
    }

    // ── HELPER ────────────────────────────────────────────────────────────────

    private function formatAbsensiDetail(Absensi $a): array
    {
        // Ambil lembur resmi yang disetujui untuk absensi ini (jika ada)
        $lembur = $a->pengajuanLembur
            ->where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->first();

        return [
            'id_absensi'         => $a->id_absensi,
            'tanggal_absensi'    => $a->tanggal_absensi?->format('Y-m-d'),
            'hari'               => $a->tanggal_absensi?->translatedFormat('l'),
            'shift'              => $a->jadwal?->shift ? [
                'nama_shift' => $a->jadwal->shift->nama_shift,
                'jam_masuk'  => substr($a->jadwal->shift->jam_masuk, 0, 5),
                'jam_pulang' => substr($a->jadwal->shift->jam_pulang, 0, 5),
            ] : null,
            'waktu_check_in'     => $a->waktu_check_in?->format('H:i'),
            'waktu_check_out'    => $a->waktu_check_out?->format('H:i'),
            'menit_kerja_normal' => $a->menit_kerja_normal,
            'menit_telat'        => $a->menit_telat,
            'menit_pulang_cepat' => $a->menit_pulang_cepat,
            'menit_kelebihan'    => $a->menit_kelebihan,
            'menit_lembur_resmi' => $lembur?->menit_lembur_resmi ?? 0,
            'status_kehadiran'   => $a->status_kehadiran,
            'status_validasi'    => $a->status_validasi,
        ];
    }
}