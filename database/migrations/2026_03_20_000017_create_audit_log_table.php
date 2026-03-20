<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setiap aksi approve/reject/create/update wajib menulis entri ke tabel ini.
     * Implementasi penulisan log ada di NotifikasiService / masing-masing Service,
     * bukan di Controller langsung.
     */
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id('id_log');
            $table->unsignedBigInteger('id_pengguna');
            $table->enum('role_pelaku', [
                'admin_outsource',
                'user_departemen',
                'hr',
                'super_admin',
                'sistem',
            ]);
            $table->enum('jenis_data', [
                'absensi',
                'lembur',
                'izin',
                'planning',
                'akun',
                'master_data',
                'konfigurasi',
            ]);
            $table->unsignedBigInteger('id_referensi');
            $table->enum('aksi', [
                'approve',
                'reject',
                'create',
                'update',
                'deactivate',
                'upload',
            ]);
            $table->text('catatan')->nullable();
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('waktu_aksi')->useCurrent();
            // Tidak pakai timestamps() — audit log tidak boleh diubah setelah ditulis

            $table->foreign('id_pengguna', 'fk_audit_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index(['waktu_aksi', 'jenis_data'], 'idx_audit_waktu');
            $table->index(['id_pengguna', 'waktu_aksi'], 'idx_audit_pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
