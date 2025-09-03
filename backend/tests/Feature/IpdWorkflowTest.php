<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Patient;

class IpdWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admit_transfer_discharge_flow()
    {
        // Seed minimal data
    // Ensure role exists for user factory
    $role = \App\Models\Role::create(['name' => 'doctor', 'display_name' => 'Doctor']);
    $user = User::factory()->create(['role_id' => $role->id]);
        $user->refresh();
        $this->assertNotNull($user->role, 'User must have a role');
        $this->assertEquals('doctor', $user->role->name);
        Sanctum::actingAs($user);

        $ward = Ward::factory()->create(['name' => 'Test Ward']);
        $bed1 = Bed::factory()->create(['ward_id' => $ward->id, 'bed_number' => 'B1', 'is_occupied' => false]);
        $bed2 = Bed::factory()->create(['ward_id' => $ward->id, 'bed_number' => 'B2', 'is_occupied' => false]);
        $patient = Patient::factory()->create();

        // Admit
        $resp = $this->postJson('/api/ipd/admit', [
            'patient_id' => $patient->id,
            'ward_id' => $ward->id,
            'bed_id' => $bed1->id,
        ]);

        $resp->assertStatus(201)->assertJson(['status' => 'success']);

        // Transfer
        $resp2 = $this->postJson('/api/ipd/transfer', [
            'from_bed_id' => $bed1->id,
            'to_bed_id' => $bed2->id,
        ]);

        $resp2->assertStatus(200)->assertJson(['status' => 'success']);

        // Discharge: find encounter id
        $encounterId = $resp->json('data.id');

        $resp3 = $this->postJson('/api/ipd/discharge', [
            'encounter_id' => $encounterId,
            'discharge_summary' => 'All good',
        ]);

        $resp3->assertStatus(200)->assertJson(['status' => 'success']);
    }
}
