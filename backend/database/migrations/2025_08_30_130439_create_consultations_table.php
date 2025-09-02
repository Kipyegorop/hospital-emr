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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');
            
            // Vital Signs
            $table->decimal('temperature', 4, 1)->nullable(); // in Celsius
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('heart_rate')->nullable(); // beats per minute
            $table->integer('respiratory_rate')->nullable(); // breaths per minute
            $table->decimal('height', 5, 2)->nullable(); // in cm
            $table->decimal('weight', 5, 2)->nullable(); // in kg
            $table->decimal('bmi', 4, 2)->nullable();
            $table->decimal('oxygen_saturation', 4, 1)->nullable(); // percentage
            
            // Consultation Details
            $table->text('chief_complaint'); // Main reason for visit
            $table->text('history_of_present_illness')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('review_of_systems')->nullable();
            
            // Physical Examination
            $table->text('physical_examination')->nullable();
            $table->text('assessment')->nullable(); // Diagnosis/impression
            
            // Treatment Plan
            $table->text('treatment_plan')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('follow_up_instructions')->nullable();
            
            // Additional Information
            $table->enum('consultation_type', ['initial', 'follow_up', 'emergency', 'routine']);
            $table->enum('status', ['in_progress', 'completed', 'pending_review']);
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // Lab results, images, etc.
            
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
