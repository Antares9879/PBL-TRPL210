<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Model PengajuanIzin
 *
 * Menyimpan pengajuan izin tidak masuk dari karyawan.
 * Mendukung izin single-day maupun multi-day (range tanggal).
 * Dokumen pendukung disimpan di tabel dokumen_izin.
 *
 * Logika range tanggal:
 *   - tanggal_izin          = tanggal mulai (wajib)
 *   - tanggal_selesai_izin  = tanggal akhir (nullable; NULL berarti izin 1 hari)
 *   - Izin 1 hari : tanggal_izin == tanggal_selesai_izin (atau tanggal_selesai_izin = null)
 *   - Izin multi-day: tanggal_selesai_izin > tanggal_izin
 *
 * @property int         $id_izin
 * @property int         $id_karyawan
 * @property int         $id_jenis_izin
 * @property string      $tanggal_izin           tanggal mulai
 * @property string|null $tanggal_selesai_izin   tanggal akhir; null = 1 hari
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
        'tanggal_selesai_izin',
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
        'tanggal_selesai_izin' => 'date',
        'diajukan_pada'        => 'datetime',
        'waktu_validasi_admin' => 'datetime',
        'waktu_verifikasi_hr'  => 'datetime',
    ];

    // ── Konstanta ─────────────────────────────────────────────────────────────

    const STATUS_MENUNGGU  = 'menunggu';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK   = 'ditolak';

    const DOKUMEN_BELUM_UPLOAD  = 'belum_upload';
    const DOKUMEN_SUDAH_UPLOAD  = 'sudah_upload';
    const DOKUMEN_LENGKAP       = 'lengkap';
    const DOKUMEN_TIDAK_LENGKAP = 'tidak_lengkap';

    // ── Relasi ────────────────────────────────────────────────────────────────

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

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeMenunggu($query)
    {
        return $query->where('status', self::STATUS_MENUNGGU);
    }

    /**
     * Scope untuk mencari pengajuan yang tanggalnya overlap dengan range tertentu.
     *
     * Dua range [A, B] dan [C, D] overlap jika: A <= D && B >= C
     * Untuk kasus tanggal_selesai_izin = null, dianggap tanggal_selesai = tanggal_izin.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $tanggalMulai  format Y-m-d
     * @param  string $tanggalSelesai format Y-m-d
     */
    public function scopeOverlapDengan($query, string $tanggalMulai, string $tanggalSelesai)
    {
        return $query->where(function ($q) use ($tanggalMulai, $tanggalSelesai) {
            // tanggal_izin (mulai) <= tanggal_selesai yang diminta
            $q->where('tanggal_izin', '<=', $tanggalSelesai)
              // DAN (tanggal_selesai_izin >= tanggal_mulai yang diminta,
              //      atau tanggal_selesai_izin null tapi tanggal_izin >= tanggal_mulai)
              ->where(function ($inner) use ($tanggalMulai) {
                  $inner->where('tanggal_selesai_izin', '>=', $tanggalMulai)
                        ->orWhere(function ($fallback) use ($tanggalMulai) {
                            // tanggal_selesai_izin null = izin 1 hari, cukup cek tanggal_izin
                            $fallback->whereNull('tanggal_selesai_izin')
                                     ->where('tanggal_izin', '>=', $tanggalMulai);
                        });
              });
        });
    }

    // ── Helper Methods ────────────────────────────────────────────────────────

    /**
     * Hitung jumlah hari izin (inklusif).
     * Contoh: 10 Mar – 12 Mar = 3 hari
     */
    public function jumlahHari(): int
    {
        $mulai   = $this->tanggal_izin;
        $selesai = $this->tanggal_selesai_izin ?? $this->tanggal_izin;

        return (int) $mulai->diffInDays($selesai) + 1;
    }

    /**
     * Ambil tanggal selesai yang efektif.
     * Jika tanggal_selesai_izin null, return tanggal_izin (izin 1 hari).
     */
    public function getTanggalSelesaiEfektif(): Carbon
    {
        return $this->tanggal_selesai_izin ?? $this->tanggal_izin;
    }

    /**
     * Apakah izin ini berlaku untuk lebih dari 1 hari?
     */
    public function isMultiHari(): bool
    {
        return $this->jumlahHari() > 1;
    }
}