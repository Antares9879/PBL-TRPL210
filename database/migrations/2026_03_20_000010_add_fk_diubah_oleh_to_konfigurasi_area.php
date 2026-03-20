<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan FK diubah_oleh → pengguna pada tabel konfigurasi_area.
     *
     * FK ini tidak bisa ditambahkan saat konfigurasi_area pertama dibuat (Tahap 1)
     * karena tabel pengguna belum ada. Sesuai catatan di STRUKTUR-FOLDER.md Tahap 3.
     */
    public function up(): void
    {
        Schema::table('konfigurasi_area', function (Blueprint $table) {
            $table->foreign('diubah_oleh', 'fk_konfigurasi_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('konfigurasi_area', function (Blueprint $table) {
            $table->dropForeign('fk_konfigurasi_pengguna');
        });
    }
};
