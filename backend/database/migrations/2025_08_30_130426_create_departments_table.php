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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cardiology, Pediatrics, Surgery, etc.
            $table->string('code')->unique(); // CARD, PED, SURG, etc.
            $table->text('description')->nullable();
            $table->foreignId('head_id')->nullable()->constrained('users'); // Head of department
            $table->string('location')->nullable(); // Building/Floor
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
