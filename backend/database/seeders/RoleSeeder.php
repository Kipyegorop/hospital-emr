<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'users.*', 'roles.*', 'departments.*', 'patients.*', 'appointments.*',
                    'consultations.*', 'prescriptions.*', 'wards.*', 'beds.*', 'lab_tests.*',
                    'medications.*', 'bills.*', 'nhif_claims.*', 'reports.*', 'settings.*'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Hospital administration with most permissions',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'departments.*', 'patients.*',
                    'appointments.*', 'consultations.*', 'prescriptions.*', 'wards.*', 'beds.*',
                    'lab_tests.*', 'medications.*', 'bills.*', 'nhif_claims.*', 'reports.*'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'doctor',
                'display_name' => 'Doctor',
                'description' => 'Medical doctor with patient care permissions',
                'permissions' => [
                    'patients.view', 'patients.edit', 'appointments.*', 'consultations.*',
                    'prescriptions.*', 'lab_tests.*', 'bills.view', 'nhif_claims.view'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'nurse',
                'display_name' => 'Nurse',
                'description' => 'Nursing staff with patient care permissions',
                'permissions' => [
                    'patients.view', 'patients.edit', 'appointments.view', 'consultations.view',
                    'prescriptions.view', 'lab_tests.view', 'wards.view', 'beds.view'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'pharmacist',
                'display_name' => 'Pharmacist',
                'description' => 'Pharmacy staff with medication permissions',
                'permissions' => [
                    'patients.view', 'prescriptions.*', 'medications.*', 'bills.view'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'lab_tech',
                'display_name' => 'Laboratory Technician',
                'description' => 'Laboratory staff with lab test permissions',
                'permissions' => [
                    'patients.view', 'lab_tests.*', 'bills.view'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'receptionist',
                'display_name' => 'Receptionist',
                'description' => 'Front desk staff with basic permissions',
                'permissions' => [
                    'patients.*', 'appointments.*', 'bills.view', 'nhif_claims.view'
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }
}
