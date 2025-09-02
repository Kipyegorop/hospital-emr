<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ward name (e.g., "General Ward A", "ICU", "Maternity")
            $table->string('code')->unique(); // Ward code (e.g., "GW-A", "ICU", "MAT")
            $table->foreignId('department_id')->constrained('departments');
            $table->enum('ward_type', ['general', 'icu', 'maternity', 'pediatric', 'surgical', 'emergency', 'private']);
            
            // Capacity and Location
            $table->integer('total_beds');
            $table->integer('available_beds');
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->text('location_description')->nullable();
            
            // Staffing
            $table->foreignId('ward_sister_id')->nullable()->constrained('users'); // Head nurse
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            
            // Features and Equipment
            $table->json('special_equipment')->nullable(); // Ventilators, monitors, etc.
            $table->boolean('has_private_rooms')->default(false);
            $table->boolean('has_air_conditioning')->default(false);
            $table->boolean('has_oxygen_supply')->default(true);
            
            // Status
            $table->enum('status', ['active', 'maintenance', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['department_id', 'ward_type']);
            $table->index('status');
            $table->index('available_beds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
