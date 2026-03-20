<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extension table untuk role karyawan sekaligus menyimpan profil lengkap.
     * Dikelola oleh Admin Outsource perusahaannya.
     */
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id('id_karyawan');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->string('nik', 30)->unique();
            $table->string('nomor_karyawan', 30)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('posisi', 100);
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedBigInteger('id_departemen');
            $table->date('tanggal_bergabung');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->unsignedBigInteger('created_by'); // id_pengguna Admin Outsource
            $table->timestamps();

            $table->foreign('id_pengguna', 'fk_karyawan_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_perusahaan', 'fk_karyawan_perusahaan')
                  ->references('id_perusahaan')->on('perusahaan_outsource')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_departemen', 'fk_karyawan_departemen')
                  ->references('id_departemen')->on('departemen')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('created_by', 'fk_karyawan_created_by')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('id_perusahaan', 'idx_karyawan_perusahaan');
            $table->index('id_departemen', 'idx_karyawan_departemen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
