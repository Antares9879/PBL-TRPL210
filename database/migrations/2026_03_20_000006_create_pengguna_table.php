<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pengguna menggantikan tabel users bawaan Laravel (Opsi A).
     *
     * Keputusan desain:
     *  - Kolom password diberi nama password_hash agar eksplisit bahwa nilai yang
     *    disimpan adalah hasil hashing, bukan plaintext.
     *  - Role disimpan sebagai ENUM di level database untuk mencegah nilai di luar
     *    domain yang valid, bukan hanya di application layer.
     *  - Tidak menyimpan id_departemen / id_perusahaan di sini — data spesifik
     *    per role disimpan di extension table (Table Per Type).
     *
     * Pastikan config/auth.php sudah diubah:
     *   'model' => App\Models\Pengguna::class
     * Dan di model Pengguna.php:
     *   protected $table = 'pengguna';
     *   protected $authPasswordName = 'password_hash';
     */
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', [
                'super_admin',
                'hr',
                'user_departemen',
                'admin_outsource',
                'karyawan',
            ]);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
