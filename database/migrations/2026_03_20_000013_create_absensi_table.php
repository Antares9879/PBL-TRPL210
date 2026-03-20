<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id('id_absensi');
            $table->unsignedBigInteger('id_karyawan');
            $table->unsignedBigInteger('id_jadwal');
            $table->date('tanggal_absensi');

            // --- Check-In ---
            $table->dateTime('waktu_check_in')->nullable();
            $table->decimal('latitude_check_in', 10, 8)->nullable();
            $table->decimal('longitude_check_in', 11, 8)->nullable();
            $table->boolean('is_lokasi_valid_in')->nullable();

            // --- Check-Out ---
            $table->dateTime('waktu_check_out')->nullable();
            $table->decimal('latitude_check_out', 10, 8)->nullable();
            $table->decimal('longitude_check_out', 11, 8)->nullable();
            $table->boolean('is_lokasi_valid_out')->nullable();

            // --- Kalkulasi Menit (dihitung otomatis oleh AbsensiService) ---
            $table->integer('menit_kerja_normal')->default(0);    // maks. 480 menit
            $table->integer('menit_telat')->default(0);
            $table->integer('menit_pulang_cepat')->default(0);
            $table->integer('menit_kelebihan')->default(0);       // potensi lembur, belum disetujui

            // --- Status ---
            $table->enum('status_kehadiran', ['hadir', 'izin', 'alpa', 'pending'])->default('pending');
            $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_penolakan')->nullable();
            $table->unsignedBigInteger('divalidasi_oleh')->nullable();
            $table->dateTime('waktu_validasi')->nullable();

            $table->timestamps();

            $table->foreign('id_karyawan', 'fk_absensi_karyawan')
                  ->references('id_karyawan')->on('karyawan')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_jadwal', 'fk_absensi_jadwal')
                  ->references('id_jadwal')->on('jadwal_kerja')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('divalidasi_oleh', 'fk_absensi_validator')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            // Satu karyawan hanya boleh punya satu record absensi per hari
            $table->unique(['id_karyawan', 'tanggal_absensi'], 'uq_absensi_karyawan_tgl');

            $table->index(['id_karyawan', 'tanggal_absensi'], 'idx_absensi_karyawan_tgl');
            $table->index(['status_validasi', 'status_kehadiran'], 'idx_absensi_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
