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
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users'); // Doctor who requested
            $table->foreignId('consultation_id')->nullable()->constrained('consultations');
            $table->string('test_request_number')->unique();
            
            // Test Details
            $table->string('test_name');
            $table->string('test_category'); // Hematology, Biochemistry, Microbiology, etc.
            $table->string('test_code')->nullable(); // Laboratory test code
            $table->text('test_description')->nullable();
            $table->text('clinical_notes')->nullable(); // Doctor's notes for lab
            $table->text('special_instructions')->nullable(); // Fasting, special preparation, etc.
            
            // Test Status
            $table->enum('status', ['requested', 'in_progress', 'completed', 'cancelled', 'rejected']);
            $table->timestamp('requested_at');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            
            // Sample Information
            $table->string('sample_type')->nullable(); // Blood, urine, stool, etc.
            $table->string('sample_id')->nullable(); // Laboratory sample identifier
            $table->text('sample_notes')->nullable();
            $table->enum('sample_quality', ['good', 'poor', 'insufficient', 'contaminated'])->nullable();
            
            // Results
            $table->json('test_results')->nullable(); // Actual test results
            $table->text('interpretation')->nullable(); // Lab technician's interpretation
            $table->text('recommendations')->nullable(); // Lab recommendations
            $table->enum('result_status', ['normal', 'abnormal', 'critical', 'pending_review']);
            
            // Personnel
            $table->foreignId('collected_by')->nullable()->constrained('users'); // Phlebotomist
            $table->foreignId('performed_by')->nullable()->constrained('users'); // Lab technician
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // Lab supervisor
            $table->foreignId('reported_by')->nullable()->constrained('users'); // Lab technician
            
            // Cost and Billing
            $table->decimal('test_cost', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'waived'])->default('pending');
            
            // Attachments
            $table->json('attachments')->nullable(); // Lab reports, images, etc.
            
            $table->timestamps();
            
            // Indexes
            $table->index(['patient_id', 'requested_at']);
            $table->index(['status', 'requested_at']);
            $table->index('test_category');
            $table->index('result_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
