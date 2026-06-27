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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Tambahkan 'karyawan' ke enum role_pelaku
        DB::statement("ALTER TABLE audit_log MODIFY COLUMN role_pelaku ENUM(
            'admin_outsource',
            'user_departemen',
            'hr',
            'super_admin',
            'karyawan',
            'sistem'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Kembalikan ke nilai enum sebelumnya (tanpa karyawan)
        DB::statement("ALTER TABLE audit_log MODIFY COLUMN role_pelaku ENUM(
            'admin_outsource',
            'user_departemen',
            'hr',
            'super_admin',
            'sistem'
        ) NOT NULL");
    }
};