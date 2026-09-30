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
        Schema::create('teaching_history', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')
            ->constrained('teachers')
            ->cascadeOnDelete();

        $table->foreignId('course_id')
            ->constrained('courses')
            ->cascadeOnDelete();

        $table->foreignId('academic_term_id')
            ->constrained('academic_terms')
            ->cascadeOnDelete();

        $table->foreignId('batch_id')
            ->constrained('batches')
            ->cascadeOnDelete();

        $table->unsignedInteger('times_taught')->default(1);

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_history');
    }
};
