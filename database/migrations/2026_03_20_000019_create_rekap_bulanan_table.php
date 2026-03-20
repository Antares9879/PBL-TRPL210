<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel rekapitulasi bulanan per karyawan.
     * Di-generate oleh RekapService, bukan diisi manual.
     * Status 'draft' bisa diedit, status 'final' dikunci oleh HR.
     */
    public function up(): void
    {
        Schema::create('rekap_bulanan', function (Blueprint $table) {
            $table->id('id_rekap');
            $table->unsignedBigInteger('id_karyawan');
            $table->tinyInteger('periode_bulan')->unsigned();
            $table->year('periode_tahun');
            $table->integer('total_hari_kerja')->default(0);
            $table->integer('total_hari_hadir')->default(0);
            $table->integer('total_hari_izin')->default(0);
            $table->integer('total_hari_alpa')->default(0);
            $table->integer('total_menit_normal')->default(0);
            $table->integer('total_menit_lembur')->default(0);
            $table->integer('total_menit_telat')->default(0);
            $table->integer('total_menit_pulang_cepat')->default(0);
            $table->enum('status_rekap', ['draft', 'final'])->default('draft');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->dateTime('ditetapkan_pada')->nullable();
            $table->timestamps();

            $table->foreign('id_karyawan', 'fk_rekap_karyawan')
                  ->references('id_karyawan')->on('karyawan')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('dibuat_oleh', 'fk_rekap_hr')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            // Satu karyawan hanya boleh punya satu rekap per periode
            $table->unique(
                ['id_karyawan', 'periode_bulan', 'periode_tahun'],
                'uq_rekap_karyawan_periode'
            );

            $table->index(['periode_tahun', 'periode_bulan'], 'idx_rekap_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_bulanan');
    }
};
