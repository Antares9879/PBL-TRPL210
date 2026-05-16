<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengajuanIzin;
use App\Models\PengajuanLembur;
use App\Models\RekapBulanan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RekapService
 *
 * Mengagregasi data absensi, lembur, dan izin bulanan ke tabel rekap_bulanan.
 * Menghasilkan rekap per karyawan yang siap diunduh HR.
 *
 * Komponen yang direkap (non-payroll, satuan menit):
 *   - Total hari kerja (sesuai jadwal)
 *   - Total hari hadir (sudah check-in dan divalidasi)
 *   - Total hari izin (disetujui)
 *   - Total hari alpa
 *   - Total menit kerja normal
 *   - Total menit lembur resmi (hanya yang disetujui User Departemen)
 *   - Total menit telat
 *   - Total menit pulang cepat
 */
class RekapService
{
    // ════════════════════════════════════════════════════════════════════════
    //  GENERATE REKAP — Buat / update rekap untuk satu periode
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Generate rekap bulanan untuk seluruh karyawan aktif pada periode tertentu.
     * Bisa difilter by departemen atau perusahaan.
     *
     * @param int      $bulan        1–12
     * @param int      $tahun
     * @param int|null $idDepartemen Filter per departemen (null = semua)
     * @param int|null $idPerusahaan Filter per perusahaan outsource (null = semua)
     * @param int      $idPembuatHr  id_pengguna HR yang men-trigger rekap
     * @return array{berhasil: int, gagal: int, errors: array}
     */
    public function generate(
        int $bulan,
        int $tahun,
        ?int $idDepartemen,
        ?int $idPerusahaan,
        int $idPembuatHr,
    ): array {
        $karyawanQuery = Karyawan::where('status', 'aktif');

        if ($idDepartemen) {
            $karyawanQuery->where('id_departemen', $idDepartemen);
        }
        if ($idPerusahaan) {
            $karyawanQuery->where('id_perusahaan', $idPerusahaan);
        }

        $karyawanList = $karyawanQuery->get(['id_karyawan', 'nama_lengkap']);

        $berhasil = 0;
        $gagal    = 0;
        $errors   = [];

        foreach ($karyawanList as $karyawan) {
            try {
                $this->generateUntukKaryawan($karyawan->id_karyawan, $bulan, $tahun, $idPembuatHr);
                $berhasil++;
            } catch (\Throwable $e) {
                $gagal++;
                $errors[] = [
                    'id_karyawan'  => $karyawan->id_karyawan,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'pesan'        => $e->getMessage(),
                ];
                Log::error('RekapService: gagal generate rekap karyawan', [
                    'id_karyawan' => $karyawan->id_karyawan,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return compact('berhasil', 'gagal', 'errors');
    }

    /**
     * Generate rekap untuk satu karyawan pada periode tertentu.
     * Menggunakan updateOrCreate — aman dipanggil berulang kali (idempotent).
     * Rekap berstatus 'final' tidak bisa di-overwrite → lempar exception.
     */
    public function generateUntukKaryawan(
        int $idKaryawan,
        int $bulan,
        int $tahun,
        int $idPembuatHr,
    ): RekapBulanan {
        // Guard: jangan overwrite rekap yang sudah final
        $existing = RekapBulanan::where('id_karyawan', $idKaryawan)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->first();

        if ($existing && $existing->status_rekap === RekapBulanan::STATUS_FINAL) {
            throw new \RuntimeException(
                "Rekap bulan ini sudah berstatus Final dan tidak dapat diperbarui."
            );
        }

        // Hitung komponen rekap
        $komponenAbsensi = $this->hitungKomponenAbsensi($idKaryawan, $bulan, $tahun);
        $totalHariIzin   = $this->hitungTotalHariIzin($idKaryawan, $bulan, $tahun);
        $totalMenitLembur= $this->hitungTotalMenitLembur($idKaryawan, $bulan, $tahun);
        $totalHariKerja  = $this->hitungTotalHariKerja($idKaryawan, $bulan, $tahun);

        $rekap = RekapBulanan::updateOrCreate(
            [
                'id_karyawan'   => $idKaryawan,
                'periode_bulan' => $bulan,
                'periode_tahun' => $tahun,
            ],
            [
                'total_hari_kerja'        => $totalHariKerja,
                'total_hari_hadir'        => $komponenAbsensi['total_hari_hadir'],
                'total_hari_izin'         => $totalHariIzin,
                'total_hari_alpa'         => $komponenAbsensi['total_hari_alpa'],
                'total_menit_normal'      => $komponenAbsensi['total_menit_normal'],
                'total_menit_lembur'      => $totalMenitLembur,
                'total_menit_telat'       => $komponenAbsensi['total_menit_telat'],
                'total_menit_pulang_cepat'=> $komponenAbsensi['total_menit_pulang_cepat'],
                'status_rekap'            => RekapBulanan::STATUS_DRAFT,
                'dibuat_oleh'             => $idPembuatHr,
            ]
        );

        return $rekap;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  TETAPKAN FINAL — Kunci rekap agar tidak bisa diubah
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Tetapkan rekap sebagai Final untuk satu karyawan.
     * Rekap yang sudah Final tidak bisa di-generate ulang.
     */
    public function tetapkanFinal(int $idRekap, int $idHr): RekapBulanan
    {
        $rekap = RekapBulanan::findOrFail($idRekap);

        if ($rekap->status_rekap === RekapBulanan::STATUS_FINAL) {
            throw new \RuntimeException('Rekap ini sudah berstatus Final.');
        }

        $rekap->update([
            'status_rekap'   => RekapBulanan::STATUS_FINAL,
            'dibuat_oleh'    => $idHr,
            'ditetapkan_pada'=> now(),
        ]);

        return $rekap->fresh();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PREVIEW DATA — Ambil data rekap tanpa menyimpan ke tabel
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Ambil data rekap real-time untuk preview sebelum generate/unduh.
     * Tidak menyimpan ke tabel rekap_bulanan.
     *
     * @return Collection<int, array> Collection data per karyawan
     */
    public function getDataPreview(
        int $bulan,
        int $tahun,
        ?int $idDepartemen = null,
        ?int $idPerusahaan = null,
    ): Collection {
        $karyawanQuery = Karyawan::with([
            'departemen:id_departemen,nama_departemen',
            'perusahaan:id_perusahaan,nama_perusahaan',
        ])
        ->where('status', 'aktif');

        if ($idDepartemen) {
            $karyawanQuery->where('id_departemen', $idDepartemen);
        }
        if ($idPerusahaan) {
            $karyawanQuery->where('id_perusahaan', $idPerusahaan);
        }

        $karyawanList = $karyawanQuery->orderBy('nama_lengkap')
            ->get(['id_karyawan', 'nama_lengkap', 'nomor_karyawan', 'posisi', 'id_departemen', 'id_perusahaan']);

        return $karyawanList->map(function ($k) use ($bulan, $tahun) {
            $komponen     = $this->hitungKomponenAbsensi($k->id_karyawan, $bulan, $tahun);
            $totalIzin    = $this->hitungTotalHariIzin($k->id_karyawan, $bulan, $tahun);
            $totalLembur  = $this->hitungTotalMenitLembur($k->id_karyawan, $bulan, $tahun);
            $totalKerja   = $this->hitungTotalHariKerja($k->id_karyawan, $bulan, $tahun);

            // Cek apakah rekap sudah ada di DB
            $rekapExisting = RekapBulanan::where('id_karyawan', $k->id_karyawan)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $tahun)
                ->first();

            return [
                'id_karyawan'             => $k->id_karyawan,
                'nama_lengkap'            => $k->nama_lengkap,
                'nomor_karyawan'          => $k->nomor_karyawan,
                'posisi'                  => $k->posisi,
                'departemen'              => $k->departemen?->nama_departemen,
                'perusahaan'              => $k->perusahaan?->nama_perusahaan,
                'total_hari_kerja'        => $totalKerja,
                'total_hari_hadir'        => $komponen['total_hari_hadir'],
                'total_hari_izin'         => $totalIzin,
                'total_hari_alpa'         => $komponen['total_hari_alpa'],
                'total_menit_normal'      => $komponen['total_menit_normal'],
                'total_menit_lembur'      => $totalLembur,
                'total_menit_telat'       => $komponen['total_menit_telat'],
                'total_menit_pulang_cepat'=> $komponen['total_menit_pulang_cepat'],
                'status_rekap'            => $rekapExisting?->status_rekap ?? 'belum_digenerate',
                'id_rekap'                => $rekapExisting?->id_rekap,
            ];
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CEK STATUS DOKUMEN — Untuk guard sebelum Final
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Cek apakah masih ada dokumen berstatus tidak lengkap untuk periode tertentu.
     * Digunakan sebagai guard sebelum HR menetapkan rekap sebagai Final.
     *
     * @return array{ada_tidak_lengkap: bool, jumlah: int, detail: array}
     */
    public function cekStatusDokumen(
        int $bulan,
        int $tahun,
        ?int $idDepartemen = null,
        ?int $idPerusahaan = null,
    ): array {
        $query = PengajuanIzin::with([
            'karyawan:id_karyawan,nama_lengkap,id_departemen,id_perusahaan',
        ])
        ->where('status', PengajuanIzin::STATUS_DISETUJUI)
        ->whereMonth('tanggal_izin', $bulan)
        ->whereYear('tanggal_izin', $tahun)
        ->whereIn('status_dokumen', [
            PengajuanIzin::DOKUMEN_SUDAH_UPLOAD,   // upload tapi belum diverifikasi HR
            PengajuanIzin::DOKUMEN_TIDAK_LENGKAP,   // ditandai tidak lengkap
            PengajuanIzin::DOKUMEN_BELUM_UPLOAD,    // wajib_dokumen tapi belum upload
        ]);

        if ($idDepartemen) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_departemen', $idDepartemen));
        }
        if ($idPerusahaan) {
            $query->whereHas('karyawan', fn($q) => $q->where('id_perusahaan', $idPerusahaan));
        }

        $izinBermasalah = $query->get();

        return [
            'ada_tidak_lengkap' => $izinBermasalah->isNotEmpty(),
            'jumlah'            => $izinBermasalah->count(),
            'detail'            => $izinBermasalah->map(fn($i) => [
                'id_izin'        => $i->id_izin,
                'nama_karyawan'  => $i->karyawan?->nama_lengkap,
                'tanggal_izin'   => $i->tanggal_izin?->format('Y-m-d'),
                'status_dokumen' => $i->status_dokumen,
            ])->values()->all(),
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PRIVATE — Kalkulasi komponen rekap
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Hitung komponen absensi dari tabel absensi untuk periode tertentu.
     */
    private function hitungKomponenAbsensi(int $idKaryawan, int $bulan, int $tahun): array
    {
        $absensiList = Absensi::where('id_karyawan', $idKaryawan)
            ->whereMonth('tanggal_absensi', $bulan)
            ->whereYear('tanggal_absensi', $tahun)
            ->get([
                'status_kehadiran',
                'menit_kerja_normal',
                'menit_telat',
                'menit_pulang_cepat',
            ]);

        return [
            'total_hari_hadir'        => $absensiList
                ->whereIn('status_kehadiran', ['hadir', 'pending'])
                ->count(),
            'total_hari_alpa'         => $absensiList
                ->where('status_kehadiran', 'alpa')
                ->count(),
            'total_menit_normal'      => (int) $absensiList->sum('menit_kerja_normal'),
            'total_menit_telat'       => (int) $absensiList->sum('menit_telat'),
            'total_menit_pulang_cepat'=> (int) $absensiList->sum('menit_pulang_cepat'),
        ];
    }

    /**
     * Hitung total hari izin yang disetujui untuk periode.
     * Menggunakan range tanggal izin (inklusif) untuk akurasi multi-day.
     */
    private function hitungTotalHariIzin(int $idKaryawan, int $bulan, int $tahun): int
    {
        $izinList = PengajuanIzin::where('id_karyawan', $idKaryawan)
            ->where('status', PengajuanIzin::STATUS_DISETUJUI)
            ->whereMonth('tanggal_izin', $bulan)
            ->whereYear('tanggal_izin', $tahun)
            ->get(['tanggal_izin', 'tanggal_selesai_izin']);

        return $izinList->sum(fn($i) => (int) $i->tanggal_izin->diffInDays(
            $i->tanggal_selesai_izin ?? $i->tanggal_izin
        ) + 1);
    }

    /**
     * Hitung total menit lembur resmi yang sudah disetujui User Departemen.
     */
    private function hitungTotalMenitLembur(int $idKaryawan, int $bulan, int $tahun): int
    {
        return (int) PengajuanLembur::where('id_karyawan', $idKaryawan)
            ->where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->whereMonth('tanggal_lembur', $bulan)
            ->whereYear('tanggal_lembur', $tahun)
            ->sum('menit_lembur_resmi');
    }

    /**
     * Hitung total hari kerja sesuai jadwal (tidak termasuk hari libur).
     */
    private function hitungTotalHariKerja(int $idKaryawan, int $bulan, int $tahun): int
    {
        return (int) DB::table('jadwal_kerja')
            ->join('planning_kerja', 'jadwal_kerja.id_planning', '=', 'planning_kerja.id_planning')
            ->where('jadwal_kerja.id_karyawan', $idKaryawan)
            ->where('jadwal_kerja.is_hari_libur', false)
            ->where('planning_kerja.status', 'aktif')
            ->whereMonth('jadwal_kerja.tanggal_kerja', $bulan)
            ->whereYear('jadwal_kerja.tanggal_kerja', $tahun)
            ->count();
    }
}
