<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model Pengguna
 *
 * Tabel autentikasi utama untuk seluruh role di sistem E-Outsourcing.
 * Mengimplementasikan Authenticatable agar dapat digunakan oleh Laravel Auth.
 *
 * Arsitektur Table Per Type:
 *   - role karyawan        → relasi hasOne(Karyawan)
 *   - role admin_outsource → relasi hasOne(AdminOutsourceProfile)
 *   - role user_departemen → relasi hasOne(UserDepartemenProfile)
 *   - role hr, super_admin → tidak ada extension table
 *
 * @property int         $id_pengguna
 * @property string      $nama_lengkap
 * @property string      $email
 * @property string      $password_hash
 * @property string      $role           super_admin|hr|user_departemen|admin_outsource|karyawan
 * @property string      $status         aktif|nonaktif
 * @property string|null $last_login
 * @property string      $created_at
 * @property string      $updated_at
 */
class Pengguna extends Authenticatable
{
    use Notifiable;

    // ── Mapping ke tabel non-default ─────────────────────────────────────────
    protected $table      = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    // ── Kolom password Laravel mengharapkan nama 'password' ──────────────────
    // Karena kolom kita bernama 'password_hash', kita override via accessor.
    // Lihat getAuthPassword() di bawah.

    protected $fillable = [
        'nama_lengkap',
        'email',
        'password_hash',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'last_login' => 'datetime',
    ];

    // ── Konstanta Role ────────────────────────────────────────────────────────

    const ROLE_SUPER_ADMIN     = 'super_admin';
    const ROLE_HR              = 'hr';
    const ROLE_USER_DEPARTEMEN = 'user_departemen';
    const ROLE_ADMIN_OUTSOURCE = 'admin_outsource';
    const ROLE_KARYAWAN        = 'karyawan';

    const STATUS_AKTIF    = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';

    // ── Override Auth contract ────────────────────────────────────────────────

    /**
     * Laravel Auth mengharapkan kolom bernama 'password'.
     * Karena schema kita menggunakan 'password_hash', kita bridge di sini.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Kolom identifier untuk login (digunakan oleh Laravel Auth).
     */
    public function getAuthIdentifierName(): string
    {
        return 'id_pengguna';
    }

    // ── Helper Methods ────────────────────────────────────────────────────────

    /**
     * Cek apakah pengguna memiliki role tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Cek apakah akun pengguna masih aktif.
     */
    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    /**
     * Dapatkan URL redirect dashboard berdasarkan role.
     */
    public function getDashboardUrl(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN     => '/super-admin/dashboard',
            self::ROLE_HR              => '/hr/dashboard',
            self::ROLE_USER_DEPARTEMEN => '/departemen/dashboard',
            self::ROLE_ADMIN_OUTSOURCE => '/admin/dashboard',
            self::ROLE_KARYAWAN        => '/karyawan/dashboard',
            default                    => '/login',
        };
    }

    // ── Relasi Table Per Type ─────────────────────────────────────────────────

    /**
     * Profil khusus Admin Outsource (extension 1:1).
     */
    public function adminOutsourceProfile(): HasOne
    {
        return $this->hasOne(AdminOutsourceProfile::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Profil khusus User Departemen (extension 1:1).
     */
    public function userDepartemenProfile(): HasOne
    {
        return $this->hasOne(UserDepartemenProfile::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Profil lengkap Karyawan (extension 1:1 + data karyawan).
     */
    public function karyawan(): HasOne
    {
        return $this->hasOne(Karyawan::class, 'id_pengguna', 'id_pengguna');
    }
}
