<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model PengajuanIzin
 *
 * Menyimpan pengajuan izin tidak masuk dari karyawan.
 * Dokumen pendukung disimpan di tabel dokumen_izin.
 *
 * @property int         $id_izin
 * @property int         $id_karyawan
 * @property int         $id_jenis_izin
 * @property string      $tanggal_izin
 * @property string|null $keterangan
 * @property string      $status              menunggu|disetujui|ditolak
 * @property string|null $catatan_penolakan
 * @property string      $status_dokumen      belum_upload|sudah_upload|lengkap|tidak_lengkap
 * @property string|null $catatan_dokumen
 * @property string      $diajukan_pada
 * @property int|null    $divalidasi_admin
 * @property string|null $waktu_validasi_admin
 * @property int|null    $diverifikasi_hr
 * @property string|null $waktu_verifikasi_hr
 */
class PengajuanIzin extends Model
{
    protected $table      = 'pengajuan_izin';
    protected $primaryKey = 'id_izin';

    protected $fillable = [
        'id_karyawan',
        'id_jenis_izin',
        'tanggal_izin',
        'keterangan',
        'status',
        'catatan_penolakan',
        'status_dokumen',
        'catatan_dokumen',
        'diajukan_pada',
        'divalidasi_admin',
        'waktu_validasi_admin',
        'diverifikasi_hr',
        'waktu_verifikasi_hr',
    ];

    protected $casts = [
        'tanggal_izin'         => 'date',
        'diajukan_pada'        => 'datetime',
        'waktu_validasi_admin' => 'datetime',
        'waktu_verifikasi_hr'  => 'datetime',
    ];

    const STATUS_MENUNGGU  = 'menunggu';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK   = 'ditolak';

    const DOKUMEN_BELUM_UPLOAD  = 'belum_upload';
    const DOKUMEN_SUDAH_UPLOAD  = 'sudah_upload';
    const DOKUMEN_LENGKAP       = 'lengkap';
    const DOKUMEN_TIDAK_LENGKAP = 'tidak_lengkap';

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function jenisIzin(): BelongsTo
    {
        return $this->belongsTo(JenisIzin::class, 'id_jenis_izin', 'id_jenis_izin');
    }

    public function validatorAdmin(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'divalidasi_admin', 'id_pengguna');
    }

    public function verifikatorHr(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diverifikasi_hr', 'id_pengguna');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenIzin::class, 'id_izin', 'id_izin');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }
}