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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users');
            $table->string('prescription_number')->unique();
            
            // Medication Details
            $table->foreignId('medication_id')->nullable()->constrained('medications');
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('dosage_form'); // tablet, syrup, injection, etc.
            $table->string('strength'); // 500mg, 10ml, etc.
            $table->string('dosage_instructions'); // Take 1 tablet twice daily
            $table->integer('quantity_prescribed');
            $table->boolean('quantity_locked')->default(true); // Kenya requirement: locked by default
            $table->string('unit'); // tablets, bottles, etc.
            $table->integer('duration_days')->nullable(); // How long to take
            $table->string('frequency'); // daily, twice daily, etc.
            
            // Prescription Details
            $table->enum('status', ['active', 'completed', 'cancelled', 'expired']);
            $table->date('prescribed_date');
            $table->date('expiry_date')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('side_effects_warning')->nullable();
            $table->boolean('requires_refrigeration')->default(false);
            
            // Dispensing Information
            $table->enum('dispensing_status', ['pending', 'dispensed', 'partially_dispensed', 'cancelled']);
            $table->integer('quantity_dispensed')->default(0);
            $table->integer('quantity_remaining')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->foreignId('dispensed_by')->nullable()->constrained('users'); // Pharmacist
            $table->text('pharmacy_notes')->nullable();

            // Pharmacy Submodule Classification
            $table->enum('prescription_type', ['opd', 'ipd', 'emergency'])->default('opd');
            $table->enum('patient_category', ['cash', 'nhif', 'insurance', 'staff', 'waiver'])->default('cash');
            
            // Cost Information (Different price lists for OPD/IPD)
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('opd_price', 10, 2)->nullable(); // OPD price list
            $table->decimal('ipd_price', 10, 2)->nullable(); // IPD price list
            $table->decimal('nhif_price', 10, 2)->nullable(); // NHIF reimbursement rate
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'waived'])->default('pending');

            // Exception Handling
            $table->boolean('has_exception_request')->default(false);
            $table->enum('exception_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'prescribed_date']);
            $table->index(['doctor_id', 'prescribed_date']);
            $table->index('status');
            $table->index('dispensing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
