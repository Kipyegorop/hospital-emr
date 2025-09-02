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
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('procedure_code')->unique(); // Hospital procedure code
            $table->string('procedure_name');
            $table->text('description')->nullable();
            $table->string('category'); // Surgery, Diagnostic, Therapeutic, etc.
            $table->string('specialty')->nullable(); // Cardiology, Orthopedics, etc.

            // Clinical Information
            $table->integer('estimated_duration_minutes'); // Expected procedure time
            $table->enum('complexity_level', ['simple', 'moderate', 'complex', 'high_risk'])->default('moderate');
            $table->enum('anesthesia_type', ['local', 'regional', 'general', 'sedation', 'none'])->nullable();
            $table->text('pre_procedure_requirements')->nullable(); // Fasting, medications, etc.
            $table->text('post_procedure_care')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('complications')->nullable();

            // Billing Information
            $table->decimal('base_price', 10, 2); // Base procedure cost
            $table->decimal('surgeon_fee', 10, 2)->nullable();
            $table->decimal('anesthetist_fee', 10, 2)->nullable();
            $table->decimal('theatre_fee', 10, 2)->nullable();
            $table->decimal('consumables_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2); // Calculated total
            $table->decimal('nhif_rate', 10, 2)->nullable(); // NHIF reimbursement rate
            $table->string('billing_code')->nullable(); // ICD-10-PCS or local billing code

            // Resource Requirements
            $table->json('required_equipment')->nullable(); // List of required equipment
            $table->json('required_staff')->nullable(); // Required staff roles and numbers
            $table->json('consumables_list')->nullable(); // Standard consumables needed
            $table->boolean('requires_implants')->default(false);
            $table->boolean('requires_blood_products')->default(false);

            // Regulatory & Quality
            $table->boolean('requires_consent')->default(true);
            $table->boolean('is_emergency_procedure')->default(false);
            $table->boolean('is_day_case')->default(false); // Can be done as day surgery
            $table->integer('typical_los_days')->nullable(); // Length of stay in days
            $table->text('quality_indicators')->nullable(); // KPIs to track

            // Status
            $table->enum('status', ['active', 'inactive', 'deprecated'])->default('active');
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            // `procedure_code` is unique already (unique() creates an index), so skip explicit index
            $table->index('procedure_name');
            $table->index('category');
            $table->index('specialty');
            $table->index('status');
            // Note: SQLite doesn't support fulltext indexes, so we use regular indexes instead
            $table->index('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
