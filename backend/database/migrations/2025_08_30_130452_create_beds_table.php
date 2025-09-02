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
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained('wards')->onDelete('cascade');
            $table->string('bed_number'); // Bed number within the ward
            $table->string('bed_code')->unique(); // Unique bed identifier across hospital
            
            // Bed Details
            $table->enum('bed_type', ['standard', 'private', 'icu', 'maternity', 'pediatric', 'emergency']);
            $table->enum('bed_size', ['standard', 'large', 'extra_large', 'pediatric']);
            $table->boolean('has_oxygen')->default(false);
            $table->boolean('has_monitor')->default(false);
            $table->boolean('has_ventilator')->default(false);
            $table->text('special_features')->nullable(); // Special equipment or features
            
            // Current Status
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved', 'cleaning']);
            $table->foreignId('current_patient_id')->nullable()->constrained('patients');
            $table->timestamp('occupied_at')->nullable();
            $table->timestamp('expected_vacancy_at')->nullable();
            
            // Bed Assignment History
            $table->json('bed_history')->nullable(); // Previous patients and dates
            
            // Maintenance
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->text('maintenance_notes')->nullable();
            
            // Cost
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->enum('rate_type', ['standard', 'private', 'icu', 'special'])->default('standard');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['ward_id', 'bed_number']);
            $table->index('status');
            $table->index('current_patient_id');
            $table->index('bed_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
