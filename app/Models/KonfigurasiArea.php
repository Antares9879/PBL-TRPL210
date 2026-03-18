<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class KonfigurasiArea extends Model
{
    protected $table      = 'konfigurasi_area';
    protected $primaryKey = 'id_konfigurasi';

    protected $fillable = [
        'nama_area',
        'latitude_pusat',
        'longitude_pusat',
        'radius_meter',
        'is_aktif',
        'diubah_oleh',
    ];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'latitude_pusat'  => 'float',
        'longitude_pusat' => 'float',
    ];

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diubah_oleh', 'id_pengguna');
    }
}
