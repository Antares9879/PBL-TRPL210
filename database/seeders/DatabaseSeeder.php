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
