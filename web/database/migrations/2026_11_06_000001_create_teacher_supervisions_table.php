<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sections a teacher supervises (advisor / project supervisor).
        Schema::create('teacher_supervisions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')
            ->constrained('teachers')
            ->cascadeOnDelete();

        $table->foreignId('section_id')
            ->constrained('sections')
            ->cascadeOnDelete();

        $table->string('note')->nullable();

        $table->timestamps();

        $table->unique(['teacher_id', 'section_id'], 'teacher_supervision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_supervisions');
    }
};
