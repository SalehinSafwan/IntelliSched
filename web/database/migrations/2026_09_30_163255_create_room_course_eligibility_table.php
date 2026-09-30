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
        Schema::create('room_course_eligibility', function (Blueprint $table) {
        $table->id();

        $table->foreignId('room_id')
            ->constrained('rooms')
            ->cascadeOnDelete();

        $table->foreignId('course_id')
            ->constrained('courses')
            ->cascadeOnDelete();

        $table->timestamps();

        $table->unique(['room_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_course_eligibility');
    }
};
