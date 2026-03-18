<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model UserDepartemenProfile
 *
 * Extension table untuk role user_departemen (Table Per Type).
 * Menyimpan referensi ke departemen yang menjadi tanggung jawab user ini.
 * Relasi 1:1 dengan tabel pengguna.
 *
 * @property int $id_profile
 * @property int $id_pengguna
 * @property int $id_departemen
 */
class UserDepartemenProfile extends Model
{
    protected $table      = 'user_departemen_profile';
    protected $primaryKey = 'id_profile';

    protected $fillable = [
        'id_pengguna',
        'id_departemen',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'id_departemen', 'id_departemen');
    }
}
