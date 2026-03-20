<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * konfigurasi_area dibuat di Tahap 1 karena tidak ada FK ke tabel lain selain pengguna.
     * FK ke kolom diubah_oleh (→ pengguna) ditambahkan terpisah di Tahap 3
     * setelah tabel pengguna terbentuk.
     *
     * Lihat: 2026_03_20_000010_add_fk_diubah_oleh_to_konfigurasi_area.php
     */
    public function up(): void
    {
        Schema::create('konfigurasi_area', function (Blueprint $table) {
            $table->id('id_konfigurasi');
            $table->string('nama_area', 100);
            $table->decimal('latitude_pusat', 10, 8);
            $table->decimal('longitude_pusat', 11, 8);
            $table->unsignedInteger('radius_meter');
            $table->boolean('is_aktif')->default(true);
            // diubah_oleh — kolom tanpa FK dulu, FK ditambahkan di tahap 3
            $table->unsignedBigInteger('diubah_oleh');
            $table->timestamps();

            // Constraint: radius wajib > 0 (sesuai SQL asli CHECK (radius_meter > 0))
            // MySQL 8.0+ support CHECK constraint, versi lama di-enforce di application layer
            // via StoreKonfigurasiAreaRequest: 'radius_meter' => ['required', 'integer', 'min:1']
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_area');
    }
};
