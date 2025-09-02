<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    // Delete any existing admin users first
    DB::table('users')->where('email', 'admin@hospital.com')->delete();
    DB::table('users')->where('email', 'admin@smarthospital.com')->delete();

    // Create simple admin user using direct DB insert
    $adminId = DB::table('users')->insertGetId([
        'name' => 'Admin User',
        'email' => 'admin@hospital.com',
        'password' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // This is 'password'
        'phone' => '+254700000000',
        'employee_id' => 'ADMIN001',
        'status' => 'active',
        'email_verified_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    echo "✅ Admin user created successfully!\n";
    echo "📧 Email: admin@hospital.com\n";
    echo "🔑 Password: password\n";
    echo "🎯 User ID: " . $adminId . "\n";

    // Also create backup with different credentials
    $backupId = DB::table('users')->insertGetId([
        'name' => 'Admin User',
        'email' => 'admin@smarthospital.com',
        'password' => Hash::make('admin123'),
        'phone' => '+254700000001',
        'employee_id' => 'ADMIN002',
        'status' => 'active',
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "\n✅ Backup admin user created!\n";
    echo "📧 Email: admin@smarthospital.com\n";
    echo "🔑 Password: admin123\n";
    echo "🎯 User ID: " . $backupId . "\n";

    // Verify users were created
    $count = DB::table('users')->count();
    echo "\n📊 Total users in database: " . $count . "\n";

    echo "\n🎉 You can now login with either account!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📋 Stack trace: " . $e->getTraceAsString() . "\n";
}
