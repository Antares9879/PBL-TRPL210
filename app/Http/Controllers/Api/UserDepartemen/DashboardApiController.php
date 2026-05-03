<?php

namespace App\Http\Controllers\Api\UserDepartemen;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengajuanLembur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardApiController — User Departemen
 *
 * Menyediakan data monitoring kehadiran karyawan outsource
 * yang ditugaskan di departemen User Departemen yang login.
 *
 * Scope keamanan:
 *   - Data yang ditampilkan HANYA karyawan dari departemen User yang login.
 *   - Tidak ada akses ke data departemen lain dalam kondisi apapun.
 *   - Semua query di-scope via getIdDepartemen().
 *
 * Endpoints:
 *   GET /api/departemen/dashboard/ringkasan    → ringkasan()   — stat cards hari ini
 *   GET /api/departemen/dashboard/absensi      → absensi()     — daftar absensi (paginasi)
 *   GET /api/departemen/dashboard/absensi/{id} → detailAbsensi() — detail satu absensi
 */
class DashboardApiController extends Controller
{
    // ════════════════════════════════════════════════════════════════════════
    //  RINGKASAN — Stat cards untuk dashboard
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Ringkasan kehadiran departemen hari ini dan bulan berjalan.
     *
     * Mengembalikan:
     *   - Total karyawan aktif di departemen
     *   - Stat hari ini: hadir / belum absen / izin / alpa
     *   - Stat bulan ini: total hari hadir, total menit lembur disetujui
     *   - Jumlah pengajuan lembur menunggu persetujuan
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $idDepartemen = $this->getIdDepartemen();
        $bulan        = (int) $request->get('bulan', now()->month);
        $tahun        = (int) $request->get('tahun', now()->year);

        // Total karyawan aktif di departemen ini
        $totalKaryawan = Karyawan::where('id_departemen', $idDepartemen)
            ->where('status', 'aktif')
            ->count();

        // Stat kehadiran hari ini
        $absensiHariIni = Absensi::whereHas('karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen))
            ->whereDate('tanggal_absensi', today())
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')
            ->groupBy('status_kehadiran')
            ->pluck('jumlah', 'status_kehadiran');

        $hadirHariIni = (int) ($absensiHariIni['hadir'] ?? 0)
                      + (int) ($absensiHariIni['pending'] ?? 0);
        $izinHariIni  = (int) ($absensiHariIni['izin'] ?? 0);
        $alpaHariIni  = (int) ($absensiHariIni['alpa'] ?? 0);
        $belumAbsen   = max(0, $totalKaryawan - $hadirHariIni - $izinHariIni - $alpaHariIni);

        // Stat bulan ini
        $absensiIds = Absensi::whereHas('karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen))
            ->whereMonth('tanggal_absensi', $bulan)
            ->whereYear('tanggal_absensi', $tahun)
            ->pluck('id_absensi');

        $totalMenitLembur = PengajuanLembur::whereHas(
            'karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen)
        )
        ->whereMonth('tanggal_lembur', $bulan)
        ->whereYear('tanggal_lembur', $tahun)
        ->where('status', PengajuanLembur::STATUS_DISETUJUI)
        ->sum('menit_lembur_resmi');

        // Lembur menunggu (semua bulan — belum diproses)
        $lemburMenunggu = PengajuanLembur::whereHas(
            'karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen)
        )
        ->where('status', PengajuanLembur::STATUS_MENUNGGU)
        ->count();

        return response()->json([
            'status'  => true,
            'message' => 'Ringkasan dashboard berhasil dimuat.',
            'data'    => [
                'periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                'karyawan_aktif'         => $totalKaryawan,
                'hari_ini'               => [
                    'hadir'       => $hadirHariIni,
                    'belum_absen' => $belumAbsen,
                    'izin'        => $izinHariIni,
                    'alpa'        => $alpaHariIni,
                ],
                'bulan_ini'              => [
                    'total_menit_lembur_disetujui' => (int) $totalMenitLembur,
                ],
                'lembur_menunggu_proses' => $lemburMenunggu,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ABSENSI — Daftar absensi karyawan (read-only, paginasi)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar absensi karyawan di departemen User Departemen yang login.
     *
     * Bisa difilter: tanggal_dari, tanggal_sampai, status_kehadiran, nama karyawan.
     * Default: tampilkan absensi 7 hari terakhir jika tidak ada filter tanggal.
     *
     * READ-ONLY: endpoint ini hanya untuk monitoring, tidak ada aksi validasi.
     */
    public function absensi(Request $request): JsonResponse
    {
        $idDepartemen = $this->getIdDepartemen();

        $query = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
        ])
        ->whereHas('karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen));

        // Filter tanggal — default 7 hari terakhir
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_absensi', '>=', $request->tanggal_dari);
        } elseif (! $request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '>=', now()->subDays(6)->toDateString());
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '<=', $request->tanggal_sampai);
        }

        // Filter hari ini shortcut
        if ($request->boolean('hari_ini')) {
            $query->whereDate('tanggal_absensi', today());
        }

        if ($request->filled('status_kehadiran')) {
            $query->where('status_kehadiran', $request->status_kehadiran);
        }

        // Filter per karyawan
        if ($request->filled('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn ($q) => $q
                ->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nomor_karyawan', 'like', "%{$search}%")
            );
        }

        $data = $query
            ->orderByDesc('tanggal_absensi')
            ->orderBy('status_kehadiran')
            ->paginate(20);

        $data->getCollection()->transform(fn ($a) => $this->formatAbsensi($a));

        return response()->json([
            'status'  => true,
            'message' => 'Data absensi departemen berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DETAIL ABSENSI — Satu record absensi lengkap
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Detail lengkap satu record absensi, termasuk data lembur yang terkait.
     * Scope ke departemen User Departemen yang login.
     */
    public function detailAbsensi(int $id): JsonResponse
    {
        $idDepartemen = $this->getIdDepartemen();

        $absensi = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,posisi,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang,durasi_normal_menit',
            'pengajuanLembur:id_lembur,id_absensi,status,menit_lembur_diajukan,menit_lembur_resmi,alasan_lembur,diajukan_pada',
        ])
        ->whereHas('karyawan', fn ($q) => $q->where('id_departemen', $idDepartemen))
        ->find($id);

        if (! $absensi) {
            return response()->json([
                'status'  => false,
                'message' => 'Data absensi tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail absensi berhasil dimuat.',
            'data'    => $this->formatAbsensi($absensi, detail: true),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DAFTAR KARYAWAN — Lookup untuk filter dropdown
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Daftar karyawan aktif di departemen User Departemen yang login.
     * Digunakan untuk populate dropdown filter pada halaman monitoring.
     * Tidak perlu paginasi — jumlah karyawan per departemen terbatas.
     */
    public function daftarKaryawan(): JsonResponse
    {
        $idDepartemen = $this->getIdDepartemen();

        $karyawan = Karyawan::where('id_departemen', $idDepartemen)
            ->where('status', 'aktif')
            ->with('perusahaan:id_perusahaan,nama_perusahaan')
            ->orderBy('nama_lengkap')
            ->get(['id_karyawan', 'nama_lengkap', 'nomor_karyawan', 'posisi', 'id_perusahaan']);

        return response()->json([
            'status'  => true,
            'message' => 'Daftar karyawan departemen berhasil dimuat.',
            'data'    => $karyawan->map(fn ($k) => [
                'id_karyawan'    => $k->id_karyawan,
                'nama_lengkap'   => $k->nama_lengkap,
                'nomor_karyawan' => $k->nomor_karyawan,
                'posisi'         => $k->posisi,
                'perusahaan'     => $k->perusahaan?->nama_perusahaan,
            ]),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Ambil id_departemen dari profil User Departemen yang sedang login.
     */
    private function getIdDepartemen(): int
    {
        return Auth::id() ? Auth::user()->id_departemen : 0;
    }

    /**
     * Format output absensi — konsisten untuk index maupun detail.
     */
    private function formatAbsensi(Absensi $a, bool $detail = false): array
    {
        $base = [
            'id_absensi'         => $a->id_absensi,
            'tanggal_absensi'    => $a->tanggal_absensi?->format('Y-m-d'),
            'karyawan'           => $a->karyawan ? [
                'id_karyawan'    => $a->karyawan->id_karyawan,
                'nama_lengkap'   => $a->karyawan->nama_lengkap,
                'nomor_karyawan' => $a->karyawan->nomor_karyawan,
                'perusahaan'     => $a->karyawan->perusahaan?->nama_perusahaan,
            ] : null,
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
            'status_kehadiran'   => $a->status_kehadiran,
            'status_validasi'    => $a->status_validasi,
        ];

        if ($detail) {
            $base['karyawan'] = $a->karyawan ? [
                'id_karyawan'    => $a->karyawan->id_karyawan,
                'nama_lengkap'   => $a->karyawan->nama_lengkap,
                'nomor_karyawan' => $a->karyawan->nomor_karyawan,
                'posisi'         => $a->karyawan->posisi ?? null,
                'departemen'     => $a->karyawan->departemen?->nama_departemen,
                'perusahaan'     => $a->karyawan->perusahaan?->nama_perusahaan,
            ] : null;

            if ($a->jadwal?->shift) {
                $base['shift']['durasi_normal_menit'] = $a->jadwal->shift->durasi_normal_menit;
            }

            // Data lokasi GPS (untuk audit trail, tidak diekspos di index)
            $base['lokasi'] = [
                'is_valid_in'  => $a->is_lokasi_valid_in,
                'is_valid_out' => $a->is_lokasi_valid_out,
            ];

            // Pengajuan lembur terkait absensi ini (jika ada)
            $base['pengajuan_lembur'] = $a->pengajuanLembur
                ->map(fn ($l) => [
                    'id_lembur'             => $l->id_lembur,
                    'status'                => $l->status,
                    'menit_lembur_diajukan' => $l->menit_lembur_diajukan,
                    'menit_lembur_resmi'    => $l->menit_lembur_resmi,
                    'alasan_lembur'         => $l->alasan_lembur,
                    'diajukan_pada'         => $l->diajukan_pada?->toDateTimeString(),
                ])
                ->values();
        }

        return $base;
    }
}