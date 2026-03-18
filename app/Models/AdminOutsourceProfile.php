<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class AdminOutsourceProfile extends Model
{
    protected $table      = 'admin_outsource_profile';
    protected $primaryKey = 'id_profile';

    protected $fillable = [
        'id_pengguna',
        'id_perusahaan',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(PerusahaanOutsource::class, 'id_perusahaan', 'id_perusahaan');
    }
}
