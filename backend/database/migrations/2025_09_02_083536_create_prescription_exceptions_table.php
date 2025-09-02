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
        Schema::create('prescription_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('requested_by_pharmacist_id')->constrained('users'); // Pharmacist requesting exception
            $table->foreignId('approved_by_doctor_id')->nullable()->constrained('users'); // Doctor who approves/rejects

            // Exception Details
            $table->enum('exception_type', ['quantity_change', 'substitution', 'dosage_change', 'other']);
            $table->text('reason_for_exception'); // Why exception is needed
            $table->text('pharmacist_notes')->nullable();

            // Original vs Requested Values
            $table->integer('original_quantity');
            $table->integer('requested_quantity');
            $table->string('original_medication')->nullable();
            $table->string('requested_medication')->nullable();
            $table->string('original_dosage')->nullable();
            $table->string('requested_dosage')->nullable();

            // Workflow Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->datetime('requested_at');
            $table->datetime('responded_at')->nullable();
            $table->text('doctor_response')->nullable();
            $table->text('rejection_reason')->nullable();

            // Audit Trail
            $table->boolean('patient_notified')->default(false);
            $table->datetime('patient_notified_at')->nullable();
            $table->json('audit_log')->nullable(); // Track all changes

            $table->timestamps();

            // Indexes
            $table->index(['prescription_id', 'status']);
            $table->index(['requested_by_pharmacist_id', 'requested_at']);
            $table->index(['approved_by_doctor_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_exceptions');
    }
};
