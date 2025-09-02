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
        Schema::create('theatre_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('procedure_id')->constrained('procedures');
            $table->foreignId('primary_surgeon_id')->constrained('users');
            $table->foreignId('anesthetist_id')->nullable()->constrained('users');
            $table->foreignId('theatre_room_id')->nullable()->constrained('departments'); // Theatre room
            $table->foreignId('encounter_id')->nullable()->constrained('encounters');

            // Scheduling Information
            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->integer('estimated_duration_minutes');
            $table->enum('priority', ['elective', 'urgent', 'emergency'])->default('elective');
            $table->enum('session', ['morning', 'afternoon', 'evening', 'night'])->default('morning');

            // Procedure Details
            $table->string('procedure_name'); // Denormalized for quick access
            $table->text('procedure_notes')->nullable();
            $table->enum('anesthesia_type', ['local', 'regional', 'general', 'sedation', 'none'])->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('surgeon_notes')->nullable();

            // Pre-operative Information
            $table->json('pre_op_checklist')->nullable(); // Checklist items and status
            $table->boolean('pre_op_completed')->default(false);
            $table->datetime('pre_op_completed_at')->nullable();
            $table->foreignId('pre_op_completed_by')->nullable()->constrained('users');
            $table->text('pre_op_notes')->nullable();

            // Consent Management
            $table->boolean('consent_obtained')->default(false);
            $table->datetime('consent_obtained_at')->nullable();
            $table->foreignId('consent_obtained_by')->nullable()->constrained('users');
            $table->text('consent_notes')->nullable();
            $table->string('consent_form_path')->nullable(); // File storage path

            // Staff Assignment
            $table->json('assigned_staff')->nullable(); // Staff roles and assignments
            $table->json('scrub_nurses')->nullable(); // Assigned scrub nurses
            $table->json('circulating_nurses')->nullable(); // Assigned circulating nurses
            $table->json('other_staff')->nullable(); // Technicians, residents, etc.

            // Equipment & Resources
            $table->json('required_equipment')->nullable(); // Equipment needed
            $table->json('equipment_checklist')->nullable(); // Equipment check status
            $table->boolean('equipment_ready')->default(false);
            $table->datetime('equipment_checked_at')->nullable();
            $table->foreignId('equipment_checked_by')->nullable()->constrained('users');

            // Consumables & Implants
            $table->json('consumables_list')->nullable(); // Required consumables
            $table->json('implants_list')->nullable(); // Required implants with serial numbers
            $table->decimal('consumables_cost', 10, 2)->default(0);
            $table->decimal('implants_cost', 10, 2)->default(0);
            $table->boolean('consumables_ready')->default(false);

            // Status Tracking
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->datetime('actual_start_time')->nullable();
            $table->datetime('actual_end_time')->nullable();
            $table->integer('actual_duration_minutes')->nullable();

            // Cancellation/Postponement
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->datetime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('rescheduled_date')->nullable();
            $table->time('rescheduled_time')->nullable();

            // Post-operative
            $table->text('post_op_instructions')->nullable();
            $table->text('complications')->nullable();
            $table->enum('outcome', ['successful', 'complicated', 'failed'])->nullable();
            $table->text('outcome_notes')->nullable();

            // Billing Integration
            $table->decimal('total_procedure_cost', 10, 2)->nullable();
            $table->boolean('charges_posted')->default(false);
            $table->datetime('charges_posted_at')->nullable();
            $table->foreignId('charges_posted_by')->nullable()->constrained('users');

            // Quality & Audit
            $table->json('quality_metrics')->nullable(); // Time metrics, complications, etc.
            $table->boolean('audit_required')->default(false);
            $table->text('audit_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['scheduled_date', 'scheduled_start_time']);
            $table->index(['patient_id', 'scheduled_date']);
            $table->index(['primary_surgeon_id', 'scheduled_date']);
            $table->index(['theatre_room_id', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
            $table->index('priority');
            $table->index('schedule_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theatre_schedules');
    }
};
