<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan lembur bisa retroaktif maksimal H+1.
     * Batas pengajuan divalidasi di LemburService, bukan di sini.
     * Status 'kadaluarsa' di-set otomatis oleh sistem jika lewat batas_pengajuan.
     */
    public function up(): void
    {
        Schema::create('pengajuan_lembur', function (Blueprint $table) {
            $table->id('id_lembur');
            $table->unsignedBigInteger('id_karyawan');
            $table->unsignedBigInteger('id_absensi');
            $table->date('tanggal_lembur');
            $table->time('jam_mulai_estimasi');
            $table->time('jam_selesai_estimasi');
            $table->integer('menit_lembur_diajukan')->default(0);
            $table->integer('menit_lembur_resmi')->default(0);
            $table->text('alasan_lembur');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'kadaluarsa'])->default('menunggu');
            $table->text('catatan_penolakan')->nullable();
            $table->date('batas_pengajuan');
            $table->dateTime('diajukan_pada')->useCurrent();
            $table->unsignedBigInteger('diproses_oleh')->nullable();
            $table->dateTime('waktu_proses')->nullable();
            $table->timestamps();

            $table->foreign('id_karyawan', 'fk_lembur_karyawan')
                  ->references('id_karyawan')->on('karyawan')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('id_absensi', 'fk_lembur_absensi')
                  ->references('id_absensi')->on('absensi')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('diproses_oleh', 'fk_lembur_prosesor')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('set null');

            $table->index(['status', 'batas_pengajuan'], 'idx_lembur_status');
            $table->index(['id_karyawan', 'tanggal_lembur'], 'idx_lembur_karyawan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_lembur');
    }
};
