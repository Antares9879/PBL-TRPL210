<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DokumenIzin
 *
 * Menyimpan metadata file dokumen pendukung pengajuan izin.
 * File fisik disimpan di storage/app/private/dokumen-izin/{id_izin}/.
 * Akses file wajib lewat controller yang memverifikasi hak akses.
 *
 * @property int    $id_dokumen
 * @property int    $id_izin
 * @property string $nama_file
 * @property string $path_file
 * @property string $tipe_file
 * @property int    $ukuran_kb
 * @property \Carbon\Carbon|null    $diunggah_oleh
 * @property \Carbon\Carbon|null $diunggah_pada
 */
class DokumenIzin extends Model
{
    protected $table      = 'dokumen_izin';
    protected $primaryKey = 'id_dokumen';

    // Tabel ini tidak pakai timestamps (hanya diunggah_pada)
    public $timestamps = false;

    protected $fillable = [
        'id_izin',
        'nama_file',
        'path_file',
        'tipe_file',
        'ukuran_kb',
        'diunggah_oleh',
        'diunggah_pada',
    ];

    protected $casts = [
        'diunggah_pada' => 'datetime',
    ];

    // Tipe file yang diizinkan untuk upload
    const TIPE_DIIZINKAN = ['pdf', 'jpg', 'jpeg', 'png'];
    const UKURAN_MAKS_KB = 5120; // 5 MB

    public function izin(): BelongsTo
    {
        return $this->belongsTo(PengajuanIzin::class, 'id_izin', 'id_izin');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diunggah_oleh', 'id_pengguna');
    }
}