<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AuditLog
 *
 * Menyimpan jejak seluruh aksi kritis (approve/reject/create/update/deactivate/upload)
 * untuk transparansi HR dan keperluan audit.
 *
 * @property int         $id_log
 * @property int         $id_pengguna
 * @property string      $role_pelaku     admin_outsource|user_departemen|hr|super_admin|sistem
 * @property string      $jenis_data      absensi|lembur|izin|planning|akun|master_data|konfigurasi
 * @property int         $id_referensi    PK dari data yang diaksi
 * @property string      $aksi            approve|reject|create|update|deactivate|upload
 * @property string|null $catatan
 * @property array|null  $data_sebelum    snapshot JSON sebelum perubahan
 * @property array|null  $data_sesudah    snapshot JSON sesudah perubahan
 * @property string|null $ip_address
 * @property string      $waktu_aksi
 */
class AuditLog extends Model
{
    protected $table      = 'audit_log';
    protected $primaryKey = 'id_log';

    // Tabel ini tidak perlu updated_at
    const UPDATED_AT = null;
    // Gunakan waktu_aksi sebagai created_at
    const CREATED_AT = 'waktu_aksi';

    protected $fillable = [
        'id_pengguna',
        'role_pelaku',
        'jenis_data',
        'id_referensi',
        'aksi',
        'catatan',
        'data_sebelum',
        'data_sesudah',
        'ip_address',
        'waktu_aksi',
    ];

    protected $casts = [
        'data_sebelum' => 'array',
        'data_sesudah' => 'array',
        'waktu_aksi'   => 'datetime',
    ];

    // ── Konstanta ─────────────────────────────────────────────────────────────

    const ROLE_ADMIN_OUTSOURCE = 'admin_outsource';
    const ROLE_USER_DEPARTEMEN = 'user_departemen';
    const ROLE_HR              = 'hr';
    const ROLE_SUPER_ADMIN     = 'super_admin';
    const ROLE_KARYAWAN        = 'karyawan';
    const ROLE_SISTEM          = 'sistem';

    const JENIS_ABSENSI     = 'absensi';
    const JENIS_LEMBUR      = 'lembur';
    const JENIS_IZIN        = 'izin';
    const JENIS_PLANNING    = 'planning';
    const JENIS_AKUN        = 'akun';
    const JENIS_MASTER_DATA = 'master_data';
    const JENIS_KONFIGURASI = 'konfigurasi';
    const JENIS_AUTH        = 'auth';

    const AKSI_LOGIN      = 'login';
    const AKSI_LOGOUT     = 'logout';
    const AKSI_APPROVE    = 'approve';
    const AKSI_REJECT     = 'reject';
    const AKSI_CREATE     = 'create';
    const AKSI_UPDATE     = 'update';
    const AKSI_DEACTIVATE = 'deactivate';
    const AKSI_ACTIVATE   = 'activate';
    const AKSI_UPLOAD     = 'upload';

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Alias untuk relasi pelaku (untuk konsistensi dengan controller)
     */
    public function pengguna(): BelongsTo
    {
        return $this->pelaku();
    }
}