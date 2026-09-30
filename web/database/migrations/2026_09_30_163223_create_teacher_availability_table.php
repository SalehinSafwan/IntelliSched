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
        Schema::create('teacher_availability', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')
            ->constrained('teachers')
            ->cascadeOnDelete();

        $table->foreignId('time_slot_id')
            ->constrained('time_slots')
            ->cascadeOnDelete();

        $table->unsignedInteger('is_available')->default(1);

        $table->string('reason')->nullable();

        $table->timestamps();

        $table->unique(['teacher_id', 'time_slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availability');
    }
};
