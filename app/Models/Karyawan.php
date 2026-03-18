<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Karyawan
 *
 * Extension table untuk role karyawan (Table Per Type).
 * Menyimpan data profil lengkap karyawan outsource.
 * Relasi 1:1 dengan tabel pengguna, dikelola oleh Admin Outsource.
 *
 * @property int    $id_karyawan
 * @property int    $id_pengguna
 * @property string $nik
 * @property string $nomor_karyawan
 * @property string $nama_lengkap
 * @property string $posisi
 * @property int    $id_perusahaan
 * @property int    $id_departemen
 * @property string $tanggal_bergabung
 * @property string $status
 * @property int    $created_by
 */
class Karyawan extends Model
{
    protected $table      = 'karyawan';
    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'id_pengguna',
        'nik',
        'nomor_karyawan',
        'nama_lengkap',
        'posisi',
        'id_perusahaan',
        'id_departemen',
        'tanggal_bergabung',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(PerusahaanOutsource::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'id_departemen', 'id_departemen');
    }
}
