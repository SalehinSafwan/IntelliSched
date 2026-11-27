<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the CR/ACR posted the approved notice to the class group,
     * so the notice board can split "ready to post" from "posted".
     */
    public function up(): void
    {
        Schema::table('class_messages', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('class_messages', function (Blueprint $table) {
            $table->dropColumn(['posted_at', 'posted_by']);
        });
    }
};
