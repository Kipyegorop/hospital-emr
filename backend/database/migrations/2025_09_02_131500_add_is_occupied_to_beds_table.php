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
        Schema::table('beds', function (Blueprint $table) {
            if (!Schema::hasColumn('beds', 'is_occupied')) {
                $table->boolean('is_occupied')->default(false)->after('bed_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beds', function (Blueprint $table) {
            if (Schema::hasColumn('beds', 'is_occupied')) {
                $table->dropColumn('is_occupied');
            }
        });
    }
};
