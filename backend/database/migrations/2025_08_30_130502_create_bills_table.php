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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique(); // Hospital bill number
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users'); // Staff who created the bill
            
            // Bill Details
            $table->enum('bill_type', ['consultation', 'medication', 'laboratory', 'radiology', 'ward', 'procedure', 'comprehensive']);
            $table->text('description')->nullable();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            
            // Amount Details
            $table->decimal('subtotal', 12, 2); // Amount before tax
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2); // Final amount
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('balance_due', 12, 2); // Remaining amount
            
            // Payment Information
            $table->enum('payment_method', ['cash', 'mpesa', 'card', 'bank_transfer', 'nhif', 'insurance', 'corporate']);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue', 'cancelled', 'waived']);
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_reference')->nullable(); // Payment reference number
            
            // Insurance and NHIF
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->decimal('insurance_coverage', 10, 2)->nullable();
            $table->string('nhif_number')->nullable();
            $table->enum('nhif_status', ['pending', 'approved', 'rejected', 'paid'])->nullable();
            
            // Related Records
            $table->json('billable_items')->nullable(); // Items included in the bill
            $table->foreignId('appointment_id')->nullable()->constrained('appointments');
            $table->foreignId('consultation_id')->nullable()->constrained('consultations');
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions');
            $table->foreignId('lab_test_id')->nullable()->constrained('lab_tests');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->text('payment_notes')->nullable();
            $table->json('attachments')->nullable(); // Receipts, invoices, etc.
            
            // Status and Tracking
            $table->enum('status', ['active', 'cancelled', 'refunded', 'archived']);
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'bill_date']);
            $table->index('bill_number');
            $table->index('payment_status');
            $table->index('bill_type');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
