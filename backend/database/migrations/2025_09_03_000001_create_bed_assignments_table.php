<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bed_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bed_id')->constrained('beds')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Add index to speed queries. We rely on row-level locks for safety.
            $table->index(['bed_id', 'patient_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bed_assignments');
    }
};
