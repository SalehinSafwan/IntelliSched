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
        Schema::table('schedule_entries', function (Blueprint $table) {
        // Second teacher of the course pair for joint sessions
        // (labs and the combined theory class).
        $table->foreignId('co_teacher_id')
            ->nullable()
            ->after('teacher_id')
            ->constrained('teachers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('co_teacher_id');
        });
    }
};
