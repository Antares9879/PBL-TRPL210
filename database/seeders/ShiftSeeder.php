<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ShiftSeeder
 *
 * Mengisi tabel shift dengan 4 shift default sesuai data awal
 * yang didefinisikan di skema SQL e_outsourcing_v2.sql.
 *
 * Shift yang dibuat:
 *   - Shift Pagi   : 07:00 – 15:00 (480 menit)
 *   - Shift Siang  : 15:00 – 23:00 (480 menit)
 *   - Shift Malam  : 23:00 – 07:00 (480 menit)
 *   - Shift Normal : 08:00 – 17:00 (480 menit)
 *
 * Jalankan: php artisan db:seed --class=ShiftSeeder
 */
class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama_shift'          => 'Shift Pagi',
                'jam_masuk'           => '07:00:00',
                'jam_pulang'          => '15:00:00',
                'durasi_normal_menit' => 480,
                'status'              => 'aktif',
            ],
            [
                'nama_shift'          => 'Shift Siang',
                'jam_masuk'           => '15:00:00',
                'jam_pulang'          => '23:00:00',
                'durasi_normal_menit' => 480,
                'status'              => 'aktif',
            ],
            [
                'nama_shift'          => 'Shift Malam',
                'jam_masuk'           => '23:00:00',
                'jam_pulang'          => '07:00:00',
                'durasi_normal_menit' => 480,
                'status'              => 'aktif',
            ],
            [
                'nama_shift'          => 'Shift Normal',
                'jam_masuk'           => '08:00:00',
                'jam_pulang'          => '17:00:00',
                'durasi_normal_menit' => 480,
                'status'              => 'aktif',
            ],
        ];

        foreach ($data as $item) {
            DB::table('shift')->updateOrInsert(
                ['nama_shift' => $item['nama_shift']],
                array_merge($item, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('ShiftSeeder: 4 shift berhasil dibuat.');
        $this->command->table(
            ['Nama Shift', 'Jam Masuk', 'Jam Pulang', 'Durasi', 'Status'],
            collect($data)->map(fn($s) => [
                $s['nama_shift'],
                substr($s['jam_masuk'],  0, 5),
                substr($s['jam_pulang'], 0, 5),
                $s['durasi_normal_menit'] . ' menit',
                $s['status'],
            ])->toArray()
        );
    }
}