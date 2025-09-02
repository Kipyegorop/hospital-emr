<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MedicationAdjustTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_stock_creates_adjustment_record()
    {
        // Create a minimal role and then user and medication
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'tester',
            'display_name' => 'Tester',
            'description' => 'Test role',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['role_id' => $roleId]);
        $med = Medication::factory()->create(['current_stock' => 10]);

        $this->actingAs($user, 'sanctum');

        $resp = $this->postJson("/api/medications/{$med->id}/adjust-stock", [
            'adjustment' => 5,
            'reason' => 'Test add'
        ]);

        $resp->assertStatus(200);
        $this->assertDatabaseHas('medication_stock_adjustments', [
            'medication_id' => $med->id,
            'user_id' => $user->id,
            'adjustment' => 5,
        ]);

        $med->refresh();
        $this->assertEquals(15, $med->current_stock);
    }
}
