<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->integer('stock_before');
            $table->integer('adjustment');
            $table->integer('stock_after');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_stock_adjustments');
    }
};
