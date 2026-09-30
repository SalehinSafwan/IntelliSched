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
        Schema::create('schedule_changes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('schedule_run_id')
            ->constrained('schedule_runs')
            ->cascadeOnDelete();

        $table->foreignId('schedule_entry_id')
            ->constrained('schedule_entries')
            ->cascadeOnDelete();

        $table->foreignId('changed_by')
            ->constrained('users');

        $table->string('change_type', 30);

        $table->text('old_value')->nullable();
        $table->text('new_value')->nullable();

        $table->text('reason')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_changes');
    }
};
