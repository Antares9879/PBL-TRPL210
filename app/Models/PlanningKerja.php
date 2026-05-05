<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model PlanningKerja
 *
 * Header planning kerja per periode per perusahaan outsource.
 * Detail jadwal per karyawan ada di tabel jadwal_kerja.
 *
 * @property int    $id_planning
 * @property int    $id_perusahaan
 * @property int    $periode_bulan    1–12
 * @property int    $periode_tahun
 * @property string $status           draft|aktif|diperbarui
 * @property int    $versi
 * @property int    $dibuat_oleh      id_pengguna Admin Outsource
 */
class PlanningKerja extends Model
{
    protected $table      = 'planning_kerja';
    protected $primaryKey = 'id_planning';

    protected $fillable = [
        'id_perusahaan',
        'periode_bulan',
        'periode_tahun',
        'status',
        'versi',
        'dibuat_oleh',
    ];

    const STATUS_DRAFT      = 'draft';
    const STATUS_AKTIF      = 'aktif';
    const STATUS_DIPERBARUI = 'diperbarui';

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(PerusahaanOutsource::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function pembuatan(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKerja::class, 'id_planning', 'id_planning');
    }

    /**
     * Alias untuk relasi jadwal (untuk konsistensi dengan controller)
     */
    public function jadwalKerja(): HasMany
    {
        return $this->jadwal();
    }

    /**
     * Label periode yang mudah dibaca (contoh: "Maret 2025").
     */
    public function getPeriodeLabelAttribute(): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',      6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',  9 => 'September',
            10 => 'Oktober',11 => 'November',12 => 'Desember',
        ];
        return ($bulan[$this->periode_bulan] ?? '?') . ' ' . $this->periode_tahun;
    }
}