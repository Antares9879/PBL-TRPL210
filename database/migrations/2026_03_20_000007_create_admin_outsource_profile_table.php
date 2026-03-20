<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extension table untuk role admin_outsource (Table Per Type).
     * Relasi 1:1 dengan pengguna — satu admin hanya mewakili satu perusahaan outsource.
     */
    public function up(): void
    {
        Schema::create('admin_outsource_profile', function (Blueprint $table) {
            $table->id('id_profile');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->unsignedBigInteger('id_perusahaan');
            $table->timestamps();

            $table->foreign('id_pengguna', 'fk_aoprofile_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('id_perusahaan', 'fk_aoprofile_perusahaan')
                  ->references('id_perusahaan')->on('perusahaan_outsource')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('id_perusahaan', 'idx_aoprofile_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_outsource_profile');
    }
};
