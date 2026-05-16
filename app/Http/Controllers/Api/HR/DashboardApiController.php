<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanIzin;
use App\Models\PengajuanLembur;
use App\Models\PerusahaanOutsource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DashboardApiController — HR Ecogreen
 *
 * Menyediakan data monitoring kehadiran seluruh karyawan outsource
 * dari semua departemen dan perusahaan untuk dashboard HR.
 *
 * Scope: HR memiliki akses penuh ke seluruh data lintas departemen
 * dan perusahaan outsource — tidak ada pembatasan scope seperti role lain.
 *
 * Endpoints:
 *   GET /api/hr/dashboard/stats            → stats()        — stat cards utama
 *   GET /api/hr/dashboard/ringkasan        → ringkasan()    — ringkasan per departemen
 *   GET /api/hr/dashboard/absensi          → absensi()      — daftar absensi (paginasi, filter)
 *   GET /api/hr/dashboard/absensi/{id}     → detailAbsensi() — detail satu absensi
 */
class DashboardApiController extends Controller
{
    // ════════════════════════════════════════════════════════════════════════
    //  STATS — Stat cards utama dashboard HR
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dashboard/stats
     *
     * Mengembalikan data agregat untuk stat cards dashboard HR:
     *   - Total karyawan outsource aktif (semua perusahaan)
     *   - Statistik kehadiran hari ini
     *   - Statistik lembur & izin pending
     *   - Ringkasan per perusahaan outsource
     *
     * Filter opsional: bulan, tahun (untuk statistik periode)
     */
    public function stats(Request $request): JsonResponse
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        // ── Total karyawan aktif (lintas perusahaan) ──────────────────────
        $totalKaryawanAktif = Karyawan::where('status', 'aktif')->count();
        $totalPerusahaan    = PerusahaanOutsource::where('status', 'aktif')->count();
        $totalDepartemen    = Departemen::where('status', 'aktif')->count();

        // ── Statistik kehadiran hari ini ──────────────────────────────────
        $absensiHariIni = Absensi::whereDate('tanggal_absensi', today())
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')
            ->groupBy('status_kehadiran')
            ->pluck('jumlah', 'status_kehadiran');

        $hadirHariIni  = (int) ($absensiHariIni['hadir']   ?? 0)
                       + (int) ($absensiHariIni['pending']  ?? 0);
        $izinHariIni   = (int) ($absensiHariIni['izin']    ?? 0);
        $alpaHariIni   = (int) ($absensiHariIni['alpa']    ?? 0);
        $belumAbsen    = max(0, $totalKaryawanAktif - $hadirHariIni - $izinHariIni - $alpaHariIni);

        // ── Statistik menunggu validasi ───────────────────────────────────
        $absensiMenunggu = Absensi::where('status_validasi', Absensi::VALIDASI_MENUNGGU)->count();
        $lemburMenunggu  = PengajuanLembur::where('status', PengajuanLembur::STATUS_MENUNGGU)->count();
        $izinMenunggu    = PengajuanIzin::where('status', PengajuanIzin::STATUS_MENUNGGU)->count();

        // ── Statistik bulan berjalan ──────────────────────────────────────
        $absensiPeriode = Absensi::whereMonth('tanggal_absensi', $bulan)
            ->whereYear('tanggal_absensi', $tahun)
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')
            ->groupBy('status_kehadiran')
            ->pluck('jumlah', 'status_kehadiran');

        $totalMenitLemburDisetujui = PengajuanLembur::where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->whereMonth('tanggal_lembur', $bulan)
            ->whereYear('tanggal_lembur', $tahun)
            ->sum('menit_lembur_resmi');

        // ── Persentase kehadiran bulan ini ────────────────────────────────
        $totalAbsensiPeriode = array_sum($absensiPeriode->toArray());
        $hadirPeriode        = (int) ($absensiPeriode['hadir'] ?? 0);
        $pctKehadiran        = $totalAbsensiPeriode > 0
            ? round(($hadirPeriode / $totalAbsensiPeriode) * 100, 1)
            : 0;

        return response()->json([
            'status'  => true,
            'message' => 'Statistik dashboard HR berhasil dimuat.',
            'data'    => [
                'periode' => ['bulan' => $bulan, 'tahun' => $tahun],

                // Stat cards utama
                'total_karyawan_aktif' => $totalKaryawanAktif,
                'total_perusahaan'     => $totalPerusahaan,
                'total_departemen'     => $totalDepartemen,

                // Kehadiran hari ini
                'hari_ini' => [
                    'hadir'       => $hadirHariIni,
                    'izin'        => $izinHariIni,
                    'alpa'        => $alpaHariIni,
                    'belum_absen' => $belumAbsen,
                ],

                // Pending validation
                'menunggu' => [
                    'absensi' => $absensiMenunggu,
                    'lembur'  => $lemburMenunggu,
                    'izin'    => $izinMenunggu,
                ],

                // Statistik periode
                'periode_stats' => [
                    'total_hadir'                  => (int) ($absensiPeriode['hadir'] ?? 0),
                    'total_izin'                   => (int) ($absensiPeriode['izin']  ?? 0),
                    'total_alpa'                   => (int) ($absensiPeriode['alpa']  ?? 0),
                    'persentase_kehadiran'         => $pctKehadiran,
                    'total_menit_lembur_disetujui' => (int) $totalMenitLemburDisetujui,
                ],
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RINGKASAN PER DEPARTEMEN — Untuk tabel monitoring di dashboard
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dashboard/ringkasan
     *
     * Mengembalikan ringkasan kehadiran per departemen untuk periode tertentu.
     * Digunakan sebagai tabel rekap status per departemen di dashboard HR.
     *
     * Filter: bulan, tahun, id_departemen, id_perusahaan
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $bulan        = (int) $request->get('bulan', now()->month);
        $tahun        = (int) $request->get('tahun', now()->year);
        $idDepartemen = $request->get('id_departemen');
        $idPerusahaan = $request->get('id_perusahaan');

        // Bangun query dasar karyawan aktif dengan filter
        $karyawanQuery = Karyawan::with('departemen:id_departemen,nama_departemen,kode_departemen')
            ->where('status', 'aktif');

        if ($idDepartemen) {
            $karyawanQuery->where('id_departemen', $idDepartemen);
        }
        if ($idPerusahaan) {
            $karyawanQuery->where('id_perusahaan', $idPerusahaan);
        }

        $karyawanList = $karyawanQuery->get(['id_karyawan', 'id_departemen', 'id_perusahaan']);

        // Absensi periode untuk karyawan yang relevan
        $idKaryawanList = $karyawanList->pluck('id_karyawan');

        $absensiData = Absensi::whereIn('id_karyawan', $idKaryawanList)
            ->whereMonth('tanggal_absensi', $bulan)
            ->whereYear('tanggal_absensi', $tahun)
            ->selectRaw('id_karyawan, status_kehadiran, COUNT(*) as jumlah')
            ->groupBy('id_karyawan', 'status_kehadiran')
            ->get()
            ->groupBy('id_karyawan');

        // Lembur disetujui per karyawan
        $lemburData = PengajuanLembur::whereIn('id_karyawan', $idKaryawanList)
            ->where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->whereMonth('tanggal_lembur', $bulan)
            ->whereYear('tanggal_lembur', $tahun)
            ->selectRaw('id_karyawan, SUM(menit_lembur_resmi) as total_menit')
            ->groupBy('id_karyawan')
            ->pluck('total_menit', 'id_karyawan');

        // Agregasi per departemen
        $ringkasanDept = [];
        foreach ($karyawanList->groupBy('id_departemen') as $idDept => $karyawanDept) {
            $dept = $karyawanDept->first()->departemen;

            $totalHadir       = 0;
            $totalIzin        = 0;
            $totalAlpa        = 0;
            $totalMenitLembur = 0;

            foreach ($karyawanDept as $k) {
                $absensiK = $absensiData->get($k->id_karyawan, collect());
                foreach ($absensiK as $a) {
                    match ($a->status_kehadiran) {
                        'hadir', 'pending' => $totalHadir += $a->jumlah,
                        'izin'             => $totalIzin  += $a->jumlah,
                        'alpa'             => $totalAlpa  += $a->jumlah,
                        default            => null,
                    };
                }
                $totalMenitLembur += (int) ($lemburData->get($k->id_karyawan, 0));
            }

            $totalAbsensi    = $totalHadir + $totalIzin + $totalAlpa;
            $pctKehadiran    = $totalAbsensi > 0
                ? round(($totalHadir / $totalAbsensi) * 100, 1)
                : 0;

            $ringkasanDept[] = [
                'id_departemen'       => $idDept,
                'nama_departemen'     => $dept?->nama_departemen ?? 'Tidak diketahui',
                'kode_departemen'     => $dept?->kode_departemen ?? '-',
                'jumlah_karyawan'     => $karyawanDept->count(),
                'total_hadir'         => $totalHadir,
                'total_izin'          => $totalIzin,
                'total_alpa'          => $totalAlpa,
                'persentase_kehadiran'=> $pctKehadiran,
                'total_menit_lembur'  => $totalMenitLembur,
            ];
        }

        // Urutkan berdasarkan nama departemen
        usort($ringkasanDept, fn($a, $b) => strcmp($a['nama_departemen'], $b['nama_departemen']));

        return response()->json([
            'status'  => true,
            'message' => 'Ringkasan per departemen berhasil dimuat.',
            'data'    => [
                'periode'   => ['bulan' => $bulan, 'tahun' => $tahun],
                'departemen'=> $ringkasanDept,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ABSENSI — Daftar absensi lintas departemen (paginasi)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dashboard/absensi
     *
     * Daftar absensi semua karyawan outsource dengan filter lengkap.
     * HR bisa filter berdasarkan departemen, perusahaan, tanggal, dan status.
     * Default: 7 hari terakhir jika tidak ada filter tanggal.
     */
    public function absensi(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 20), 100));

        $query = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang',
        ]);

        // Filter departemen
        if ($request->filled('id_departemen')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_departemen', $request->id_departemen));
        }

        // Filter perusahaan outsource
        if ($request->filled('id_perusahaan')) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $request->id_perusahaan));
        }

        // Filter tanggal — default 7 hari terakhir
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_absensi', '>=', $request->tanggal_dari);
        } elseif (! $request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '>=', now()->subDays(6)->toDateString());
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_absensi', '<=', $request->tanggal_sampai);
        }

        // Filter hari ini
        if ($request->boolean('hari_ini')) {
            $query->whereDate('tanggal_absensi', today());
        }

        // Filter status
        if ($request->filled('status_kehadiran')) {
            $query->where('status_kehadiran', $request->status_kehadiran);
        }

        if ($request->filled('status_validasi')) {
            $query->where('status_validasi', $request->status_validasi);
        }

        // Filter karyawan spesifik
        if ($request->filled('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        // Search nama / nomor karyawan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('karyawan', fn($q) => $q
                ->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nomor_karyawan', 'like', "%{$search}%")
            );
        }

        $data = $query
            ->orderByDesc('tanggal_absensi')
            ->orderBy('status_validasi')
            ->paginate($perPage);

        $data->getCollection()->transform(fn($a) => $this->formatAbsensi($a));

        return response()->json([
            'status'  => true,
            'message' => 'Data absensi berhasil dimuat.',
            'data'    => $data,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DETAIL ABSENSI — Satu record lengkap
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dashboard/absensi/{id}
     *
     * Detail lengkap satu record absensi beserta data lembur terkait.
     * HR dapat mengakses absensi karyawan dari departemen manapun.
     */
    public function detailAbsensi(int $id): JsonResponse
    {
        $absensi = Absensi::with([
            'karyawan:id_karyawan,nama_lengkap,nomor_karyawan,posisi,id_departemen,id_perusahaan',
            'karyawan.departemen:id_departemen,nama_departemen,kode_departemen',
            'karyawan.perusahaan:id_perusahaan,nama_perusahaan',
            'jadwal.shift:id_shift,nama_shift,jam_masuk,jam_pulang,durasi_normal_menit',
            'pengajuanLembur:id_lembur,id_absensi,status,menit_lembur_diajukan,menit_lembur_resmi,alasan_lembur,diajukan_pada',
            'validator:id_pengguna,nama_lengkap,role',
        ])->find($id);

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
    //  LOOKUP — Untuk filter dropdown
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/hr/dashboard/filter-options
     *
     * Mengembalikan daftar departemen dan perusahaan untuk populate dropdown filter.
     */
    public function filterOptions(): JsonResponse
    {
        $departemen = Departemen::where('status', 'aktif')
            ->orderBy('nama_departemen')
            ->get(['id_departemen', 'nama_departemen', 'kode_departemen']);

        $perusahaan = PerusahaanOutsource::where('status', 'aktif')
            ->orderBy('nama_perusahaan')
            ->get(['id_perusahaan', 'nama_perusahaan']);

        return response()->json([
            'status'  => true,
            'message' => 'Filter options berhasil dimuat.',
            'data'    => [
                'departemen' => $departemen,
                'perusahaan' => $perusahaan,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Helpers
    // ════════════════════════════════════════════════════════════════════════

    private function formatAbsensi(Absensi $a, bool $detail = false): array
    {
        $base = [
            'id_absensi'         => $a->id_absensi,
            'tanggal_absensi'    => $a->tanggal_absensi?->format('Y-m-d'),
            'karyawan'           => $a->karyawan ? [
                'id_karyawan'    => $a->karyawan->id_karyawan,
                'nama_lengkap'   => $a->karyawan->nama_lengkap,
                'nomor_karyawan' => $a->karyawan->nomor_karyawan,
                'departemen'     => $a->karyawan->departemen?->nama_departemen,
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
            'catatan_penolakan'  => $a->catatan_penolakan,
        ];

        if ($detail) {
            $base['karyawan']['posisi']          = $a->karyawan?->posisi;
            $base['karyawan']['kode_departemen'] = $a->karyawan?->departemen?->kode_departemen;

            if ($a->jadwal?->shift) {
                $base['shift']['durasi_normal_menit'] = $a->jadwal->shift->durasi_normal_menit;
            }

            $base['lokasi'] = [
                'is_valid_in'  => $a->is_lokasi_valid_in,
                'is_valid_out' => $a->is_lokasi_valid_out,
            ];

            $base['validator'] = $a->validator ? [
                'nama_lengkap' => $a->validator->nama_lengkap,
                'role'         => $a->validator->role,
            ] : null;

            $base['waktu_validasi'] = $a->waktu_validasi?->toDateTimeString();

            $base['pengajuan_lembur'] = $a->pengajuanLembur
                ->map(fn($l) => [
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
