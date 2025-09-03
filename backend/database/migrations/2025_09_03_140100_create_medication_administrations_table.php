<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->onDelete('set null');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('cascade');
            $table->foreignId('administered_by')->nullable()->constrained('users');
            $table->string('medication_name');
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->string('frequency')->nullable();
            $table->timestamp('administration_time')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('given')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};
