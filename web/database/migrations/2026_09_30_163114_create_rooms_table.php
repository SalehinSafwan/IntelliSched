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
        Schema::create('rooms', function (Blueprint $table) {
        $table->id();

        $table->string('room_code')->unique();
        $table->string('room_name');

        $table->string('room_type', 20);

        $table->unsignedInteger('capacity');
        $table->unsignedInteger('priority')->default(999);

        $table->string('building')->nullable();
        $table->string('floor')->nullable();

        $table->string('status', 20)->default('ACTIVE');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
