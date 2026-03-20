<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_kerja', function (Blueprint $table) {
            $table->id('id_planning');
            $table->unsignedBigInteger('id_perusahaan');
            $table->tinyInteger('periode_bulan')->unsigned();
            $table->year('periode_tahun');
            $table->enum('status', ['draft', 'aktif', 'diperbarui'])->default('draft');
            $table->tinyInteger('versi')->unsigned()->default(1);
            $table->unsignedBigInteger('dibuat_oleh');
            $table->timestamps();

            $table->foreign('id_perusahaan', 'fk_planning_perusahaan')
                  ->references('id_perusahaan')->on('perusahaan_outsource')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('dibuat_oleh', 'fk_planning_dibuat_oleh')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            // Unique: satu perusahaan hanya boleh punya satu versi per periode
            $table->unique(
                ['id_perusahaan', 'periode_bulan', 'periode_tahun', 'versi'],
                'uq_planning_periode'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_kerja');
    }
};
