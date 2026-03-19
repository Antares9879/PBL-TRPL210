<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class RekapBulanan extends Model
{
    protected $table      = 'rekap_bulanan';
    protected $primaryKey = 'id_rekap';

    protected $fillable = [
        'id_karyawan',
        'periode_bulan',
        'periode_tahun',
        'total_hari_kerja',
        'total_hari_hadir',
        'total_hari_izin',
        'total_hari_alpa',
        'total_menit_normal',
        'total_menit_lembur',
        'total_menit_telat',
        'total_menit_pulang_cepat',
        'status_rekap',
        'dibuat_oleh',
        'ditetapkan_pada',
    ];

    protected $casts = [
        'ditetapkan_pada' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_FINAL = 'final';

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }
}