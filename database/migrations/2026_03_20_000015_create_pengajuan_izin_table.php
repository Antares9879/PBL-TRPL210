<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur validasi izin: karyawan submit → admin_outsource validasi → HR verifikasi.
     * Dua kolom validator terpisah: divalidasi_admin dan diverifikasi_hr.
     */
    public function up(): void
    {
        Schema::create('pengajuan_izin', function (Blueprint $table) {
            $table->id('id_izin');
            $table->unsignedBigInteger('id_karyawan');
            $table->unsignedBigInteger('id_jenis_izin');
            $table->date('tanggal_izin');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_penolakan')->nullable();
            $table->enum('status_dokumen', [
                'belum_upload',
                'sudah_upload',
                'lengkap',
                'tidak_lengkap',
            ])->default('belum_upload');
            $table->text('catatan_dokumen')->nullable();
            $table->dateTime('diajukan_pada')->useCurrent();
            $table->unsignedBigInteger('divalidasi_admin')->nullable();
            $table->dateTime('waktu_validasi_admin')->nullable();
            $table->unsignedBigInteger('diverifikasi_hr')->nullable();
            $table->dateTime('waktu_verifikasi_hr')->nullable();
            $table->timestamps();

            $table->foreign('id_karyawan', 'fk_izin_karyawan')
                  ->references('id_karyawan')->on('karyawan')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_jenis_izin', 'fk_izin_jenis')
                  ->references('id_jenis_izin')->on('jenis_izin')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('divalidasi_admin', 'fk_izin_admin')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            $table->foreign('diverifikasi_hr', 'fk_izin_hr')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            $table->index(['id_karyawan', 'tanggal_izin'], 'idx_izin_karyawan');
            $table->index(['status', 'status_dokumen'], 'idx_izin_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_izin');
    }
};
