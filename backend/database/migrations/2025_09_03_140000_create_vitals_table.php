<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('cascade');
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('systolic_bp')->nullable();
            $table->integer('diastolic_bp')->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
