<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan wajib mengikuti dependency FK antar tabel:
     *
     *   1. ShiftSeeder          → tabel shift (tidak ada FK)
     *   2. JenisIzinSeeder      → tabel jenis_izin (tidak ada FK)
     *   3. PenggunaSeeder       → tabel pengguna (tidak ada FK)
     *
     * Seeder berikut belum dibuat, akan ditambahkan saat fitur siap:
     *   - DepartemenSeeder
     *   - PerusahaanOutsourceSeeder
     *   - KonfigurasiAreaSeeder   (butuh id_pengguna dari PenggunaSeeder)
     */
    public function run(): void
    {
        $this->call([
            ShiftSeeder::class,
            JenisIzinSeeder::class,
            PenggunaSeeder::class,
        ]);
    }
}   

