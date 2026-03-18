<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model PerusahaanOutsource
 *
 * @property int    $id_perusahaan
 * @property string $nama_perusahaan
 * @property string $alamat
 * @property string $no_telepon
 * @property string $email
 * @property string $status  aktif|nonaktif
 */
class PerusahaanOutsource extends Model
{
    protected $table      = 'perusahaan_outsource';
    protected $primaryKey = 'id_perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'alamat',
        'no_telepon',
        'email',
        'status',
    ];

    const STATUS_AKTIF    = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';

    public function adminProfiles(): HasMany
    {
        return $this->hasMany(AdminOutsourceProfile::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'id_perusahaan', 'id_perusahaan');
    }
}
