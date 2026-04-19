<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom tanggal_selesai_izin ke tabel pengajuan_izin.
     *
     * Keputusan desain:
     *   - Kolom tanggal_izin tetap digunakan sebagai tanggal mulai (backward compatible).
     *   - Kolom tanggal_selesai_izin nullable agar data lama tidak perlu di-migrate ulang.
     *     Data lama (tanggal_selesai_izin = null) dianggap izin 1 hari.
     *   - Untuk izin 1 hari: tanggal_izin == tanggal_selesai_izin.
     *   - Index ditambahkan untuk mendukung query cek overlap yang efisien.
     */
    public function up(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            // Tambah setelah kolom tanggal_izin agar urutan kolom rapi di DB
            $table->date('tanggal_selesai_izin')
                  ->nullable()
                  ->after('tanggal_izin')
                  ->comment('Tanggal akhir izin. NULL = izin 1 hari (sama dengan tanggal_izin). Harus >= tanggal_izin.');

            // Index untuk mendukung query overlap:
            // WHERE tanggal_izin <= :tgl_selesai AND tanggal_selesai_izin >= :tgl_mulai
            $table->index(
                ['id_karyawan', 'tanggal_izin', 'tanggal_selesai_izin'],
                'idx_izin_range'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            $table->dropIndex('idx_izin_range');
            $table->dropColumn('tanggal_selesai_izin');
        });
    }
};
