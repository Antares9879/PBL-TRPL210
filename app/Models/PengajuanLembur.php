<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class PengajuanLembur extends Model
{
    protected $table      = 'pengajuan_lembur';
    protected $primaryKey = 'id_lembur';

    protected $fillable = [
        'id_karyawan',
        'id_absensi',
        'tanggal_lembur',
        'jam_mulai_estimasi',
        'jam_selesai_estimasi',
        'menit_lembur_diajukan',
        'menit_lembur_resmi',
        'alasan_lembur',
        'status',
        'catatan_penolakan',
        'batas_pengajuan',
        'diajukan_pada',
        'diproses_oleh',
        'waktu_proses',
    ];

    protected $casts = [
        'tanggal_lembur'  => 'date',
        'batas_pengajuan' => 'date',
        'diajukan_pada'   => 'datetime',
        'waktu_proses'    => 'datetime',
    ];

    const STATUS_MENUNGGU   = 'menunggu';
    const STATUS_DISETUJUI  = 'disetujui';
    const STATUS_DITOLAK    = 'ditolak';
    const STATUS_KADALUARSA = 'kadaluarsa';

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class, 'id_absensi', 'id_absensi');
    }

    public function prosesor(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diproses_oleh', 'id_pengguna');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }

    /**
     * Cek apakah pengajuan masih dalam batas waktu H+1.
     */
    public function masihDalamBatas(): bool
    {
        return now()->startOfDay()->lte($this->batas_pengajuan);
    }
}