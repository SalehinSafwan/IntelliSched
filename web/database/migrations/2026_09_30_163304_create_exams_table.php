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
        Schema::create('exams', function (Blueprint $table) {
        $table->id();

        $table->foreignId('course_id')
            ->constrained('courses')
            ->cascadeOnDelete();

        $table->foreignId('section_id')
            ->constrained('sections')
            ->cascadeOnDelete();

        $table->foreignId('academic_term_id')
            ->constrained('academic_terms')
            ->cascadeOnDelete();

        $table->foreignId('room_id')
            ->nullable()
            ->constrained('rooms')
            ->nullOnDelete();

        $table->foreignId('time_slot_id')
            ->nullable()
            ->constrained('time_slots')
            ->nullOnDelete();

        $table->string('exam_type', 20);

        $table->date('exam_date');

        $table->string('status', 20)->default('SCHEDULED');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
