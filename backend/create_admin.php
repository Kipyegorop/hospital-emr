<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    // Create admin user
    $admin = User::updateOrCreate(
        ['email' => 'admin@smarthospital.co.ke'],
        [
            'name' => 'System Administrator',
            'email' => 'admin@smarthospital.co.ke',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'department_id' => 1,
            'phone' => '+254700000000',
            'employee_id' => 'EMP001',
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );

    echo "Admin user created successfully!\n";
    echo "Email: admin@smarthospital.co.ke\n";
    echo "Password: password123\n";

    // Also create a simple admin with the old credentials as backup
    $backup = User::updateOrCreate(
        ['email' => 'admin@smarthospital.com'],
        [
            'name' => 'Admin User',
            'email' => 'admin@smarthospital.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'department_id' => 1,
            'phone' => '+254700000001',
            'employee_id' => 'EMP002',
            'status' => 'active',
            'email_verified_at' => now(),
        ]
    );

    echo "Backup admin user created successfully!\n";
    echo "Email: admin@smarthospital.com\n";
    echo "Password: password\n";

} catch (Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
}
