<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class Notifikasi extends Model
{
    protected $table      = 'notifikasi';
    protected $primaryKey = 'id_notifikasi';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_penerima',
        'id_pengirim',
        'judul',
        'isi',
        'jenis',
        'id_referensi',
        'is_dibaca',
        'dibaca_pada',
    ];

    protected $casts = [
        'is_dibaca'  => 'boolean',
        'dibaca_pada'=> 'datetime',
    ];

    const JENIS_ABSENSI  = 'absensi';
    const JENIS_LEMBUR   = 'lembur';
    const JENIS_IZIN     = 'izin';
    const JENIS_PLANNING = 'planning';
    const JENIS_SISTEM   = 'sistem';

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_penerima', 'id_pengguna');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengirim', 'id_pengguna');
    }
}