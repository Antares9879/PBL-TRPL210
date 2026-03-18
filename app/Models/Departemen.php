<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Departemen
 *
 * @property int    $id_departemen
 * @property string $nama_departemen
 * @property string $kode_departemen
 * @property string $status  aktif|nonaktif
 */
class Departemen extends Model
{
    protected $table      = 'departemen';
    protected $primaryKey = 'id_departemen';

    protected $fillable = [
        'nama_departemen',
        'kode_departemen',
        'status',
    ];

    const STATUS_AKTIF    = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';

    public function userDepartemenProfiles(): HasMany
    {
        return $this->hasMany(UserDepartemenProfile::class, 'id_departemen', 'id_departemen');
    }

    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'id_departemen', 'id_departemen');
    }
}
