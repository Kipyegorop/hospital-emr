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
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->string('encounter_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('attending_doctor_id')->nullable()->constrained('users');
            $table->enum('encounter_type', ['outpatient', 'inpatient', 'emergency', 'consultation']);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('encounter_number');
            $table->index(['patient_id', 'status']);
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
