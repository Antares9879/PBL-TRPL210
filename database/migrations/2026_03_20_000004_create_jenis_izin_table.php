<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_izin', function (Blueprint $table) {
            $table->id('id_jenis_izin');
            $table->string('nama_jenis', 50);
            $table->boolean('wajib_dokumen')->default(false);
            $table->string('keterangan', 200)->nullable();
            // timestamps() diperlukan karena model JenisIzin tidak set public $timestamps = false
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_izin');
    }
};
