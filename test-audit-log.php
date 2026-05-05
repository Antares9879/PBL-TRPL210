<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get super admin user
$user = App\Models\Pengguna::where('role', 'super_admin')->first();

if ($user) {
    // Create test audit log
    App\Services\AuditLogService::catat(
        pengguna: $user,
        jenis: App\Models\AuditLog::JENIS_AUTH,
        idReferensi: $user->id_pengguna,
        aksi: App\Models\AuditLog::AKSI_LOGIN,
        catatan: 'Test login dari script',
        ipAddress: '127.0.0.1'
    );
    
    echo "✓ Test audit log created successfully\n";
    echo "Total audit logs: " . App\Models\AuditLog::count() . "\n";
} else {
    echo "✗ No super admin user found\n";
}
