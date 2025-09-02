<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = [
            [
                'patient_number' => 'P20250001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'middle_name' => 'M',
                'date_of_birth' => '1985-03-15',
                'gender' => 'male',
                'phone' => '+254700123001',
                'email' => 'john.doe@email.com',
                'nhif_number' => 'NHIF001234567',
                'id_number' => '12345678',
                'emergency_contact_name' => 'Jane Doe',
                'emergency_contact_phone' => '+254700123002',
                'emergency_contact_relationship' => 'Spouse',
                'address_line_1' => '123 Main Street',
                'city' => 'Nairobi',
                'county' => 'Nairobi',
                'country' => 'Kenya',
                'allergies' => 'Penicillin',
                'medical_history' => 'Hypertension, Diabetes Type 2',
                'current_medications' => 'Metformin 500mg, Amlodipine 5mg',
                'blood_type' => 'O+',
                'height' => 175.0,
                'weight' => 80.0,
                'insurance_provider' => 'NHIF',
                'payment_method' => 'nhif',
                'status' => 'active',
            ],
            [
                'patient_number' => 'P20250002',
                'first_name' => 'Mary',
                'last_name' => 'Wanjiku',
                'middle_name' => 'A',
                'date_of_birth' => '1990-07-22',
                'gender' => 'female',
                'phone' => '+254700123003',
                'email' => 'mary.wanjiku@email.com',
                'nhif_number' => 'NHIF001234568',
                'id_number' => '12345679',
                'emergency_contact_name' => 'Peter Wanjiku',
                'emergency_contact_phone' => '+254700123004',
                'emergency_contact_relationship' => 'Spouse',
                'address_line_1' => '456 Oak Avenue',
                'city' => 'Mombasa',
                'county' => 'Mombasa',
                'country' => 'Kenya',
                'allergies' => 'None',
                'medical_history' => 'Asthma',
                'current_medications' => 'Salbutamol inhaler',
                'blood_type' => 'A+',
                'height' => 162.0,
                'weight' => 55.0,
                'insurance_provider' => 'NHIF',
                'payment_method' => 'nhif',
                'status' => 'active',
            ],
            [
                'patient_number' => 'P20250003',
                'first_name' => 'David',
                'last_name' => 'Ochieng',
                'middle_name' => 'K',
                'date_of_birth' => '1978-11-08',
                'gender' => 'male',
                'phone' => '+254700123005',
                'email' => 'david.ochieng@email.com',
                'nhif_number' => 'NHIF001234569',
                'id_number' => '12345680',
                'emergency_contact_name' => 'Sarah Ochieng',
                'emergency_contact_phone' => '+254700123006',
                'emergency_contact_relationship' => 'Spouse',
                'address_line_1' => '789 Pine Road',
                'city' => 'Kisumu',
                'county' => 'Kisumu',
                'country' => 'Kenya',
                'allergies' => 'Sulfa drugs',
                'medical_history' => 'Hypertension',
                'current_medications' => 'Lisinopril 10mg',
                'blood_type' => 'B+',
                'height' => 180.0,
                'weight' => 85.0,
                'insurance_provider' => 'NHIF',
                'payment_method' => 'nhif',
                'status' => 'active',
            ],
        ];

        foreach ($patients as $patientData) {
            // Ensure required unique UHID is present — generate if missing
            if (empty($patientData['uhid'])) {
                $patientData['uhid'] = Patient::generateUhid();
            }

            // Ensure patient_number exists; if not, generate one
            if (empty($patientData['patient_number'])) {
                $patientData['patient_number'] = Patient::generatePatientNumber();
            }

            Patient::create($patientData);
        }
    }
}
