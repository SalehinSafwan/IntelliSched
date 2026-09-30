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
        Schema::create('schedule_entries', function (Blueprint $table) {
        $table->id();

        $table->foreignId('schedule_run_id')
            ->constrained('schedule_runs')
            ->cascadeOnDelete();

        $table->foreignId('course_offering_id')
            ->constrained('course_offerings')
            ->cascadeOnDelete();

        $table->foreignId('teacher_id')
            ->constrained('teachers');

        $table->foreignId('room_id')
            ->constrained('rooms');

        $table->foreignId('time_slot_id')
            ->constrained('time_slots');

        $table->string('session_type', 20);

        // Groups the 3 records belonging to one lab block.
        $table->string('session_group_id', 50)->nullable();

        $table->timestamps();

        $table->unique(
            ['schedule_run_id', 'room_id', 'time_slot_id'],
                'schedule_entry_unique'
            );

        $table->unique(
                 ['schedule_run_id', 'teacher_id', 'time_slot_id'],
                    'schedule_teacher_unique'
                );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};
