<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'General Medicine',
                'code' => 'GMED',
                'description' => 'General medical consultations and treatment',
                'location' => 'Ground Floor, Building A',
                'contact_number' => '+254700123456',
                'email' => 'general.medicine@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Cardiology',
                'code' => 'CARD',
                'description' => 'Heart and cardiovascular care',
                'location' => 'First Floor, Building A',
                'contact_number' => '+254700123457',
                'email' => 'cardiology@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Pediatrics',
                'code' => 'PED',
                'description' => 'Child and adolescent healthcare',
                'location' => 'Ground Floor, Building B',
                'contact_number' => '+254700123458',
                'email' => 'pediatrics@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Surgery',
                'code' => 'SURG',
                'description' => 'Surgical procedures and operations',
                'location' => 'Second Floor, Building A',
                'contact_number' => '+254700123459',
                'email' => 'surgery@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Obstetrics & Gynecology',
                'code' => 'OBGYN',
                'description' => 'Women\'s health and maternity care',
                'location' => 'First Floor, Building B',
                'contact_number' => '+254700123460',
                'email' => 'obgyn@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Medicine',
                'code' => 'EMER',
                'description' => 'Emergency and urgent care services',
                'location' => 'Ground Floor, Building C',
                'contact_number' => '+254700123461',
                'email' => 'emergency@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Laboratory Services',
                'code' => 'LAB',
                'description' => 'Medical laboratory testing and diagnostics',
                'location' => 'Basement, Building A',
                'contact_number' => '+254700123462',
                'email' => 'laboratory@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Radiology',
                'code' => 'RAD',
                'description' => 'Medical imaging and diagnostic radiology',
                'location' => 'Basement, Building A',
                'contact_number' => '+254700123463',
                'email' => 'radiology@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Pharmacy',
                'code' => 'PHARM',
                'description' => 'Medication dispensing and pharmaceutical services',
                'location' => 'Ground Floor, Building A',
                'contact_number' => '+254700123464',
                'email' => 'pharmacy@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Nursing',
                'code' => 'NURS',
                'description' => 'Nursing care and patient support services',
                'location' => 'All Floors',
                'contact_number' => '+254700123465',
                'email' => 'nursing@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Administration',
                'code' => 'ADMIN',
                'description' => 'Hospital administration and management',
                'location' => 'Ground Floor, Building A',
                'contact_number' => '+254700123466',
                'email' => 'admin@smarthospital.co.ke',
                'is_active' => true,
            ],
            [
                'name' => 'Finance & Billing',
                'code' => 'FIN',
                'description' => 'Financial services, billing, and NHIF claims',
                'location' => 'Ground Floor, Building A',
                'contact_number' => '+254700123467',
                'email' => 'finance@smarthospital.co.ke',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::create($departmentData);
        }
    }
}
