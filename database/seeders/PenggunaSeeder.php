<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PenggunaSeeder
 *
 * Membuat satu akun testing per role untuk keperluan development & testing.
 * Semua akun menggunakan password: password123
 *
 * Jalankan: php artisan db:seed --class=PenggunaSeeder
 */
class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('password123');

        $pengguna = [
            [
                'nama_lengkap' => 'Super Admin',
                'email'        => 'superadmin@ecogreen.test',
                'password_hash'=> $passwordHash,
                'role'         => Pengguna::ROLE_SUPER_ADMIN,
                'status'       => Pengguna::STATUS_AKTIF,
            ],
            [
                'nama_lengkap' => 'HR Officer',
                'email'        => 'hr@ecogreen.test',
                'password_hash'=> $passwordHash,
                'role'         => Pengguna::ROLE_HR,
                'status'       => Pengguna::STATUS_AKTIF,
            ],
            [
                'nama_lengkap' => 'User Departemen Produksi',
                'email'        => 'departemen@ecogreen.test',
                'password_hash'=> $passwordHash,
                'role'         => Pengguna::ROLE_USER_DEPARTEMEN,
                'status'       => Pengguna::STATUS_AKTIF,
            ],
            [
                'nama_lengkap' => 'Admin PT Maju Jaya',
                'email'        => 'admin@majujaya.test',
                'password_hash'=> $passwordHash,
                'role'         => Pengguna::ROLE_ADMIN_OUTSOURCE,
                'status'       => Pengguna::STATUS_AKTIF,
            ],
            [
                'nama_lengkap' => 'Budi Santoso',
                'email'        => 'karyawan@majujaya.test',
                'password_hash'=> $passwordHash,
                'role'         => Pengguna::ROLE_KARYAWAN,
                'status'       => Pengguna::STATUS_AKTIF,
            ],
        ];

        foreach ($pengguna as $data) {
            Pengguna::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        $this->command->info('PenggunaSeeder: 5 akun testing berhasil dibuat.');
        $this->command->table(
            ['Email', 'Role', 'Password'],
            collect($pengguna)->map(fn($p) => [
                $p['email'],
                $p['role'],
                'password123',
            ])->toArray()
        );
    }
}
