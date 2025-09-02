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
        Schema::create('procedure_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_schedule_id')->constrained('theatre_schedules')->onDelete('cascade');
            $table->foreignId('procedure_id')->constrained('procedures');
            $table->string('item_code')->nullable(); // Hospital inventory code
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->enum('item_type', ['consumable', 'implant', 'medication', 'equipment', 'other']);

            // Quantity & Usage
            $table->integer('planned_quantity'); // Planned usage
            $table->integer('actual_quantity_used')->default(0); // Actually used
            $table->string('unit_of_measure'); // pieces, ml, mg, etc.

            // Batch & Traceability (Critical for implants)
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable(); // For implants
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();

            // Pricing & Billing
            $table->decimal('unit_cost', 10, 2); // Cost per unit
            $table->decimal('unit_price', 10, 2); // Selling price per unit
            $table->decimal('total_cost', 10, 2); // Total cost (quantity × unit_cost)
            $table->decimal('total_charge', 10, 2); // Total charge (quantity × unit_price)
            $table->boolean('billable')->default(true);
            $table->string('billing_code')->nullable();

            // Inventory Management
            $table->integer('stock_before_use')->nullable(); // Stock level before procedure
            $table->integer('stock_after_use')->nullable(); // Stock level after procedure
            $table->boolean('stock_updated')->default(false);
            $table->datetime('stock_updated_at')->nullable();
            $table->foreignId('stock_updated_by')->nullable()->constrained('users');

            // Usage Tracking
            $table->enum('usage_status', ['planned', 'issued', 'used', 'returned', 'wasted'])->default('planned');
            $table->datetime('issued_at')->nullable();
            $table->datetime('used_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->foreignId('used_by')->nullable()->constrained('users'); // Surgeon/nurse who used it

            // Quality & Compliance
            $table->boolean('requires_tracking')->default(false); // For implants/high-value items
            $table->text('tracking_notes')->nullable();
            $table->boolean('adverse_event')->default(false);
            $table->text('adverse_event_notes')->nullable();

            // Return/Waste Management
            $table->integer('quantity_returned')->default(0);
            $table->integer('quantity_wasted')->default(0);
            $table->text('return_reason')->nullable();
            $table->text('waste_reason')->nullable();
            $table->decimal('waste_cost', 10, 2)->default(0);

            // Regulatory (for implants)
            $table->string('regulatory_approval')->nullable(); // FDA, CE, KEBS approval
            $table->boolean('requires_patient_consent')->default(false);
            $table->boolean('patient_consent_obtained')->default(false);
            $table->text('patient_information')->nullable(); // Info given to patient about implant

            $table->timestamps();

            // Indexes
            $table->index(['theatre_schedule_id', 'item_type']);
            $table->index(['procedure_id', 'item_type']);
            $table->index('item_code');
            $table->index('batch_number');
            $table->index('serial_number');
            $table->index(['usage_status', 'created_at']);
            $table->index(['requires_tracking', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_consumables');
    }
};
