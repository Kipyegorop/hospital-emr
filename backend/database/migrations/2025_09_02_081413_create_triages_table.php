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
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users'); // Nurse who performed triage
            $table->foreignId('department_id')->nullable()->constrained('departments');

            // Vital Signs
            $table->decimal('temperature', 4, 1)->nullable(); // Celsius
            $table->integer('systolic_bp')->nullable(); // mmHg
            $table->integer('diastolic_bp')->nullable(); // mmHg
            $table->integer('heart_rate')->nullable(); // BPM
            $table->integer('respiratory_rate')->nullable(); // per minute
            $table->decimal('oxygen_saturation', 5, 2)->nullable(); // %
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('bmi', 5, 2)->nullable(); // calculated

            // Triage Assessment
            $table->enum('triage_level', ['emergency', 'urgent', 'semi_urgent', 'non_urgent', 'fast_track'])
                  ->default('non_urgent');
            $table->string('triage_color', 20)->default('green'); // red, orange, yellow, green, blue
            $table->text('chief_complaint')->nullable();
            $table->text('presenting_symptoms')->nullable();
            $table->text('pain_scale')->nullable(); // 1-10 scale
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('assessment_notes')->nullable();

            // Queue Management
            $table->integer('queue_number')->nullable();
            $table->enum('queue_status', ['waiting', 'called', 'in_progress', 'completed', 'cancelled'])
                  ->default('waiting');
            $table->timestamp('queue_time')->nullable();
            $table->timestamp('called_time')->nullable();
            $table->timestamp('started_time')->nullable();
            $table->timestamp('completed_time')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['patient_id', 'created_at']);
            $table->index(['triage_level', 'queue_status']);
            $table->index(['queue_status', 'queue_time']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triages');
    }
};
