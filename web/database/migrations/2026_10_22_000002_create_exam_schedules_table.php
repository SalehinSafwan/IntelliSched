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
        // One exam schedule per batch, term and exam type. Every exam
        // in it shares the same time window and the same rooms.
        Schema::create('exam_schedules', function (Blueprint $table) {
        $table->id();

        $table->foreignId('academic_term_id')
            ->constrained('academic_terms')
            ->cascadeOnDelete();

        $table->foreignId('batch_id')
            ->constrained('batches')
            ->cascadeOnDelete();

        $table->string('exam_type', 20);

        // MORNING (10:00-13:00) or AFTERNOON (14:00-17:00)
        $table->string('session', 20);
        $table->string('start_time', 5);
        $table->string('end_time', 5);

        $table->string('status', 20)->default('DRAFT');

        $table->foreignId('created_by')
            ->constrained('users');

        $table->timestamp('published_at')->nullable();

        $table->timestamps();

        $table->unique(
            ['academic_term_id', 'batch_id', 'exam_type'],
                'exam_schedule_unique'
            );
        });

        Schema::create('exam_schedule_rooms', function (Blueprint $table) {
        $table->id();

        $table->foreignId('exam_schedule_id')
            ->constrained('exam_schedules')
            ->cascadeOnDelete();

        $table->foreignId('room_id')
            ->constrained('rooms')
            ->cascadeOnDelete();

        $table->timestamps();

        $table->unique(['exam_schedule_id', 'room_id'], 'exam_room_unique');
        });

        Schema::create('exam_schedule_courses', function (Blueprint $table) {
        $table->id();

        $table->foreignId('exam_schedule_id')
            ->constrained('exam_schedules')
            ->cascadeOnDelete();

        $table->foreignId('course_id')
            ->constrained('courses')
            ->cascadeOnDelete();

        $table->date('exam_date')->nullable();

        $table->timestamps();

        $table->unique(['exam_schedule_id', 'course_id'], 'exam_course_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedule_courses');
        Schema::dropIfExists('exam_schedule_rooms');
        Schema::dropIfExists('exam_schedules');
    }
};
