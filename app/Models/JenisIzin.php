<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 */
class JenisIzin extends Model
{
    protected $table      = 'jenis_izin';
    protected $primaryKey = 'id_jenis_izin';

    protected $fillable = [
        'nama_jenis',
        'wajib_dokumen',
        'keterangan',
    ];

    protected $casts = [
        'wajib_dokumen' => 'boolean',
    ];

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanIzin::class, 'id_jenis_izin', 'id_jenis_izin');
    }
}