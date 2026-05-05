<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogService
 *
 * Mencatat setiap aksi kritis ke tabel audit_log.
 * Dipanggil dari controller setelah setiap operasi approve/reject/create/update.
 *
 * Pola penggunaan:
 *   AuditLogService::catat(
 *       pengguna: auth()->user(),
 *       jenis: AuditLog::JENIS_ABSENSI,
 *       idReferensi: $absensi->id_absensi,
 *       aksi: AuditLog::AKSI_APPROVE,
 *       catatan: 'Disetujui oleh Admin Outsource',
 *       sebelum: $absensi->toArray(),
 *       sesudah: $absensi->fresh()->toArray(),
 *   );
 */
class AuditLogService
{
    /**
     * Catat satu entri audit log.
     *
     * @param \App\Models\Pengguna $pengguna  Pengguna yang melakukan aksi
     * @param string  $jenis        Konstanta AuditLog::JENIS_*
     * @param int     $idReferensi  PK dari data yang diaksi
     * @param string  $aksi         Konstanta AuditLog::AKSI_*
     * @param string|null $catatan  Keterangan tambahan (opsional)
     * @param array|null  $sebelum  Snapshot data sebelum perubahan
     * @param array|null  $sesudah  Snapshot data sesudah perubahan
     * @param string|null $ipAddress IP address pengguna (opsional, ambil dari request)
     */
    public static function catat(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        string $aksi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
        ?string $ipAddress = null,
    ): AuditLog {
        return AuditLog::create([
            'id_pengguna'  => $pengguna->id_pengguna,
            'role_pelaku'  => $pengguna->role,
            'jenis_data'   => $jenis,
            'id_referensi' => $idReferensi,
            'aksi'         => $aksi,
            'catatan'      => $catatan,
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
            'ip_address'   => $ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Catat approve.
     */
    public static function approve(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_APPROVE, $catatan, $sebelum, $sesudah);
    }

    /**
     * Catat reject.
     */
    public static function reject(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_REJECT, $catatan, $sebelum, $sesudah);
    }

    /**
     * Catat create.
     */
    public static function create(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_CREATE, $catatan, null, $sesudah);
    }

    /**
     * Catat update.
     */
    public static function update(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_UPDATE, $catatan, $sebelum, $sesudah);
    }

    /**
     * Catat activate.
     */
    public static function activate(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_ACTIVATE, $catatan, $sebelum, $sesudah);
    }

    /**
     * Catat deactivate.
     */
    public static function deactivate(
        \App\Models\Pengguna $pengguna,
        string $jenis,
        int $idReferensi,
        ?string $catatan = null,
        ?array $sebelum  = null,
        ?array $sesudah  = null,
    ): AuditLog {
        return static::catat($pengguna, $jenis, $idReferensi, AuditLog::AKSI_DEACTIVATE, $catatan, $sebelum, $sesudah);
    }
}