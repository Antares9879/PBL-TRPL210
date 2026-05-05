<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Karyawan\StoreLemburRequest;
use App\Models\Absensi;
use App\Models\PengajuanLembur;
use App\Services\LemburService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
/**
 * LemburApiController — F03
 *
 * Menangani pengajuan lembur retroaktif dari karyawan.
 *
 * Business rules (sesuai SKPPL 1.3 & 2.4):
 *   - Karyawan lembur terlebih dahulu, baru ajukan form.
 *   - Batas pengajuan: H+1 setelah tanggal lembur.
 *   - Pengajuan melewati H+1 → otomatis kadaluarsa.
 *   - Satu absensi hanya boleh punya satu pengajuan lembur aktif (menunggu/disetujui).
 *   - Menit lembur berstatus Pending hingga disetujui User Departemen (F12).
 *
 * Endpoints:
 *   GET  /api/karyawan/lembur       → index()  — riwayat pengajuan lembur
 *   POST /api/karyawan/lembur       → store()  — ajukan lembur baru
 *   GET  /api/karyawan/lembur/{id}  → show()   — detail satu pengajuan
 */
class LemburApiController extends Controller
{
    public function __construct(
        private readonly LemburService $lemburService,
    ) {}

    /**
     * Riwayat pengajuan lembur karyawan yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        if (! $karyawan) {
            return response()->json([
                'status'  => false,
                'message' => 'Data karyawan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $query = PengajuanLembur::where('id_karyawan', $karyawan->id_karyawan);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query
            ->orderByDesc('tanggal_lembur')
            ->paginate(20);

        $data->getCollection()->transform(fn($l) => $this->formatLembur($l));

        return response()->json([
            'status'  => true,
            'message' => 'Data pengajuan lembur berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    /**
     * Ajukan lembur baru.
     *
     * Validasi sebelum simpan:
     * 1. Karyawan sudah check-out pada tanggal lembur (absensi harus ada).
     * 2. Tidak ada pengajuan lembur aktif (menunggu/disetujui) untuk tanggal yang sama.
     * 3. Masih dalam batas H+1 — jika lewat, langsung simpan sebagai kadaluarsa.
     */
    public function store(StoreLemburRequest $request): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        if (! $karyawan) {
            return response()->json([
                'status'  => false,
                'message' => 'Data karyawan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Cari absensi pada tanggal lembur
        $absensi = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->whereDate('tanggal_absensi', $request->tanggal_lembur)
            ->whereNotNull('waktu_check_out') // harus sudah check-out
            ->first();

        if (! $absensi) {
            return response()->json([
                'status'  => false,
                'message' => 'Data absensi pada tanggal lembur tidak ditemukan atau belum check-out. Pengajuan lembur hanya bisa dilakukan setelah check-out.',
                'data'    => null,
            ], 422);
        }

        // Cek apakah ada kelebihan waktu yang dicatat saat check-out
        if ($absensi->menit_kelebihan === 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada kelebihan waktu kerja yang tercatat pada tanggal tersebut.',
                'data'    => null,
            ], 422);
        }

        // Cek minimum threshold untuk pengajuan lembur
        if ($absensi->menit_kelebihan < LemburService::MINIMUM_MENIT_LEMBUR) {
            return response()->json([
                'status'  => false,
                'message' => 'Kelebihan waktu kerja minimal ' . LemburService::MINIMUM_MENIT_LEMBUR . ' menit untuk dapat diajukan sebagai lembur.',
                'data'    => [
                    'menit_kelebihan'  => $absensi->menit_kelebihan,
                    'minimum_required' => LemburService::MINIMUM_MENIT_LEMBUR,
                ],
            ], 422);
        }

        // Cegah duplikasi: pengajuan aktif untuk tanggal yang sama
        $pengajuanAktif = PengajuanLembur::where('id_karyawan', $karyawan->id_karyawan)
            ->whereDate('tanggal_lembur', $request->tanggal_lembur)
            ->whereIn('status', [
                PengajuanLembur::STATUS_MENUNGGU,
                PengajuanLembur::STATUS_DISETUJUI,
            ])
            ->exists();

        if ($pengajuanAktif) {
            return response()->json([
                'status'  => false,
                'message' => 'Sudah ada pengajuan lembur aktif untuk tanggal ini.',
                'data'    => null,
            ], 422);
        }

        $lembur = $this->lemburService->buat([
            'id_karyawan'          => $karyawan->id_karyawan,
            'id_absensi'           => $absensi->id_absensi,
            'tanggal_lembur'       => $request->tanggal_lembur,
            'jam_mulai_estimasi'   => $request->jam_mulai_estimasi,
            'jam_selesai_estimasi' => $request->jam_selesai_estimasi,
            'alasan_lembur'        => $request->alasan_lembur,
        ]);

        // Susun pesan berdasarkan status hasil
        if ($lembur->status === PengajuanLembur::STATUS_KADALUARSA) {
            $pesan = 'Pengajuan lembur ditolak secara otomatis karena melewati batas waktu H+1.';
        } else {
            $pesan = 'Pengajuan lembur berhasil dikirim. Menunggu persetujuan User Departemen.';
        }

        return response()->json([
            'status'  => $lembur->status !== PengajuanLembur::STATUS_KADALUARSA,
            'message' => $pesan,
            'data'    => $this->formatLembur($lembur),
        ], 201);
    }

    /**
     * Detail satu pengajuan lembur.
     */
    public function show(int $id): JsonResponse
    {
        $karyawan = Auth::user()->karyawan;

        $lembur = PengajuanLembur::where('id_lembur', $id)
            ->where('id_karyawan', $karyawan?->id_karyawan)
            ->first();

        if (! $lembur) {
            return response()->json([
                'status'  => false,
                'message' => 'Pengajuan lembur tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail pengajuan lembur berhasil dimuat.',
            'data'    => $this->formatLembur($lembur),
        ]);
    }

    private function formatLembur(PengajuanLembur $l): array
    {
        return [
            'id_lembur'             => $l->id_lembur,
            'tanggal_lembur'        => $l->tanggal_lembur?->format('Y-m-d'),
            'jam_mulai_estimasi'    => $l->jam_mulai_estimasi,
            'jam_selesai_estimasi'  => $l->jam_selesai_estimasi,
            'menit_lembur_diajukan' => $l->menit_lembur_diajukan,
            'menit_lembur_resmi'    => $l->menit_lembur_resmi,
            'alasan_lembur'         => $l->alasan_lembur,
            'status'                => $l->status,
            'catatan_penolakan'     => $l->catatan_penolakan,
            'batas_pengajuan'       => $l->batas_pengajuan?->format('Y-m-d'),
            'diajukan_pada'         => $l->diajukan_pada?->toDateTimeString(),
            'waktu_proses'          => $l->waktu_proses?->toDateTimeString(),
        ];
    }
}