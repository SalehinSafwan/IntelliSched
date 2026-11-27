<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teacher message → CR/ACR formal notice → teacher review.
     *
     * A message is a session. The CR/ACR answers it with a formal
     * notice for the class; the teacher approves it (the session is
     * completed and closed) or rejects it, optionally with a
     * correction, and the CR/ACR submits a corrected notice.
     */
    public function up(): void
    {
        Schema::table('class_messages', function (Blueprint $table) {
            // AWAITING_NOTICE, PENDING_REVIEW, CHANGES_REQUESTED, COMPLETED
            $table->string('status', 20)->default('AWAITING_NOTICE');
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('class_message_replies', function (Blueprint $table) {
            // COMMENT, NOTICE, APPROVED, REJECTED
            $table->string('kind', 20)->default('COMMENT');

            // On an APPROVED or REJECTED review: the notice it judged.
            $table->unsignedBigInteger('notice_reply_id')->nullable();

            // What the teacher wants changed, on a rejection.
            $table->string('correction', 2000)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('class_message_replies', function (Blueprint $table) {
            $table->dropColumn(['kind', 'notice_reply_id', 'correction']);
        });

        Schema::table('class_messages', function (Blueprint $table) {
            $table->dropColumn(['status', 'completed_at']);
        });
    }
};
