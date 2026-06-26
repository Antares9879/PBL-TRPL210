<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::table('dokumen_izin', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->after('path_file');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_izin', function (Blueprint $table) {
            $table->dropColumn('cloudinary_public_id');
        });
    }
};
