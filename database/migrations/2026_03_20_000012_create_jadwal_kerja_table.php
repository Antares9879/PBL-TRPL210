<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_kerja', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->unsignedBigInteger('id_planning');
            $table->unsignedBigInteger('id_karyawan');
            $table->unsignedBigInteger('id_shift');
            $table->date('tanggal_kerja');
            $table->boolean('is_hari_libur')->default(false);
            $table->timestamps();

            $table->foreign('id_planning', 'fk_jadwal_planning')
                  ->references('id_planning')->on('planning_kerja')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_karyawan', 'fk_jadwal_karyawan')
                  ->references('id_karyawan')->on('karyawan')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_shift', 'fk_jadwal_shift')
                  ->references('id_shift')->on('shift')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            // Satu karyawan tidak boleh punya dua jadwal di tanggal & planning yang sama
            $table->unique(
                ['id_karyawan', 'tanggal_kerja', 'id_planning'],
                'uq_jadwal_karyawan_tanggal'
            );

            $table->index(['id_karyawan', 'tanggal_kerja'], 'idx_jadwal_karyawan_tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kerja');
    }
};
