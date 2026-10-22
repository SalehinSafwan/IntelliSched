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
        Schema::table('teacher_availability', function (Blueprint $table) {
        // Soft time-of-day preference for an available slot:
        // 1 = preferred, 0 = neutral, -1 = avoid if possible.
        $table->integer('preference')->default(0)->after('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_availability', function (Blueprint $table) {
            $table->dropColumn('preference');
        });
    }
};
