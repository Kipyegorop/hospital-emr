<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Triage;
use App\Models\Consultation;
use App\Models\User;

class OpdAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opd_index_returns_aggregates()
    {
    $role = \App\Models\Role::create(['name' => 'receptionist', 'display_name' => 'Receptionist']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $this->actingAs($user, 'sanctum');

        // create sample data
        Appointment::factory()->createMany([
            ['patient_id' => 1, 'appointment_date' => now()->toDateString(), 'status' => 'scheduled'],
        ]);

        Triage::factory()->createMany([
            ['patient_id' => 1, 'triage_level' => 'non_urgent', 'queue_status' => 'waiting'],
        ]);

        Consultation::factory()->createMany([
            ['patient_id' => 1, 'doctor_id' => $user->id, 'status' => 'in_progress'],
        ]);

        $resp = $this->getJson('/api/opd');
        $resp->assertStatus(200)->assertJsonStructure(['status', 'data' => ['today' , 'lists']]);
    }
}
