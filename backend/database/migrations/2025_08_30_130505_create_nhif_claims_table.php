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
        Schema::create('nhif_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique(); // NHIF claim number
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('bill_id')->constrained('bills')->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users'); // Staff who submitted
            
            // Patient NHIF Information
            $table->string('nhif_number');
            $table->string('nhif_card_number')->nullable();
            $table->string('principal_member_name')->nullable();
            $table->string('principal_member_nhif_number')->nullable();
            $table->enum('relationship_type', ['principal', 'spouse', 'child', 'parent'])->default('principal');
            
            // Claim Details
            $table->enum('claim_type', ['outpatient', 'inpatient', 'dental', 'optical', 'maternity', 'emergency']);
            $table->date('admission_date')->nullable(); // For inpatient
            $table->date('discharge_date')->nullable(); // For inpatient
            $table->integer('length_of_stay')->nullable(); // Days for inpatient
            $table->text('diagnosis')->nullable();
            $table->text('procedures_performed')->nullable();
            
            // Financial Information
            $table->decimal('total_bill_amount', 12, 2);
            $table->decimal('nhif_coverable_amount', 12, 2);
            $table->decimal('patient_contribution', 12, 2)->default(0.00);
            $table->decimal('nhif_payable_amount', 12, 2);
            $table->decimal('amount_paid_by_nhif', 12, 2)->default(0.00);
            $table->decimal('outstanding_amount', 12, 2);
            
            // Claim Status
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'paid', 'cancelled']);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // NHIF Processing
            $table->string('nhif_reference_number')->nullable();
            $table->text('nhif_remarks')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expected_payment_date')->nullable();
            
            // Supporting Documents
            $table->json('required_documents')->nullable(); // List of required documents
            $table->json('submitted_documents')->nullable(); // Documents submitted
            $table->boolean('all_documents_submitted')->default(false);
            
            // Hospital Information
            $table->string('hospital_code')->nullable(); // NHIF hospital code
            $table->string('provider_number')->nullable(); // NHIF provider number
            $table->string('branch_code')->nullable(); // Hospital branch code
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('attachments')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'created_at']);
            $table->index('claim_number');
            $table->index('status');
            $table->index('nhif_number');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhif_claims');
    }
};
