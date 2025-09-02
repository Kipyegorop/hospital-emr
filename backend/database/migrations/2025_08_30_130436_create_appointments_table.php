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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users'); // Doctor assigned
            $table->foreignId('department_id')->constrained('departments');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->time('estimated_duration')->default('00:30:00'); // Default 30 minutes
            $table->enum('appointment_type', ['consultation', 'follow_up', 'emergency', 'routine_checkup', 'specialist']);
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show']);
            $table->text('reason_for_visit')->nullable();
            $table->text('notes')->nullable();
            $table->string('queue_number')->nullable(); // For queue management
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'waived'])->default('pending');
            $table->timestamps();
            
            // Indexes
            $table->index(['appointment_date', 'appointment_time']);
            $table->index(['doctor_id', 'appointment_date']);
            $table->index('status');
            $table->index('queue_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
