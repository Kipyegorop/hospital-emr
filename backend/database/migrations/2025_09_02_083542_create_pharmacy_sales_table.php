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
        Schema::create('pharmacy_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('patient_id')->nullable()->constrained('patients'); // Null for walk-in OTC sales
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions'); // Null for OTC sales
            $table->foreignId('medication_id')->constrained('medications');
            $table->foreignId('pharmacist_id')->constrained('users'); // Pharmacist who made the sale
            $table->foreignId('department_id')->nullable()->constrained('departments'); // For IPD ward issues

            // Sale Classification
            $table->enum('sale_type', ['otc', 'opd_prescription', 'ipd_prescription', 'walk_in', 'ward_issue']);
            $table->enum('patient_category', ['cash', 'nhif', 'insurance', 'staff', 'waiver'])->default('cash');

            // Medication Details
            $table->string('medication_name');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity_sold');
            $table->string('unit'); // tablets, bottles, etc.

            // Pricing (Different price lists)
            $table->decimal('unit_cost', 10, 2); // Cost to hospital
            $table->decimal('unit_price', 10, 2); // Selling price used
            $table->decimal('opd_price', 10, 2)->nullable(); // OPD price list
            $table->decimal('ipd_price', 10, 2)->nullable(); // IPD price list
            $table->decimal('otc_price', 10, 2)->nullable(); // OTC price list
            $table->decimal('nhif_price', 10, 2)->nullable(); // NHIF reimbursement rate
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('profit_margin', 10, 2)->nullable(); // Calculated profit

            // Payment Information
            $table->enum('payment_method', ['cash', 'mpesa', 'card', 'bank_transfer', 'nhif', 'insurance', 'credit']);
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable(); // M-Pesa code, card reference, etc.
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);

            // Stock Management
            $table->integer('stock_before_sale');
            $table->integer('stock_after_sale');
            $table->boolean('stock_updated')->default(false);

            // Customer Information (for walk-ins)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();

            // Clinical Information
            $table->text('indication')->nullable(); // What it's for
            $table->text('dosage_instructions')->nullable();
            $table->text('pharmacist_counseling_notes')->nullable();
            $table->boolean('counseling_provided')->default(false);

            // Regulatory & Compliance
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled_substance')->default(false);
            $table->string('prescriber_license')->nullable(); // For controlled substances
            $table->text('regulatory_notes')->nullable();

            // Workflow
            $table->datetime('sale_date');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'returned'])->default('pending');
            $table->datetime('completed_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();

            // Return/Refund Information
            $table->boolean('is_returnable')->default(true);
            $table->datetime('return_deadline')->nullable();
            $table->decimal('returned_amount', 10, 2)->default(0);
            $table->text('return_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['sale_type', 'sale_date']);
            $table->index(['patient_id', 'sale_date']);
            $table->index(['medication_id', 'sale_date']);
            $table->index(['pharmacist_id', 'sale_date']);
            $table->index(['payment_status', 'sale_date']);
            $table->index('sale_number');
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_sales');
    }
};
