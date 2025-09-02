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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('medication_code')->unique(); // Internal medication code
            $table->string('name'); // Brand name
            $table->string('generic_name')->nullable();
            $table->string('dosage_form'); // tablet, syrup, injection, etc.
            $table->string('strength'); // 500mg, 10ml, etc.
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();
            
            // Classification
            $table->string('therapeutic_class')->nullable(); // Antibiotic, Analgesic, etc.
            $table->string('pharmacological_class')->nullable();
            $table->boolean('is_controlled')->default(false); // Controlled substance
            $table->boolean('requires_prescription')->default(true);
            
            // Stock Management
            $table->integer('current_stock');
            $table->integer('minimum_stock'); // Reorder level
            $table->integer('maximum_stock')->nullable();
            $table->string('unit'); // tablets, bottles, vials, etc.
            $table->string('storage_location')->nullable(); // Pharmacy shelf, refrigerator, etc.
            $table->text('storage_instructions')->nullable();
            
            // Pricing
            $table->decimal('unit_cost', 10, 2); // Cost to hospital
            $table->decimal('selling_price', 10, 2); // Price to patient
            $table->decimal('nhif_price', 10, 2)->nullable(); // NHIF reimbursement rate
            $table->string('currency')->default('KES');
            
            // Expiry and Batch
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'discontinued', 'out_of_stock']);
            $table->boolean('is_available')->default(true);
            
            // Additional Information
            $table->text('description')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('drug_interactions')->nullable();
            $table->text('dosage_instructions')->nullable();
            $table->json('active_ingredients')->nullable();
            
            // Regulatory
            $table->string('registration_number')->nullable(); // Pharmacy and Poisons Board
            $table->boolean('is_essential_drug')->default(false); // WHO essential medicines list
            
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index('generic_name');
            $table->index('therapeutic_class');
            $table->index('status');
            $table->index('current_stock');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
