<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            ConsultationSeeder::class,
            PrescriptionSeeder::class,
            WardSeeder::class,
            BedSeeder::class,
            LabTestSeeder::class,
            MedicationSeeder::class,
            BillSeeder::class,
            NhifClaimSeeder::class,
        ]);
    }
}
