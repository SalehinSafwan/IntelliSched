<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track the last reply each reader has seen by ID. Timestamps are
     * stored to the second, so a reply made in the same second as a
     * read could not be told apart.
     */
    public function up(): void
    {
        Schema::table('class_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_seen_reply_id')->nullable()->after('teacher_seen_at');
        });

        Schema::table('class_message_reads', function (Blueprint $table) {
            $table->unsignedBigInteger('last_reply_id')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('class_messages', function (Blueprint $table) {
            $table->dropColumn('teacher_seen_reply_id');
        });

        Schema::table('class_message_reads', function (Blueprint $table) {
            $table->dropColumn('last_reply_id');
        });
    }
};
