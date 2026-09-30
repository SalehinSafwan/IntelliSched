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
        Schema::create('time_slots', function (Blueprint $table) {
        $table->id();

        $table->string('day', 15);

        $table->unsignedInteger('slot_number');

        $table->time('start_time');
        $table->time('end_time');

        $table->unsignedInteger('is_break')->default(0);

        $table->timestamps();

        $table->unique(['day', 'slot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
