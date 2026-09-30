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
        Schema::create('schedule_runs', function (Blueprint $table) {
        $table->id();

        $table->foreignId('batch_id')
            ->constrained('batches')
            ->cascadeOnDelete();

        $table->foreignId('academic_term_id')
            ->constrained('academic_terms')
            ->cascadeOnDelete();

        $table->foreignId('generated_by')
            ->constrained('users');

        $table->unsignedInteger('version');

        $table->string('status', 20)->default('DRAFT');

        $table->decimal('objective_score', 10, 2)->nullable();

        $table->timestamp('generated_at')->nullable();

        $table->timestamps();

        $table->unique(
            ['batch_id', 'academic_term_id', 'version'],
                'schedule_run_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_runs');
    }
};
