<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Shift
 *
 * @property int    $id_shift
 * @property string $nama_shift
 * @property string $jam_masuk          format HH:MM:SS
 * @property string $jam_pulang         format HH:MM:SS
 * @property int    $durasi_normal_menit default 480 (8 jam)
 * @property string $status             aktif|nonaktif
 */
class Shift extends Model
{
    protected $table      = 'shift';
    protected $primaryKey = 'id_shift';

    protected $fillable = [
        'nama_shift',
        'jam_masuk',
        'jam_pulang',
        'durasi_normal_menit',
        'status',
    ];

    const STATUS_AKTIF    = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';
    const DURASI_DEFAULT  = 480;

    public function jadwalKerja(): HasMany
    {
        return $this->hasMany(JadwalKerja::class, 'id_shift', 'id_shift');
    }
}
