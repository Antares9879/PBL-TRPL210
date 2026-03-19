<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pengguna;

/**
 * NotifikasiService
 *
 * Menulis entri ke tabel notifikasi setelah perubahan status.
 * Satu metode per jenis notifikasi agar mudah di-maintain.
 */
class NotifikasiService
{
    /**
     * Kirim notifikasi ke satu penerima.
     */
    public static function kirim(
        int $idPenerima,
        string $judul,
        string $isi,
        string $jenis,
        ?int $idPengirim  = null,
        ?int $idReferensi = null,
    ): Notifikasi {
        return Notifikasi::create([
            'id_penerima'  => $idPenerima,
            'id_pengirim'  => $idPengirim,
            'judul'        => $judul,
            'isi'          => $isi,
            'jenis'        => $jenis,
            'id_referensi' => $idReferensi,
            'is_dibaca'    => false,
        ]);
    }

    /**
     * Kirim notifikasi status izin ke karyawan.
     */
    public static function izinDiproses(
        int $idKaryawan,
        string $statusBaru,
        ?string $catatan = null,
        ?int $idIzin     = null,
        ?int $idPengirim = null,
    ): void {
        $judul = $statusBaru === 'disetujui'
            ? 'Pengajuan izin Anda disetujui'
            : 'Pengajuan izin Anda ditolak';

        $isi = $catatan
            ? "{$judul}. Catatan: {$catatan}"
            : $judul . '.';

        static::kirim(
            idPenerima: $idKaryawan,
            judul:      $judul,
            isi:        $isi,
            jenis:      Notifikasi::JENIS_IZIN,
            idPengirim: $idPengirim,
            idReferensi: $idIzin,
        );
    }

    /**
     * Kirim notifikasi absensi baru ke Admin Outsource.
     */
    public static function absensiBaruKeAdmin(
        int $idAdmin,
        string $namaKaryawan,
        string $tanggal,
        int $idAbsensi,
    ): void {
        static::kirim(
            idPenerima:  $idAdmin,
            judul:       "Absensi baru dari {$namaKaryawan}",
            isi:         "{$namaKaryawan} melakukan absensi pada {$tanggal}. Menunggu validasi.",
            jenis:       Notifikasi::JENIS_ABSENSI,
            idReferensi: $idAbsensi,
        );
    }

    /**
     * Kirim notifikasi hasil validasi absensi ke karyawan.
     */
    public static function absensiDivalidasi(
        int $idKaryawan,
        string $statusBaru,
        string $tanggal,
        ?string $catatan = null,
        ?int $idAbsensi  = null,
        ?int $idPengirim = null,
    ): void {
        $judul = $statusBaru === 'disetujui'
            ? "Absensi {$tanggal} Anda disetujui"
            : "Absensi {$tanggal} Anda ditolak";

        $isi = $catatan ? "{$judul}. Catatan: {$catatan}" : $judul . '.';

        static::kirim(
            idPenerima:  $idKaryawan,
            judul:       $judul,
            isi:         $isi,
            jenis:       Notifikasi::JENIS_ABSENSI,
            idPengirim:  $idPengirim,
            idReferensi: $idAbsensi,
        );
    }

    /**
     * Kirim notifikasi planning baru ke semua karyawan yang terdaftar.
     *
     * @param int[] $idKaryawanList
     */
    public static function planningBaru(
        array $idKaryawanList,
        string $periodeLabel,
        int $idPlanning,
        int $idPengirim,
    ): void {
        foreach ($idKaryawanList as $idKaryawan) {
            static::kirim(
                idPenerima:  $idKaryawan,
                judul:       "Planning kerja {$periodeLabel} tersedia",
                isi:         "Admin outsource telah membuat jadwal kerja untuk periode {$periodeLabel}. Cek jadwal Anda.",
                jenis:       Notifikasi::JENIS_PLANNING,
                idPengirim:  $idPengirim,
                idReferensi: $idPlanning,
            );
        }
    }
}