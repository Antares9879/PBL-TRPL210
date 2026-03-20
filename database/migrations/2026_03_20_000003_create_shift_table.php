<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift', function (Blueprint $table) {
            $table->id('id_shift');
            $table->string('nama_shift', 50);
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->integer('durasi_normal_menit')->default(480);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift');
    }
};
