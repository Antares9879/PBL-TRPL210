<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 */
class JadwalKerja extends Model
{
    protected $table      = 'jadwal_kerja';
    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_planning',
        'id_karyawan',
        'id_shift',
        'tanggal_kerja',
        'is_hari_libur',
    ];

    protected $casts = [
        'tanggal_kerja' => 'date',
        'is_hari_libur' => 'boolean',
    ];

    public function planning(): BelongsTo
    {
        return $this->belongsTo(PlanningKerja::class, 'id_planning', 'id_planning');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'id_shift', 'id_shift');
    }

    public function absensi(): HasOne
    {
        return $this->hasOne(Absensi::class, 'id_jadwal', 'id_jadwal');
    }
}