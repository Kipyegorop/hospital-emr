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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('cascade');
            $table->foreignId('ordering_physician_id')->constrained('users'); // Doctor who placed the order
            $table->foreignId('department_id')->constrained('departments'); // Ordering department
            $table->foreignId('target_department_id')->nullable()->constrained('departments'); // Department to fulfill order

            // Order Classification
            $table->enum('order_type', ['laboratory', 'radiology', 'procedure', 'medication', 'consultation', 'other']);
            $table->enum('priority', ['routine', 'urgent', 'stat', 'asap'])->default('routine');
            $table->enum('status', ['pending', 'acknowledged', 'in_progress', 'completed', 'cancelled', 'discontinued'])->default('pending');

            // Timing
            $table->datetime('ordered_at');
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('cancelled_at')->nullable();

            // Clinical Information
            $table->text('clinical_indication')->nullable(); // Reason for order
            $table->text('special_instructions')->nullable();
            $table->text('notes')->nullable();

            // Billing Integration
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->boolean('billable')->default(true);
            $table->string('billing_code')->nullable();

            // Workflow
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users');
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users');
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['patient_id', 'ordered_at']);
            $table->index(['order_type', 'status']);
            $table->index(['target_department_id', 'status']);
            $table->index(['priority', 'ordered_at']);
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
