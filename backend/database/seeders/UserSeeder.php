<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles and departments
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $doctorRole = Role::where('name', 'doctor')->first();
        $nurseRole = Role::where('name', 'nurse')->first();
        $pharmacistRole = Role::where('name', 'pharmacist')->first();
        $labTechRole = Role::where('name', 'lab_tech')->first();
        $receptionistRole = Role::where('name', 'receptionist')->first();

        $adminDept = Department::where('code', 'ADMIN')->first();
        $generalMedDept = Department::where('code', 'GMED')->first();
        $cardiologyDept = Department::where('code', 'CARD')->first();
        $pediatricsDept = Department::where('code', 'PED')->first();
        $surgeryDept = Department::where('code', 'SURG')->first();
        $pharmacyDept = Department::where('code', 'PHARM')->first();
        $labDept = Department::where('code', 'LAB')->first();
        $financeDept = Department::where('code', 'FIN')->first();

        $users = [
            // Super Admin
            [
                'name' => 'System Administrator',
                'email' => 'admin@smarthospital.co.ke',
                'phone' => '+254700000001',
                'password' => Hash::make('password123'),
                'role_id' => $superAdminRole->id,
                'department_id' => $adminDept->id,
                'employee_id' => 'EMP001',
                'status' => 'active',
            ],
            // Hospital Administrator
            [
                'name' => 'Hospital Manager',
                'email' => 'manager@smarthospital.co.ke',
                'phone' => '+254700000002',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'department_id' => $adminDept->id,
                'employee_id' => 'EMP002',
                'status' => 'active',
            ],
            // Doctors
            [
                'name' => 'Dr. John Kamau',
                'email' => 'dr.kamau@smarthospital.co.ke',
                'phone' => '+254700000003',
                'password' => Hash::make('password123'),
                'role_id' => $doctorRole->id,
                'department_id' => $generalMedDept->id,
                'employee_id' => 'EMP003',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Sarah Wanjiku',
                'email' => 'dr.wanjiku@smarthospital.co.ke',
                'phone' => '+254700000004',
                'password' => Hash::make('password123'),
                'role_id' => $doctorRole->id,
                'department_id' => $cardiologyDept->id,
                'employee_id' => 'EMP004',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Michael Ochieng',
                'email' => 'dr.ochieng@smarthospital.co.ke',
                'phone' => '+254700000005',
                'password' => Hash::make('password123'),
                'role_id' => $doctorRole->id,
                'department_id' => $pediatricsDept->id,
                'employee_id' => 'EMP005',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Grace Akinyi',
                'email' => 'dr.akinyi@smarthospital.co.ke',
                'phone' => '+254700000006',
                'password' => Hash::make('password123'),
                'role_id' => $doctorRole->id,
                'department_id' => $surgeryDept->id,
                'employee_id' => 'EMP006',
                'status' => 'active',
            ],
            // Nurses
            [
                'name' => 'Nurse Mary Njeri',
                'email' => 'nurse.njeri@smarthospital.co.ke',
                'phone' => '+254700000007',
                'password' => Hash::make('password123'),
                'role_id' => $nurseRole->id,
                'department_id' => $generalMedDept->id,
                'employee_id' => 'EMP007',
                'status' => 'active',
            ],
            [
                'name' => 'Nurse Peter Odhiambo',
                'email' => 'nurse.odhiambo@smarthospital.co.ke',
                'phone' => '+254700000008',
                'password' => Hash::make('password123'),
                'role_id' => $nurseRole->id,
                'department_id' => $cardiologyDept->id,
                'employee_id' => 'EMP008',
                'status' => 'active',
            ],
            // Pharmacist
            [
                'name' => 'Pharmacist James Mwangi',
                'email' => 'pharmacist.mwangi@smarthospital.co.ke',
                'phone' => '+254700000009',
                'password' => Hash::make('password123'),
                'role_id' => $pharmacistRole->id,
                'department_id' => $pharmacyDept->id,
                'employee_id' => 'EMP009',
                'status' => 'active',
            ],
            // Lab Technician
            [
                'name' => 'Lab Tech Lucy Achieng',
                'email' => 'labtech.achieng@smarthospital.co.ke',
                'phone' => '+254700000010',
                'password' => Hash::make('password123'),
                'role_id' => $labTechRole->id,
                'department_id' => $labDept->id,
                'employee_id' => 'EMP010',
                'status' => 'active',
            ],
            // Receptionist
            [
                'name' => 'Receptionist David Kiprop',
                'email' => 'reception.kiprop@smarthospital.co.ke',
                'phone' => '+254700000011',
                'password' => Hash::make('password123'),
                'role_id' => $receptionistRole->id,
                'department_id' => $adminDept->id,
                'employee_id' => 'EMP011',
                'status' => 'active',
            ],
            // Finance Officer
            [
                'name' => 'Finance Officer Jane Wambui',
                'email' => 'finance.wambui@smarthospital.co.ke',
                'phone' => '+254700000012',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'department_id' => $financeDept->id,
                'employee_id' => 'EMP012',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Update department heads
        $adminDept->update(['head_id' => User::where('email', 'admin@smarthospital.co.ke')->first()->id]);
        $generalMedDept->update(['head_id' => User::where('email', 'dr.kamau@smarthospital.co.ke')->first()->id]);
        $cardiologyDept->update(['head_id' => User::where('email', 'dr.wanjiku@smarthospital.co.ke')->first()->id]);
        $pediatricsDept->update(['head_id' => User::where('email', 'dr.ochieng@smarthospital.co.ke')->first()->id]);
        $surgeryDept->update(['head_id' => User::where('email', 'dr.akinyi@smarthospital.co.ke')->first()->id]);
        $pharmacyDept->update(['head_id' => User::where('email', 'pharmacist.mwangi@smarthospital.co.ke')->first()->id]);
        $labDept->update(['head_id' => User::where('email', 'labtech.achieng@smarthospital.co.ke')->first()->id]);
        $financeDept->update(['head_id' => User::where('email', 'finance.wambui@smarthospital.co.ke')->first()->id]);
    }
}
