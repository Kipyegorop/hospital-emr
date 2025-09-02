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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            // $table->foreignId('loinc_code_id')->nullable()->constrained('loinc_codes'); // Removed LOINC dependency

            // Item Details
            $table->string('item_code')->nullable(); // Internal hospital code
            $table->string('item_name'); // Test/procedure/medication name
            $table->text('item_description')->nullable();
            $table->string('item_category')->nullable(); // e.g., 'chemistry', 'hematology', 'imaging'

            // LOINC Mapping
            $table->string('loinc_code')->nullable(); // LOINC identifier
            $table->string('loinc_display_name')->nullable();
            $table->string('loinc_system')->nullable();

            // Specifications
            $table->integer('quantity')->default(1);
            $table->string('unit_of_measure')->nullable();
            $table->text('specimen_type')->nullable(); // For lab tests
            $table->text('collection_instructions')->nullable();
            $table->text('preparation_instructions')->nullable();

            // Status Tracking
            $table->enum('status', ['pending', 'collected', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->datetime('collected_at')->nullable();
            $table->datetime('resulted_at')->nullable();
            $table->text('result_value')->nullable();
            $table->text('result_unit')->nullable();
            $table->text('reference_range')->nullable();
            $table->enum('result_status', ['normal', 'abnormal', 'critical', 'pending'])->nullable();
            $table->text('result_notes')->nullable();

            // Billing
            $table->decimal('unit_price', 8, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('billing_code')->nullable();

            // Workflow
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->datetime('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'status']);
            $table->index('loinc_code');
            $table->index('item_code');
            $table->index(['item_category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
