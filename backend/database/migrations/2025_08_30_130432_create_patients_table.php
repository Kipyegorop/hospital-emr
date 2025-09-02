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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number')->unique(); // Hospital patient ID
            $table->string('uhid')->unique(); // Unique Hospital ID (UHID)
            $table->string('nhif_number')->nullable()->unique(); // NHIF membership number
            $table->string('id_number')->nullable()->unique(); // National ID or passport
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone')->nullable()->unique(); // Unique phone constraint
            $table->string('email')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Address Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Kenya');
            
            // Medical Information
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('current_medications')->nullable();
            $table->string('blood_type')->nullable();
            $table->decimal('height', 5, 2)->nullable(); // in cm
            $table->decimal('weight', 5, 2)->nullable(); // in kg
            
            // Insurance & Billing
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->enum('payment_method', ['cash', 'nhif', 'insurance', 'corporate'])->default('cash');
            
            // Status and Merge tracking
            $table->enum('status', ['active', 'inactive', 'deceased', 'merged'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('merged_into_patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->timestamp('merged_at')->nullable();
            $table->foreignId('merged_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['first_name', 'last_name']);
            $table->index('nhif_number');
            $table->index('phone');
            $table->index('uhid');
            $table->index('id_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
