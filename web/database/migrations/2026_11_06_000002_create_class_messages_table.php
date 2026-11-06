<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A teacher's notice to one section's CR and ACR.
        Schema::create('class_messages', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')
            ->constrained('teachers')
            ->cascadeOnDelete();

        $table->foreignId('section_id')
            ->constrained('sections')
            ->cascadeOnDelete();

        $table->foreignId('course_id')
            ->nullable()
            ->constrained('courses')
            ->nullOnDelete();

        // RESCHEDULE, CANCEL, EXTRA_CLASS, ROOM_CHANGE, ASSESSMENT, GENERAL
        $table->string('category', 20);

        $table->string('subject', 150);
        $table->string('body', 2000);

        // The class concerned and, for a reschedule or extra class,
        // when it now takes place.
        $table->date('class_date')->nullable();
        $table->foreignId('time_slot_id')
            ->nullable()
            ->constrained('time_slots')
            ->nullOnDelete();

        $table->date('new_date')->nullable();
        $table->foreignId('new_time_slot_id')
            ->nullable()
            ->constrained('time_slots')
            ->nullOnDelete();

        // Bumped on every reply so both sides can spot new activity.
        $table->timestamp('last_activity_at')->nullable();
        $table->timestamp('teacher_seen_at')->nullable();

        $table->timestamps();
        });

        Schema::create('class_message_replies', function (Blueprint $table) {
        $table->id();

        $table->foreignId('class_message_id')
            ->constrained('class_messages')
            ->cascadeOnDelete();

        $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->string('body', 2000);

        $table->timestamps();
        });

        // When each CR/ACR last opened a message.
        Schema::create('class_message_reads', function (Blueprint $table) {
        $table->id();

        $table->foreignId('class_message_id')
            ->constrained('class_messages')
            ->cascadeOnDelete();

        $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->timestamp('read_at');

        $table->timestamps();

        $table->unique(['class_message_id', 'user_id'], 'class_message_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_message_reads');
        Schema::dropIfExists('class_message_replies');
        Schema::dropIfExists('class_messages');
    }
};
