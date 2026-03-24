<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\PengajuanLembur;
use Carbon\Carbon;

/**
 * LemburService
 *
 * Memusatkan seluruh logika bisnis pengajuan lembur retroaktif (F03).
 *
 * Business rules dari SKPPL:
 *   - Karyawan lembur dulu, ajukan form setelahnya (retroaktif).
 *   - Batas pengajuan: maksimal H+1 setelah tanggal lembur.
 *   - Pengajuan melewati H+1 ditolak otomatis oleh sistem (status: kadaluarsa).
 *   - Menit lembur resmi baru dihitung setelah disetujui User Departemen.
 *   - Menit lembur yang dihitung = berdasarkan check-out aktual vs jam pulang jadwal.
 */
class LemburService
{
    /**
     * Validasi apakah pengajuan lembur masih dalam batas waktu H+1.
     *
     * @param  string|\Carbon\Carbon $tanggalLembur   Tanggal ketika lembur terjadi
     * @param  string|\Carbon\Carbon $tanggalPengajuan Tanggal saat form diajukan
     * @return bool
     */
    public function masihDalamBatasH1(
        Carbon|string $tanggalLembur,
        Carbon|string $tanggalPengajuan,
    ): bool {
        $lembur   = Carbon::parse($tanggalLembur)->startOfDay();
        $pengajuan= Carbon::parse($tanggalPengajuan)->startOfDay();
        $batas    = $lembur->copy()->addDay(); // H+1

        return $pengajuan->lte($batas);
    }

    /**
     * Hitung menit lembur yang diajukan berdasarkan jam estimasi.
     * Ini untuk preview di form — menit resmi dihitung saat approval User Departemen.
     */
    public function hitungMenitDiajukan(string $jamMulai, string $jamSelesai): int
    {
        $mulai   = Carbon::parse($jamMulai);
        $selesai = Carbon::parse($jamSelesai);

        // Handle overnight lembur
        if ($selesai->lte($mulai)) {
            $selesai->addDay();
        }

        return max(0, (int) $mulai->diffInMinutes($selesai));
    }

    /**
     * Hitung menit lembur resmi saat approval.
     *
     * Menit lembur resmi = selisih waktu check-out aktual vs jam pulang jadwal.
     * Dibatasi maksimal oleh menit yang diajukan (tidak bisa lebih dari klaim).
     *
     * @param  Absensi $absensi  Record absensi karyawan pada hari lembur
     * @param  int     $menitDiajukan  Menit lembur dari form pengajuan
     * @return int
     */
    public function hitungMenitResmi(Absensi $absensi, int $menitDiajukan): int
    {
        // Gunakan menit_kelebihan dari absensi sebagai batas aktual
        $menitAktual = $absensi->menit_kelebihan;

        // Menit resmi = minimum dari yang diajukan vs yang aktual di lapangan
        return min($menitDiajukan, $menitAktual);
    }

    /**
     * Buat record pengajuan lembur baru.
     *
     * Jika tanggal pengajuan melewati H+1, langsung set status kadaluarsa.
     *
     * @param  array{
     *     id_karyawan: int,
     *     id_absensi: int,
     *     tanggal_lembur: string,
     *     jam_mulai_estimasi: string,
     *     jam_selesai_estimasi: string,
     *     alasan_lembur: string,
     * } $data
     * @return PengajuanLembur
     */
    public function buat(array $data): PengajuanLembur
    {
        $tanggalLembur    = Carbon::parse($data['tanggal_lembur']);
        $batasPengajuan   = $tanggalLembur->copy()->addDay()->endOfDay();
        $masihDalamBatas  = now()->lte($batasPengajuan);

        $menitDiajukan = $this->hitungMenitDiajukan(
            $data['jam_mulai_estimasi'],
            $data['jam_selesai_estimasi'],
        );

        return PengajuanLembur::create([
            'id_karyawan'          => $data['id_karyawan'],
            'id_absensi'           => $data['id_absensi'],
            'tanggal_lembur'       => $tanggalLembur->format('Y-m-d'),
            'jam_mulai_estimasi'   => $data['jam_mulai_estimasi'],
            'jam_selesai_estimasi' => $data['jam_selesai_estimasi'],
            'menit_lembur_diajukan'=> $menitDiajukan,
            'menit_lembur_resmi'   => 0,  // dihitung saat approval
            'alasan_lembur'        => $data['alasan_lembur'],
            'status'               => $masihDalamBatas
                ? PengajuanLembur::STATUS_MENUNGGU
                : PengajuanLembur::STATUS_KADALUARSA,
            'batas_pengajuan'      => $tanggalLembur->copy()->addDay()->format('Y-m-d'),
            'diajukan_pada'        => now(),
        ]);
    }
}