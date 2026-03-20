# 📋 Panduan Migrasi Database — phpMyAdmin → Laravel Migration
> **Proyek:** PBL-TRPL210 E-Outsourcing PT Ecogreen  
> **Tanggal:** 20 Maret 2026  
> **Dibuat untuk:** Antares & Tim (William, Kelvin, Rafly, Rivaldi, Akut)

---

## ⚠️ Baca Dulu Sebelum Mulai

Kamu punya **database phpMyAdmin yang sudah ada datanya**. Artinya ada dua hal yang
perlu dilakukan secara berurutan:

1. **Backup data** dari phpMyAdmin terlebih dahulu
2. **Setup migration** Laravel yang baru
3. **Restore data** ke database yang sudah dikelola Laravel

Jangan langsung jalankan `migrate:fresh` tanpa backup — semua data akan hilang.

---

## Langkah 1 — Backup Database phpMyAdmin

### Via phpMyAdmin GUI:
1. Buka phpMyAdmin → pilih database `e_outsourcing` (atau nama database kamu)
2. Klik tab **Export**
3. Pilih method: **Custom**
4. Format: **SQL**
5. Di bagian **Data**: centang **Insert** (bukan hanya struktur)
6. Klik **Go** → simpan file `.sql` di tempat yang aman

### Via Command Line (lebih cepat):
```bash
# Di terminal / Git Bash
mysqldump -u root -p e_outsourcing > backup_e_outsourcing_$(date +%Y%m%d).sql
```

> 💡 Simpan file backup ini di luar folder proyek (jangan di dalam repo Git).

---

## Langkah 2 — Hapus Migration Bawaan Laravel

Migration default Laravel membuat tabel `users`, `password_reset_tokens`, `sessions`,
`cache`, `jobs`, dll. Kita pakai **Opsi A** — tabel `pengguna` menggantikan `users`
sepenuhnya, jadi migration bawaan harus dihapus.

### File yang DIHAPUS:
```
database/migrations/0001_01_01_000000_create_users_table.php      ← HAPUS
database/migrations/0001_01_01_000001_create_cache_table.php      ← HAPUS
database/migrations/0001_01_01_000002_create_jobs_table.php       ← HAPUS
database/migrations/2026_03_18_060647_create_personal_access_tokens_table.php  ← HAPUS
```

> **Catatan:** Kita tidak pakai Sanctum (personal_access_tokens) karena autentikasi
> menggunakan Laravel Session secara manual, sesuai keputusan arsitektur STRUKTUR-FOLDER.md.
> Cache dan jobs tidak dipakai di skala PBL ini.

---

## Langkah 3 — Salin File Migration Baru

Salin semua file migration dari folder ini ke `database/migrations/` di proyek Laravel:

```
2026_03_20_000001_create_perusahaan_outsource_table.php
2026_03_20_000002_create_departemen_table.php
2026_03_20_000003_create_shift_table.php
2026_03_20_000004_create_jenis_izin_table.php
2026_03_20_000005_create_konfigurasi_area_table.php
2026_03_20_000006_create_pengguna_table.php
2026_03_20_000007_create_admin_outsource_profile_table.php
2026_03_20_000008_create_user_departemen_profile_table.php
2026_03_20_000009_create_karyawan_table.php
2026_03_20_000010_add_fk_diubah_oleh_to_konfigurasi_area.php
2026_03_20_000011_create_planning_kerja_table.php
2026_03_20_000012_create_jadwal_kerja_table.php
2026_03_20_000013_create_absensi_table.php
2026_03_20_000014_create_pengajuan_lembur_table.php
2026_03_20_000015_create_pengajuan_izin_table.php
2026_03_20_000016_create_dokumen_izin_table.php
2026_03_20_000017_create_audit_log_table.php
2026_03_20_000018_create_notifikasi_table.php
2026_03_20_000019_create_rekap_bulanan_table.php
```

---

## Langkah 4 — Konfigurasi File `.env`

Buka file `.env` di root proyek. Ubah bagian berikut:

```env
# ============================================================
# APP
# ============================================================
APP_NAME="E-Outsourcing Ecogreen"
APP_ENV=local
APP_KEY=                          # akan diisi otomatis oleh php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

# ============================================================
# DATABASE
# ============================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_outsourcing         # ← sesuaikan dengan nama database di phpMyAdmin kamu
DB_USERNAME=root                  # ← username MySQL kamu (default Laragon: root)
DB_PASSWORD=                      # ← password MySQL kamu (default Laragon: kosong)

# ============================================================
# SESSION — wajib file, bukan database, sesuai keputusan arsitektur
# ============================================================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ============================================================
# CACHE — pakai file untuk skala PBL
# ============================================================
CACHE_STORE=file

# ============================================================
# QUEUE — sync untuk skala PBL (tidak perlu worker background)
# ============================================================
QUEUE_CONNECTION=sync

# ============================================================
# FILESYSTEM — local storage untuk dokumen izin
# ============================================================
FILESYSTEM_DISK=local

# ============================================================
# MAIL — opsional, bisa diisi nanti jika butuh notifikasi email
# ============================================================
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@ecogreen.test"
MAIL_FROM_NAME="${APP_NAME}"
```

> ⚠️ **Jangan commit file `.env` ke Git.** File ini sudah ada di `.gitignore` bawaan
> Laravel. Setiap anggota tim punya `.env` masing-masing.

---

## Langkah 5 — Konfigurasi `config/auth.php`

Karena kita pakai tabel `pengguna` bukan `users`, Laravel perlu diberitahu.
Buka `config/auth.php` dan ubah bagian `providers`:

```php
// config/auth.php

'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'pengguna',   // ← ubah dari 'users' ke 'pengguna'
    ],
],

'providers' => [
    'pengguna' => [                 // ← ganti nama provider dari 'users' ke 'pengguna'
        'driver' => 'eloquent',
        'model'  => App\Models\Pengguna::class,   // ← arahkan ke model Pengguna
    ],
],

'passwords' => [
    'pengguna' => [                 // ← sesuaikan juga bagian ini
        'provider' => 'pengguna',
        'table'    => 'password_reset_tokens',
        'expire'   => 60,
        'throttle' => 60,
    ],
],
```

---

## Langkah 6 — Pastikan Model `Pengguna.php` Sudah Benar

Model `Pengguna` harus mengimplementasikan `Authenticatable` agar bisa dipakai
Laravel Auth. Pastikan isinya seperti ini:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    // --- Wajib: arahkan ke tabel yang benar ---
    protected $table      = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    // --- Wajib: nama kolom password di tabel kita berbeda dari default Laravel ---
    protected $authPasswordName = 'password_hash';

    protected $fillable = [
        'nama_lengkap',
        'email',
        'password_hash',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',    // jangan expose hash ke response JSON
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
        ];
    }

    // --- Konstanta role agar tidak ada magic string di seluruh kode ---
    const ROLE_SUPER_ADMIN      = 'super_admin';
    const ROLE_HR               = 'hr';
    const ROLE_USER_DEPARTEMEN  = 'user_departemen';
    const ROLE_ADMIN_OUTSOURCE  = 'admin_outsource';
    const ROLE_KARYAWAN         = 'karyawan';

    const STATUS_AKTIF    = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';
}
```

> **Catatan penting:** `Authenticatable` sudah meng-extend `Model` sekaligus
> mengimplementasikan interface `AuthenticatableContract`. Kamu tidak perlu extend
> `Model` lagi secara terpisah.

---

## Langkah 7 — Jalankan Migration (Fresh Start)

Karena kita pindah dari skema phpMyAdmin lama ke migration baru, jalankan perintah
berikut untuk **drop semua tabel lama** dan buat ulang dari migration:

```bash
# Di terminal, dari root folder proyek
php artisan migrate:fresh
```

Perintah ini akan:
1. Drop semua tabel yang ada di database
2. Jalankan semua migration dari awal secara berurutan

> ⚠️ **Perintah ini menghapus semua data!** Pastikan sudah backup di Langkah 1.

---

## Langkah 8 — Jalankan Seeder

Setelah migration berhasil, isi data awal (shift, jenis izin, akun testing):

```bash
# Jalankan semua seeder sekaligus (pastikan DatabaseSeeder.php sudah diupdate)
php artisan db:seed

# Atau jalankan seeder spesifik satu per satu
php artisan db:seed --class=ShiftSeeder
php artisan db:seed --class=JenisIzinSeeder
php artisan db:seed --class=PenggunaSeeder
```

Setelah seeder berhasil, kamu bisa login dengan akun testing:

| Email                      | Role             | Password     |
|----------------------------|------------------|--------------|
| superadmin@ecogreen.test   | super_admin      | password123  |
| hr@ecogreen.test           | hr               | password123  |
| departemen@ecogreen.test   | user_departemen  | password123  |
| admin@majujaya.test        | admin_outsource  | password123  |
| karyawan@majujaya.test     | karyawan         | password123  |

---

## Langkah 9 — Restore Data Lama (Opsional)

Jika ada data dari phpMyAdmin yang ingin dipindahkan ke database baru:

### Opsi A — Import SQL manual (jika struktur tabel tidak berubah):
```bash
mysql -u root -p e_outsourcing < backup_e_outsourcing_20260320.sql
```

### Opsi B — Export per tabel, import manual (jika ada perubahan struktur):
1. Di phpMyAdmin, export tabel yang datanya ingin dipertahankan (misal: `karyawan`)
2. Pastikan kolom di file SQL export cocok dengan skema migration baru
3. Import tabel per tabel secara manual

### Opsi C — Buat seeder dari data lama (rekomendasi untuk data master):
Untuk data master seperti `departemen` atau `perusahaan_outsource`, lebih baik
dibuat sebagai Seeder agar bisa di-reproduce kapan saja:

```bash
php artisan make:seeder DepartemenSeeder
php artisan make:seeder PerusahaanOutsourceSeeder
```

---

## Langkah 10 — Update `DatabaseSeeder.php`

Buka `database/seeders/DatabaseSeeder.php` dan update agar memanggil semua seeder
secara berurutan (urutan penting karena ada FK):

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan wajib mengikuti dependency FK
        $this->call([
            ShiftSeeder::class,
            JenisIzinSeeder::class,
            DepartemenSeeder::class,
            PerusahaanOutsourceSeeder::class,
            KonfigurasiAreaSeeder::class,
            PenggunaSeeder::class,         // setelah semua master data ada
        ]);
    }
}
```

---

## Langkah 11 — Verifikasi

Setelah semua langkah selesai, verifikasi hasilnya:

```bash
# Cek semua tabel sudah terbentuk
php artisan migrate:status

# Cek semua route terdaftar dengan benar
php artisan route:list

# Cek tidak ada error di aplikasi
php artisan about
```

Di phpMyAdmin, pastikan tabel-tabel berikut sudah ada (19 tabel):
- `perusahaan_outsource`, `departemen`, `shift`, `jenis_izin`, `konfigurasi_area`
- `pengguna`
- `admin_outsource_profile`, `user_departemen_profile`, `karyawan`
- `planning_kerja`, `jadwal_kerja`
- `absensi`, `pengajuan_lembur`, `pengajuan_izin`, `dokumen_izin`
- `audit_log`, `notifikasi`, `rekap_bulanan`
- `migrations` (tabel internal Laravel untuk tracking migration)

---

## ❓ Troubleshooting Umum

### Error: `SQLSTATE[42000]: Syntax error — ENUM`
Pastikan versi MySQL >= 5.7. Cek di phpMyAdmin: **Server** → **Variables** → cari `version`.

### Error: `Class "App\Models\Pengguna" not found`
Jalankan:
```bash
composer dump-autoload
php artisan cache:clear
```

### Error: `Cannot add foreign key constraint`
Urutan migration sudah diatur dengan benar di file-file ini. Jika masih error,
kemungkinan ada sisa tabel lama di database. Jalankan `migrate:fresh` untuk
drop semua tabel dan mulai bersih.

### Error: `Access denied for user 'root'@'localhost'`
Cek ulang `DB_USERNAME` dan `DB_PASSWORD` di `.env`. Di Laragon, defaultnya
`root` dengan password kosong.

### `php artisan migrate` berhasil tapi tabel tidak muncul di phpMyAdmin
Refresh halaman phpMyAdmin. Pastikan `DB_DATABASE` di `.env` mengarah ke
database yang sedang kamu lihat di phpMyAdmin.

---

## 📝 Catatan untuk Anggota Tim

Setiap anggota tim yang clone repo ini untuk pertama kali perlu:

```bash
# 1. Copy .env.example menjadi .env
cp .env.example .env

# 2. Isi DB_DATABASE, DB_USERNAME, DB_PASSWORD sesuai setup lokal masing-masing

# 3. Generate app key
php artisan key:generate

# 4. Jalankan migration + seeder
php artisan migrate --seed

# 5. Buat symlink storage (wajib sekali)
php artisan storage:link
```

Pastikan `.env.example` sudah diupdate dengan semua key yang dibutuhkan
(tanpa nilai sensitifnya) agar anggota tim tahu apa yang perlu diisi.
