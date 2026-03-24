<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AbsensiService
 *
 * Memusatkan seluruh logika bisnis kalkulasi waktu absensi:
 *   - Menit kerja normal (maks. 480 menit / 8 jam per SKPPL)
 *   - Menit telat
 *   - Menit pulang cepat
 *   - Menit kelebihan (Pending Lembur)
 *
 * Semua kalkulasi menggunakan jadwal_kerja sebagai referensi.
 * Tidak ada kalkulasi rupiah/upah sesuai batasan sistem.
 */
class AbsensiService
{
    /** Durasi kerja normal maksimum dalam menit (8 jam sesuai SKPPL 2.4) */
    private const MENIT_KERJA_NORMAL_MAKS = 480;

    // ════════════════════════════════════════════════════════════════════════
    //  CHECK-IN (F01)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Proses check-in karyawan.
     *
     * Flow:
     * 1. Pastikan karyawan belum check-in hari ini.
     * 2. Validasi GPS (dilakukan di controller sebelum memanggil service ini).
     * 3. Cari jadwal aktif hari ini untuk karyawan.
     * 4. Hitung menit telat = max(0, waktu_check_in - jam_masuk_jadwal).
     * 5. Simpan record absensi dengan status 'pending' (menunggu validasi Admin).
     *
     * @param  Karyawan $karyawan
     * @param  float    $lat            Koordinat latitude dari GPS
     * @param  float    $lng            Koordinat longitude dari GPS
     * @param  bool     $lokasiValid    Hasil validasi GPS dari GpsValidationService
     * @return array{absensi: Absensi, menit_telat: int}
     * @throws \RuntimeException
     */
    public function checkIn(
        Karyawan $karyawan,
        float $lat,
        float $lng,
        bool $lokasiValid,
    ): array {
        // Guard: sudah check-in hari ini?
        $sudahCheckIn = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->whereDate('tanggal_absensi', today())
            ->whereNotNull('waktu_check_in')
            ->exists();

        if ($sudahCheckIn) {
            throw new \RuntimeException('Anda sudah melakukan absensi masuk hari ini.');
        }

        // Cari jadwal aktif hari ini
        $jadwal = JadwalKerja::with('shift')
            ->where('id_karyawan', $karyawan->id_karyawan)
            ->whereDate('tanggal_kerja', today())
            ->where('is_hari_libur', false)
            ->first();

        if (! $jadwal) {
            throw new \RuntimeException('Tidak ada jadwal kerja aktif untuk hari ini.');
        }

        $waktuCheckIn = now();
        $menit_telat  = $this->hitungMenitTelat($waktuCheckIn, $jadwal->shift->jam_masuk);

        $absensi = Absensi::create([
            'id_karyawan'        => $karyawan->id_karyawan,
            'id_jadwal'          => $jadwal->id_jadwal,
            'tanggal_absensi'    => today(),
            'waktu_check_in'     => $waktuCheckIn,
            'latitude_check_in'  => $lat,
            'longitude_check_in' => $lng,
            'is_lokasi_valid_in' => $lokasiValid,
            'menit_kerja_normal' => 0,
            'menit_telat'        => $menit_telat,
            'menit_pulang_cepat' => 0,
            'menit_kelebihan'    => 0,
            'status_kehadiran'   => Absensi::STATUS_PENDING,
            'status_validasi'    => Absensi::VALIDASI_MENUNGGU,
        ]);

        return [
            'absensi'     => $absensi,
            'menit_telat' => $menit_telat,
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CHECK-OUT (F01)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Proses check-out karyawan.
     *
     * Flow:
     * 1. Cari record absensi check-in hari ini.
     * 2. Validasi GPS.
     * 3. Hitung menit kerja normal (maks. 480 menit).
     * 4. Hitung menit pulang cepat ATAU menit kelebihan (Pending Lembur).
     * 5. Update record absensi.
     * 6. Kirim notifikasi Pending Lembur jika ada kelebihan waktu.
     *
     * @return array{absensi: Absensi, pending_lembur: bool, menit_kelebihan: int}
     * @throws \RuntimeException
     */
    public function checkOut(
        Karyawan $karyawan,
        float $lat,
        float $lng,
        bool $lokasiValid,
    ): array {
        $absensi = Absensi::with('jadwal.shift')
            ->where('id_karyawan', $karyawan->id_karyawan)
            ->whereDate('tanggal_absensi', today())
            ->whereNotNull('waktu_check_in')
            ->whereNull('waktu_check_out')
            ->first();

        if (! $absensi) {
            throw new \RuntimeException('Check-in belum dilakukan hari ini atau Anda sudah check-out.');
        }

        $shift          = $absensi->jadwal->shift;
        $waktuCheckOut  = now();
        $jamMasukJadwal = Carbon::parse(today()->format('Y-m-d') . ' ' . $shift->jam_masuk);
        $jamPulangJadwal= Carbon::parse(today()->format('Y-m-d') . ' ' . $shift->jam_pulang);

        // Handle shift malam: jam_pulang keesokan harinya
        if ($jamPulangJadwal->lte($jamMasukJadwal)) {
            $jamPulangJadwal->addDay();
        }

        // Titik mulai efektif = jam_masuk jadwal (telat tidak tambah waktu kerja)
        $checkInEfektif = Carbon::parse($absensi->waktu_check_in);
        if ($checkInEfektif->lt($jamMasukJadwal)) {
            $checkInEfektif = $jamMasukJadwal;
        }

        $menitKerjaTotal = max(0, $checkInEfektif->diffInMinutes($waktuCheckOut));
        $menitNormalAktual = (int) $jamMasukJadwal->diffInMinutes($jamPulangJadwal);

        // Hitung komponen waktu
        $menitKerjaNormal  = min($menitKerjaTotal, self::MENIT_KERJA_NORMAL_MAKS);
        $menitPulangCepat  = 0;
        $menitKelebihan    = 0;

        if ($waktuCheckOut->lt($jamPulangJadwal)) {
            // Pulang lebih awal dari jadwal
            $menitPulangCepat = (int) $waktuCheckOut->diffInMinutes($jamPulangJadwal);
        } elseif ($waktuCheckOut->gt($jamPulangJadwal)) {
            // Melewati jam pulang jadwal → kelebihan → Pending Lembur
            $menitKelebihan = (int) $jamPulangJadwal->diffInMinutes($waktuCheckOut);
        }

        $absensi->update([
            'waktu_check_out'     => $waktuCheckOut,
            'latitude_check_out'  => $lat,
            'longitude_check_out' => $lng,
            'is_lokasi_valid_out' => $lokasiValid,
            'menit_kerja_normal'  => $menitKerjaNormal,
            'menit_pulang_cepat'  => $menitPulangCepat,
            'menit_kelebihan'     => $menitKelebihan,
        ]);

        return [
            'absensi'         => $absensi->fresh(),
            'pending_lembur'  => $menitKelebihan > 0,
            'menit_kelebihan' => $menitKelebihan,
            'batas_lembur'    => $menitKelebihan > 0
                ? today()->addDay()->format('Y-m-d') // H+1
                : null,
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS KALKULASI
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Hitung menit telat.
     * Jika check-in sebelum jam masuk jadwal, telat = 0.
     */
    private function hitungMenitTelat(Carbon $waktuCheckIn, string $jamMasukJadwal): int
    {
        $jadwalMasuk = Carbon::parse(today()->format('Y-m-d') . ' ' . $jamMasukJadwal);

        if ($waktuCheckIn->lte($jadwalMasuk)) {
            return 0;
        }

        return (int) $jadwalMasuk->diffInMinutes($waktuCheckIn);
    }
}