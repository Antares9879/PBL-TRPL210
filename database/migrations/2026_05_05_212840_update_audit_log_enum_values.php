<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hanya jalankan di MySQL — syntax MODIFY COLUMN tidak support SQLite
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE audit_log MODIFY COLUMN jenis_data ENUM(
            'absensi',
            'lembur',
            'izin',
            'planning',
            'akun',
            'master_data',
            'konfigurasi',
            'auth'
        ) NOT NULL");

        DB::statement("ALTER TABLE audit_log MODIFY COLUMN aksi ENUM(
            'approve',
            'reject',
            'create',
            'update',
            'deactivate',
            'upload',
            'login',
            'logout',
            'activate'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE audit_log MODIFY COLUMN jenis_data ENUM(
            'absensi',
            'lembur',
            'izin',
            'planning',
            'akun',
            'master_data',
            'konfigurasi'
        ) NOT NULL");

        DB::statement("ALTER TABLE audit_log MODIFY COLUMN aksi ENUM(
            'approve',
            'reject',
            'create',
            'update',
            'deactivate',
            'upload'
        ) NOT NULL");
    }
};