<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property \Carbon\Carbon $tanggal_bergabung
 * @property string $status
 * @property int    $created_by
 */
class Karyawan extends Model
{
    protected $table      = 'karyawan';
    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'id_pengguna',
        'nik',
        'nomor_karyawan',
        'nama_lengkap',
        'posisi',
        'id_perusahaan',
        'id_departemen',
        'tanggal_bergabung',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
    ];

    // ── Relasi BelongsTo (sudah ada sebelumnya) ───────────────────────────────

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(PerusahaanOutsource::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'id_departemen', 'id_departemen');
    }

    // ── Relasi HasMany (ditambahkan) ──────────────────────────────────────────

    /**
     * Seluruh record absensi karyawan ini.
     * Digunakan di: AbsensiApiController, RiwayatAbsensiApiController, AbsensiService
     */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'id_karyawan', 'id_karyawan');
    }

    /**
     * Seluruh jadwal kerja harian karyawan ini (dari semua planning).
     * Digunakan di: JadwalApiController, KaryawanApiController (cek jadwal aktif sebelum nonaktifkan)
     */
    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKerja::class, 'id_karyawan', 'id_karyawan');
    }

    /**
     * Seluruh pengajuan izin karyawan ini.
     * Digunakan di: IzinApiController, ValidasiAbsensiApiController
     */
    public function pengajuanIzin(): HasMany
    {
        return $this->hasMany(PengajuanIzin::class, 'id_karyawan', 'id_karyawan');
    }

    /**
     * Seluruh pengajuan lembur karyawan ini.
     * Digunakan di: LemburApiController, RiwayatAbsensiApiController
     */
    public function pengajuanLembur(): HasMany
    {
        return $this->hasMany(PengajuanLembur::class, 'id_karyawan', 'id_karyawan');
    }

    /**
     * Seluruh rekap bulanan karyawan ini.
     * Digunakan di: RiwayatAbsensiApiController (ringkasan), HR (F13–F16)
     */
    public function rekapBulanan(): HasMany
    {
        return $this->hasMany(RekapBulanan::class, 'id_karyawan', 'id_karyawan');
    }
}
