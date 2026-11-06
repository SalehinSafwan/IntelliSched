<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
        // Student accounts belong to one section. A student may also be
        // the section's class representative: CR or ACR.
        $table->foreignId('section_id')
            ->nullable()
            ->after('role')
            ->constrained('sections')
            ->nullOnDelete();

        $table->string('class_rep', 5)->nullable()->after('section_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropColumn('class_rep');
        });
    }
};
