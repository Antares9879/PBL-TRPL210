<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JenisIzinSeeder
 *
 * Mengisi tabel jenis_izin dengan 4 jenis izin default
 * sesuai data awal yang didefinisikan di skema SQL e_outsourcing_v2.sql.
 *
 * Jalankan: php artisan db:seed --class=JenisIzinSeeder
 */
class JenisIzinSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama_jenis'    => 'Sakit',
                'wajib_dokumen' => true,
                'keterangan'    => 'Wajib melampirkan surat dokter',
            ],
            [
                'nama_jenis'    => 'Izin Keperluan Keluarga',
                'wajib_dokumen' => true,
                'keterangan'    => 'Wajib melampirkan surat/bukti',
            ],
            [
                'nama_jenis'    => 'Izin Keperluan Lain',
                'wajib_dokumen' => false,
                'keterangan'    => 'Tanpa dokumen wajib',
            ],
            [
                'nama_jenis'    => 'Cuti',
                'wajib_dokumen' => false,
                'keterangan'    => 'Cuti tahunan',
            ],
        ];

        foreach ($data as $item) {
            DB::table('jenis_izin')->updateOrInsert(
                ['nama_jenis' => $item['nama_jenis']],
                array_merge($item, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('JenisIzinSeeder: 4 jenis izin berhasil dibuat.');
        $this->command->table(
            ['Nama Jenis', 'Wajib Dokumen', 'Keterangan'],
            collect($data)->map(fn($d) => [
                $d['nama_jenis'],
                $d['wajib_dokumen'] ? 'Ya' : 'Tidak',
                $d['keterangan'],
            ])->toArray()
        );
    }
}