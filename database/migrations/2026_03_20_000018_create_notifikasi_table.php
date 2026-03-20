<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('id_penerima');
            $table->unsignedBigInteger('id_pengirim')->nullable();
            $table->string('judul', 200);
            $table->text('isi');
            $table->enum('jenis', ['absensi', 'lembur', 'izin', 'planning', 'sistem']);
            $table->unsignedBigInteger('id_referensi')->nullable();
            $table->boolean('is_dibaca')->default(false);
            $table->dateTime('dibaca_pada')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Tidak pakai updated_at — notifikasi tidak diupdate, hanya ditandai dibaca

            $table->foreign('id_penerima', 'fk_notif_penerima')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('id_pengirim', 'fk_notif_pengirim')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            $table->index(['id_penerima', 'is_dibaca'], 'idx_notif_penerima');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
