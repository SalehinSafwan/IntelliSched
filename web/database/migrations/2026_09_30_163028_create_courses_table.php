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
        Schema::create('courses', function (Blueprint $table) {
        $table->id();

        $table->string('course_code')->unique();
        $table->string('course_name');

        $table->string('department');

        $table->string('course_type', 20);

        $table->unsignedInteger('credit_hours');

        $table->unsignedInteger('weekly_theory_classes')
            ->default(0);

        $table->unsignedInteger('weekly_lab_classes')
            ->default(0);

        $table->string('status', 20)->default('ACTIVE');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
