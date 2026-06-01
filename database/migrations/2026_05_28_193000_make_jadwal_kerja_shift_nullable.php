<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->dropForeign('fk_jadwal_shift');
        });

        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->unsignedBigInteger('id_shift')->nullable()->change();
        });

        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->foreign('id_shift', 'fk_jadwal_shift')
                  ->references('id_shift')->on('shift')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        $fallbackShiftId = DB::table('shift')->orderBy('id_shift')->value('id_shift');

        if ($fallbackShiftId === null && DB::table('jadwal_kerja')->whereNull('id_shift')->exists()) {
            throw new RuntimeException('Tidak bisa rollback: jadwal libur dengan id_shift null membutuhkan data shift pengganti.');
        }

        DB::table('jadwal_kerja')
            ->whereNull('id_shift')
            ->update(['id_shift' => $fallbackShiftId]);

        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->dropForeign('fk_jadwal_shift');
        });

        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->unsignedBigInteger('id_shift')->nullable(false)->change();
        });

        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->foreign('id_shift', 'fk_jadwal_shift')
                  ->references('id_shift')->on('shift')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }
};
