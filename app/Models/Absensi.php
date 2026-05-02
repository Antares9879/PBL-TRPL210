<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model Absensi
 *
 * Menyimpan data check-in dan check-out karyawan harian
 * beserta kalkulasi menit kerja normal, telat, pulang cepat, dan kelebihan.
 * Status kehadiran: hadir, izin, alpa, pending
 * Status validasi: menunggu, disetujui, ditolak
 * @property int         $id_absensi
 * @property int         $id_karyawan
 * @property int         $id_jadwal
 * @property \Carbon\Carbon|null $tanggal_absensi
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
 */
class Absensi extends Model
{
    protected $table      = 'absensi';
    protected $primaryKey = 'id_absensi';

    protected $fillable = [
        'id_karyawan',
        'id_jadwal',
        'tanggal_absensi',
        'waktu_check_in',
        'latitude_check_in',
        'longitude_check_in',
        'is_lokasi_valid_in',
        'waktu_check_out',
        'latitude_check_out',
        'longitude_check_out',
        'is_lokasi_valid_out',
        'menit_kerja_normal',
        'menit_telat',
        'menit_pulang_cepat',
        'menit_kelebihan',
        'status_kehadiran',
        'status_validasi',
        'catatan_penolakan',
        'divalidasi_oleh',
        'waktu_validasi',
    ];

    protected $casts = [
        'tanggal_absensi'     => 'date',
        'waktu_check_in'      => 'datetime',
        'waktu_check_out'     => 'datetime',
        'waktu_validasi'      => 'datetime',
        'latitude_check_in'   => 'float',
        'longitude_check_in'  => 'float',
        'latitude_check_out'  => 'float',
        'longitude_check_out' => 'float',
        'is_lokasi_valid_in'  => 'boolean',
        'is_lokasi_valid_out' => 'boolean',
    ];

    // ── Konstanta status ──────────────────────────────────────────────────────

    const STATUS_HADIR   = 'hadir';
    const STATUS_IZIN    = 'izin';
    const STATUS_ALPA    = 'alpa';
    const STATUS_PENDING = 'pending';

    const VALIDASI_MENUNGGU  = 'menunggu';
    const VALIDASI_DISETUJUI = 'disetujui';
    const VALIDASI_DITOLAK   = 'ditolak';

    // ── Relasi ────────────────────────────────────────────────────────────────

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalKerja::class, 'id_jadwal', 'id_jadwal');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'divalidasi_oleh', 'id_pengguna');
    }

    public function pengajuanLembur(): HasMany
    {
        return $this->hasMany(PengajuanLembur::class, 'id_absensi', 'id_absensi');
    }

    /**
     * Mengembalikan koleksi pengajuanLembur yang aman (tidak pernah null).
     * Diakses via $absensi->pengajuan_lembur_safe
     */
    public function getPengajuanLemburSafeAttribute(): \Illuminate\Support\Collection
    {
        return $this->relationLoaded('pengajuanLembur')
            ? $this->getRelation('pengajuanLembur')
            : collect();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    
    public function scopeMenungguValidasi(Builder $query): Builder
    {
        return $query->where('status_validasi', self::VALIDASI_MENUNGGU);
    }

    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal_absensi', today());
    }
}