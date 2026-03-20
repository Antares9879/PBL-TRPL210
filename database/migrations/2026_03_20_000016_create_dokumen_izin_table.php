<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * File disimpan di storage/app/private/dokumen-izin/{id_izin}/
     * Akses wajib melalui DokumenIzinApiController yang memverifikasi hak akses.
     */
    public function up(): void
    {
        Schema::create('dokumen_izin', function (Blueprint $table) {
            $table->id('id_dokumen');
            $table->unsignedBigInteger('id_izin');
            $table->string('nama_file', 255);
            $table->string('path_file', 500);
            $table->string('tipe_file', 50);
            $table->integer('ukuran_kb');
            $table->unsignedBigInteger('diunggah_oleh');
            $table->dateTime('diunggah_pada')->useCurrent();
            // Tidak pakai timestamps() karena dokumen tidak pernah diupdate, hanya upload/hapus

            $table->foreign('id_izin', 'fk_dokumen_izin')
                  ->references('id_izin')->on('pengajuan_izin')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('diunggah_oleh', 'fk_dokumen_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_izin');
    }
};
