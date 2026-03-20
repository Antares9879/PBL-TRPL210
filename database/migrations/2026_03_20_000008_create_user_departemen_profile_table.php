<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extension table untuk role user_departemen (Table Per Type).
     * Relasi 1:1 dengan pengguna — satu User Departemen bertanggung jawab atas satu departemen.
     */
    public function up(): void
    {
        Schema::create('user_departemen_profile', function (Blueprint $table) {
            $table->id('id_profile');
            $table->unsignedBigInteger('id_pengguna')->unique();
            $table->unsignedBigInteger('id_departemen');
            $table->timestamps();

            $table->foreign('id_pengguna', 'fk_udprofile_pengguna')
                  ->references('id_pengguna')->on('pengguna')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->foreign('id_departemen', 'fk_udprofile_departemen')
                  ->references('id_departemen')->on('departemen')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('id_departemen', 'idx_udprofile_departemen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_departemen_profile');
    }
};
