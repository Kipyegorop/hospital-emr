<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('cascade');
            $table->foreignId('nurse_id')->nullable()->constrained('users');
            $table->text('assessment')->nullable();
            $table->text('care_plan')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_assessments');
    }
};
