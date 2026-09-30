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
        Schema::create('course_offerings', function (Blueprint $table) {
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

        $table->unsignedInteger('weekly_theory_classes')
            ->default(0);

        $table->unsignedInteger('weekly_lab_classes')
            ->default(0);

        $table->string('status', 20)->default('ACTIVE');

        $table->timestamps();

        $table->unique(
            ['course_id', 'section_id', 'academic_term_id'],
                'course_off_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
