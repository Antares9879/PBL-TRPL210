<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Model Absensi
 *
 * Menyimpan data check-in dan check-out karyawan harian
 * beserta kalkulasi menit kerja normal, telat, pulang cepat, dan kelebihan.
 *
 * @property int         $id_absensi
 * @property int         $id_karyawan
 * @property int         $id_jadwal
 * @property \Carbon\Carbon $tanggal_absensi
 * @property \Carbon\Carbon|null $waktu_check_in
 * @property float|null  $latitude_check_in
 * @property float|null  $longitude_check_in
 * @property bool|null   $is_lokasi_valid_in
 * @property \Carbon\Carbon|null $waktu_check_out
 * @property float|null  $latitude_check_out
 * @property float|null  $longitude_check_out
 * @property bool|null   $is_lokasi_valid_out
 * @property int         $menit_kerja_normal
 * @property int         $menit_telat
 * @property int         $menit_pulang_cepat
 * @property int         $menit_kelebihan
 * @property string      $status_kehadiran   hadir|izin|alpa|pending
 * @property string      $status_validasi    menunggu|disetujui|ditolak
 * @property string|null $catatan_penolakan
 * @property int|null    $divalidasi_oleh
 * @property \Carbon\Carbon|null $waktu_validasi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Support\Collection $pengajuan_lembur_safe
 * @property-read \App\Models\JadwalKerja $jadwal
 * @property-read \App\Models\Karyawan $karyawan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PengajuanLembur> $pengajuanLembur
 * @property-read int|null $pengajuan_lembur_count
 * @property-read \App\Models\Pengguna|null $validator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi hariIni()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi menungguValidasi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereCatatanPenolakan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereDivalidasiOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereIdAbsensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereIdJadwal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereIsLokasiValidIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereIsLokasiValidOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereLatitudeCheckIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereLatitudeCheckOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereLongitudeCheckIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereLongitudeCheckOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereMenitKelebihan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereMenitKerjaNormal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereMenitPulangCepat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereMenitTelat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereStatusKehadiran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereStatusValidasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereTanggalAbsensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereWaktuCheckIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereWaktuCheckOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absensi whereWaktuValidasi($value)
 */
	class Absensi extends \Eloquent {}
}

namespace App\Models{
/**
 * Model AdminOutsourceProfile
 *
 * Extension table untuk role admin_outsource (Table Per Type).
 * Menyimpan referensi ke perusahaan outsource yang dikelola admin.
 * Relasi 1:1 dengan tabel pengguna.
 *
 * @property int $id_profile
 * @property int $id_pengguna
 * @property int $id_perusahaan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pengguna $pengguna
 * @property-read \App\Models\PerusahaanOutsource $perusahaan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile whereIdPengguna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile whereIdPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile whereIdProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminOutsourceProfile whereUpdatedAt($value)
 */
	class AdminOutsourceProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * Model AuditLog
 *
 * Menyimpan jejak seluruh aksi kritis (approve/reject/create/update/deactivate/upload)
 * untuk transparansi HR dan keperluan audit.
 *
 * @property int         $id_log
 * @property int         $id_pengguna
 * @property string      $role_pelaku     admin_outsource|user_departemen|hr|super_admin|sistem
 * @property string      $jenis_data      absensi|lembur|izin|planning|akun|master_data|konfigurasi
 * @property int         $id_referensi    PK dari data yang diaksi
 * @property string      $aksi            approve|reject|create|update|deactivate|upload
 * @property string|null $catatan
 * @property array|null  $data_sebelum    snapshot JSON sebelum perubahan
 * @property array|null  $data_sesudah    snapshot JSON sesudah perubahan
 * @property string|null $ip_address
 * @property string      $waktu_aksi
 * @property-read \App\Models\Pengguna $pelaku
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAksi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereDataSebelum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereDataSesudah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIdLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIdPengguna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIdReferensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereJenisData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereRolePelaku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereWaktuAksi($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * Model Departemen
 *
 * @property int    $id_departemen
 * @property string $nama_departemen
 * @property string $kode_departemen
 * @property string $status  aktif|nonaktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Karyawan> $karyawan
 * @property-read int|null $karyawan_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserDepartemenProfile> $userDepartemenProfiles
 * @property-read int|null $user_departemen_profiles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereIdDepartemen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereKodeDepartemen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereNamaDepartemen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departemen whereUpdatedAt($value)
 */
	class Departemen extends \Eloquent {}
}

namespace App\Models{
/**
 * Model DokumenIzin
 *
 * Menyimpan metadata file dokumen pendukung pengajuan izin.
 * File fisik disimpan di storage/app/private/dokumen-izin/{id_izin}/.
 * Akses file wajib lewat controller yang memverifikasi hak akses.
 *
 * @property int    $id_dokumen
 * @property int    $id_izin
 * @property string $nama_file
 * @property string $path_file
 * @property string $tipe_file
 * @property int    $ukuran_kb
 * @property int    $diunggah_oleh
 * @property string $diunggah_pada
 * @property-read \App\Models\PengajuanIzin $izin
 * @property-read \App\Models\Pengguna $pengunggah
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereDiunggahOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereDiunggahPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereIdDokumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereIdIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereNamaFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin wherePathFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereTipeFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DokumenIzin whereUkuranKb($value)
 */
	class DokumenIzin extends \Eloquent {}
}

namespace App\Models{
/**
 * Model JadwalKerja
 *
 * Detail jadwal kerja harian per karyawan per planning.
 * Menjadi referensi utama untuk kalkulasi menit kerja & lembur.
 *
 * @property int    $id_jadwal
 * @property int    $id_planning
 * @property int    $id_karyawan
 * @property int    $id_shift
 * @property string $tanggal_kerja
 * @property bool   $is_hari_libur
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Absensi|null $absensi
 * @property-read \App\Models\Karyawan $karyawan
 * @property-read \App\Models\PlanningKerja $planning
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereIdJadwal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereIdPlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereIdShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereIsHariLibur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereTanggalKerja($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JadwalKerja whereUpdatedAt($value)
 */
	class JadwalKerja extends \Eloquent {}
}

namespace App\Models{
/**
 * Model JenisIzin
 *
 * Lookup tabel jenis izin tidak masuk.
 * Field wajib_dokumen menentukan apakah upload wajib dilakukan.
 *
 * @property int         $id_jenis_izin
 * @property string      $nama_jenis
 * @property bool        $wajib_dokumen
 * @property string|null $keterangan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PengajuanIzin> $pengajuan
 * @property-read int|null $pengajuan_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereIdJenisIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereNamaJenis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisIzin whereWajibDokumen($value)
 */
	class JenisIzin extends \Eloquent {}
}

namespace App\Models{
/**
 * Model Karyawan
 *
 * Extension table untuk role karyawan (Table Per Type).
 * Menyimpan data profil lengkap karyawan outsource.
 * Relasi 1:1 dengan tabel pengguna, dikelola oleh Admin Outsource.
 *
 * @property int    $id_karyawan
 * @property int    $id_pengguna
 * @property string $nik
 * @property string $nomor_karyawan
 * @property string $nama_lengkap
 * @property string $posisi
 * @property int    $id_perusahaan
 * @property int    $id_departemen
 * @property string $tanggal_bergabung
 * @property string $status
 * @property int    $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Absensi> $absensi
 * @property-read int|null $absensi_count
 * @property-read \App\Models\Departemen $departemen
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalKerja> $jadwal
 * @property-read int|null $jadwal_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PengajuanIzin> $pengajuanIzin
 * @property-read int|null $pengajuan_izin_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PengajuanLembur> $pengajuanLembur
 * @property-read int|null $pengajuan_lembur_count
 * @property-read \App\Models\Pengguna $pengguna
 * @property-read \App\Models\PerusahaanOutsource $perusahaan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RekapBulanan> $rekapBulanan
 * @property-read int|null $rekap_bulanan_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereIdDepartemen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereIdPengguna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereIdPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereNamaLengkap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereNomorKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan wherePosisi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereTanggalBergabung($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Karyawan whereUpdatedAt($value)
 */
	class Karyawan extends \Eloquent {}
}

namespace App\Models{
/**
 * Model KonfigurasiArea
 *
 * Menyimpan konfigurasi radius GPS untuk validasi lokasi absensi.
 * Hanya satu area yang boleh is_aktif = true dalam satu waktu.
 *
 * @property int        $id_konfigurasi
 * @property string     $nama_area
 * @property float      $latitude_pusat
 * @property float      $longitude_pusat
 * @property int        $radius_meter
 * @property bool       $is_aktif
 * @property int        $diubah_oleh      id_pengguna Super Admin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pengguna $diubahOleh
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereDiubahOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereIdKonfigurasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereIsAktif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereLatitudePusat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereLongitudePusat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereNamaArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereRadiusMeter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KonfigurasiArea whereUpdatedAt($value)
 */
	class KonfigurasiArea extends \Eloquent {}
}

namespace App\Models{
/**
 * Model Notifikasi
 *
 * Notifikasi in-app untuk setiap pengguna.
 * Tidak ada updated_at — notifikasi tidak diedit setelah dibuat.
 *
 * @property int         $id_notifikasi
 * @property int         $id_penerima
 * @property int|null    $id_pengirim
 * @property string      $judul
 * @property string      $isi
 * @property string      $jenis         absensi|lembur|izin|planning|sistem
 * @property int|null    $id_referensi
 * @property bool        $is_dibaca
 * @property string|null $dibaca_pada
 * @property string      $created_at
 * @property-read \App\Models\Pengguna $penerima
 * @property-read \App\Models\Pengguna|null $pengirim
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereDibacaPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIdNotifikasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIdPenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIdPengirim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIdReferensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIsDibaca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereIsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereJenis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifikasi whereJudul($value)
 */
	class Notifikasi extends \Eloquent {}
}

namespace App\Models{
/**
 * Model PengajuanIzin
 *
 * Menyimpan pengajuan izin tidak masuk dari karyawan.
 * Mendukung izin single-day maupun multi-day (range tanggal).
 * Dokumen pendukung disimpan di tabel dokumen_izin.
 *
 * Logika range tanggal:
 *   - tanggal_izin          = tanggal mulai (wajib)
 *   - tanggal_selesai_izin  = tanggal akhir (nullable; NULL berarti izin 1 hari)
 *   - Izin 1 hari : tanggal_izin == tanggal_selesai_izin (atau tanggal_selesai_izin = null)
 *   - Izin multi-day: tanggal_selesai_izin > tanggal_izin
 *
 * @property int         $id_izin
 * @property int         $id_karyawan
 * @property int         $id_jenis_izin
 * @property string      $tanggal_izin           tanggal mulai
 * @property string|null $tanggal_selesai_izin   tanggal akhir; null = 1 hari
 * @property string|null $keterangan
 * @property string      $status              menunggu|disetujui|ditolak
 * @property string|null $catatan_penolakan
 * @property string      $status_dokumen      belum_upload|sudah_upload|lengkap|tidak_lengkap
 * @property string|null $catatan_dokumen
 * @property string      $diajukan_pada
 * @property int|null    $divalidasi_admin
 * @property string|null $waktu_validasi_admin
 * @property int|null    $diverifikasi_hr
 * @property string|null $waktu_verifikasi_hr
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DokumenIzin> $dokumen
 * @property-read int|null $dokumen_count
 * @property-read \App\Models\JenisIzin $jenisIzin
 * @property-read \App\Models\Karyawan $karyawan
 * @property-read \App\Models\Pengguna|null $validatorAdmin
 * @property-read \App\Models\Pengguna|null $verifikatorHr
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin menunggu()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin overlapDengan(string $tanggalMulai, string $tanggalSelesai)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereCatatanDokumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereCatatanPenolakan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereDiajukanPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereDivalidasiAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereDiverifikasiHr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereIdIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereIdJenisIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereStatusDokumen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereTanggalIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereTanggalSelesaiIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereWaktuValidasiAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanIzin whereWaktuVerifikasiHr($value)
 */
	class PengajuanIzin extends \Eloquent {}
}

namespace App\Models{
/**
 * Model PengajuanLembur
 *
 * Menyimpan pengajuan lembur karyawan (bisa retroaktif maks. H+1).
 * Menit lembur berstatus Pending hingga disetujui User Departemen.
 *
 * @property int         $id_lembur
 * @property int         $id_karyawan
 * @property int         $id_absensi
 * @property string      $tanggal_lembur
 * @property string      $jam_mulai_estimasi
 * @property string      $jam_selesai_estimasi
 * @property int         $menit_lembur_diajukan
 * @property int         $menit_lembur_resmi
 * @property string      $alasan_lembur
 * @property string      $status            menunggu|disetujui|ditolak|kadaluarsa
 * @property string|null $catatan_penolakan
 * @property string      $batas_pengajuan
 * @property string      $diajukan_pada
 * @property int|null    $diproses_oleh
 * @property string|null $waktu_proses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Absensi $absensi
 * @property-read \App\Models\Karyawan $karyawan
 * @property-read \App\Models\Pengguna|null $prosesor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur menunggu()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereAlasanLembur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereBatasPengajuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereCatatanPenolakan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereDiajukanPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereDiprosesOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereIdAbsensi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereIdLembur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereJamMulaiEstimasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereJamSelesaiEstimasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereMenitLemburDiajukan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereMenitLemburResmi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereTanggalLembur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PengajuanLembur whereWaktuProses($value)
 */
	class PengajuanLembur extends \Eloquent {}
}

namespace App\Models{
/**
 * Model Pengguna
 *
 * Tabel autentikasi utama untuk seluruh role di sistem E-Outsourcing.
 * Mengimplementasikan Authenticatable agar dapat digunakan oleh Laravel Auth.
 *
 * Arsitektur Table Per Type:
 *   - role karyawan        → relasi hasOne(Karyawan)
 *   - role admin_outsource → relasi hasOne(AdminOutsourceProfile)
 *   - role user_departemen → relasi hasOne(UserDepartemenProfile)
 *   - role hr, super_admin → tidak ada extension table
 *
 * @property int         $id_pengguna
 * @property string      $nama_lengkap
 * @property string      $email
 * @property string      $password_hash
 * @property string      $role           super_admin|hr|user_departemen|admin_outsource|karyawan
 * @property string      $status         aktif|nonaktif
 * @property string|null $last_login
 * @property string      $created_at
 * @property string      $updated_at
 * @property-read \App\Models\AdminOutsourceProfile|null $adminOutsourceProfile
 * @property-read \App\Models\Karyawan|null $karyawan
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\UserDepartemenProfile|null $userDepartemenProfile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereIdPengguna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereNamaLengkap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna wherePasswordHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pengguna whereUpdatedAt($value)
 */
	class Pengguna extends \Eloquent {}
}

namespace App\Models{
/**
 * Model PerusahaanOutsource
 *
 * @property int    $id_perusahaan
 * @property string $nama_perusahaan
 * @property string $alamat
 * @property string $no_telepon
 * @property string $email
 * @property string $status  aktif|nonaktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AdminOutsourceProfile> $adminProfiles
 * @property-read int|null $admin_profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Karyawan> $karyawan
 * @property-read int|null $karyawan_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereIdPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereNamaPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereNoTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerusahaanOutsource whereUpdatedAt($value)
 */
	class PerusahaanOutsource extends \Eloquent {}
}

namespace App\Models{
/**
 * Model PlanningKerja
 *
 * Header planning kerja per periode per perusahaan outsource.
 * Detail jadwal per karyawan ada di tabel jadwal_kerja.
 *
 * @property int    $id_planning
 * @property int    $id_perusahaan
 * @property int    $periode_bulan    1–12
 * @property int    $periode_tahun
 * @property string $status           draft|aktif|diperbarui
 * @property int    $versi
 * @property int    $dibuat_oleh      id_pengguna Admin Outsource
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $periode_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalKerja> $jadwal
 * @property-read int|null $jadwal_count
 * @property-read \App\Models\Pengguna $pembuatan
 * @property-read \App\Models\PerusahaanOutsource $perusahaan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereDibuatOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereIdPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereIdPlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja wherePeriodeBulan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja wherePeriodeTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanningKerja whereVersi($value)
 */
	class PlanningKerja extends \Eloquent {}
}

namespace App\Models{
/**
 * Model RekapBulanan
 *
 * Tabel agregasi absensi bulanan per karyawan.
 * Hanya berisi data waktu dalam satuan menit (non-payroll).
 *
 * @property int    $id_rekap
 * @property int    $id_karyawan
 * @property int    $periode_bulan       1–12
 * @property int    $periode_tahun
 * @property int    $total_hari_kerja
 * @property int    $total_hari_hadir
 * @property int    $total_hari_izin
 * @property int    $total_hari_alpa
 * @property int    $total_menit_normal
 * @property int    $total_menit_lembur
 * @property int    $total_menit_telat
 * @property int    $total_menit_pulang_cepat
 * @property string $status_rekap        draft|final
 * @property int|null $dibuat_oleh
 * @property string|null $ditetapkan_pada
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Karyawan $karyawan
 * @property-read \App\Models\Pengguna|null $pembuat
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereDibuatOleh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereDitetapkanPada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereIdKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereIdRekap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan wherePeriodeBulan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan wherePeriodeTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereStatusRekap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalHariAlpa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalHariHadir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalHariIzin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalHariKerja($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalMenitLembur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalMenitNormal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalMenitPulangCepat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereTotalMenitTelat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RekapBulanan whereUpdatedAt($value)
 */
	class RekapBulanan extends \Eloquent {}
}

namespace App\Models{
/**
 * Model Shift
 *
 * @property int    $id_shift
 * @property string $nama_shift
 * @property string $jam_masuk          format HH:MM:SS
 * @property string $jam_pulang         format HH:MM:SS
 * @property int    $durasi_normal_menit default 480 (8 jam)
 * @property string $status             aktif|nonaktif
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalKerja> $jadwalKerja
 * @property-read int|null $jadwal_kerja_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDurasiNormalMenit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIdShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereJamMasuk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereJamPulang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereNamaShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 */
	class Shift extends \Eloquent {}
}

namespace App\Models{
/**
 * Model UserDepartemenProfile
 *
 * Extension table untuk role user_departemen (Table Per Type).
 * Menyimpan referensi ke departemen yang menjadi tanggung jawab user ini.
 * Relasi 1:1 dengan tabel pengguna.
 *
 * @property int $id_profile
 * @property int $id_pengguna
 * @property int $id_departemen
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Departemen $departemen
 * @property-read \App\Models\Pengguna $pengguna
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile whereIdDepartemen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile whereIdPengguna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile whereIdProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartemenProfile whereUpdatedAt($value)
 */
	class UserDepartemenProfile extends \Eloquent {}
}

