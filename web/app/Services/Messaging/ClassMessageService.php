<?php

namespace App\Services\Messaging;

use App\Models\ClassMessage;
use App\Models\ClassMessageRead;
use App\Models\ClassMessageReply;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * Who may read which teacher → CR/ACR message.
 *
 * A message belongs to one teacher and one section. Only the teacher
 * who sent it and the active CR and ACR of that section can open it;
 * nobody else, not even other students of the section.
 *
 * Each message is a session: the CR/ACR answers with a formal notice
 * for the class, the teacher approves it (session completed, closed
 * to further messages) or rejects it, optionally with a correction,
 * and the CR/ACR submits a corrected notice for another review.
 */
class ClassMessageService
{
    /**
     * Active CR and ACR accounts of a section.
     */
    public function recipients(int $sectionId): Collection
    {
        return User::where('section_id', $sectionId)
            ->whereRaw('LOWER(role) = ?', ['student'])
            ->whereIn('class_rep', array_keys(config('intellisched.class_reps')))
            ->where('is_active', 1)
            ->orderBy('class_rep', 'desc')
            ->get();
    }

    /**
     * Messages the user may see: their own as a teacher, their
     * section's as a CR/ACR, otherwise none.
     */
    public function visibleTo(User $user): Builder
    {
        $query = ClassMessage::query();

        if ($teacher = $this->teacherOf($user)) {
            return $query->where('teacher_id', $teacher->id);
        }

        if ($user->isClassRep()) {
            return $query->where('section_id', $user->section_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canView(User $user, ClassMessage $message): bool
    {
        if ($teacher = $this->teacherOf($user)) {
            return (int) $message->teacher_id === (int) $teacher->id;
        }

        return $user->isClassRep() && (int) $message->section_id === (int) $user->section_id;
    }

    public function isSender(User $user, ClassMessage $message): bool
    {
        $teacher = $this->teacherOf($user);

        return $teacher && (int) $message->teacher_id === (int) $teacher->id;
    }

    /**
     * Either side may add a comment while the session is open.
     */
    public function canComment(User $user, ClassMessage $message): bool
    {
        return !$message->isCompleted() && $this->canView($user, $message);
    }

    /**
     * Only the section's CR/ACR writes the notice, and only while the
     * teacher is not already reviewing one.
     */
    public function canSubmitNotice(User $user, ClassMessage $message): bool
    {
        return $message->awaitsNotice()
            && !$this->isSender($user, $message)
            && $this->canView($user, $message);
    }

    public function canReview(User $user, ClassMessage $message): bool
    {
        return $message->awaitsReview() && $this->isSender($user, $message);
    }

    /**
     * Whether the session is waiting on this user.
     */
    public function needsActionFrom(User $user, ClassMessage $message): bool
    {
        return $this->isSender($user, $message)
            ? $message->awaitsReview()
            : $message->awaitsNotice() && $user->isClassRep();
    }

    public function submitNotice(User $user, ClassMessage $message, string $body): ClassMessageReply
    {
        return DB::transaction(function () use ($user, $message, $body) {
            // Re-read under a lock so two quick submits cannot both land.
            $fresh = ClassMessage::whereKey($message->id)->lockForUpdate()->firstOrFail();

            if (!$this->canSubmitNotice($user, $fresh)) {
                throw ValidationException::withMessages([
                    'notice' => $fresh->isCompleted()
                        ? 'This session is already completed.'
                        : 'The teacher is reviewing your notice. Wait for their decision.',
                ]);
            }

            $notice = $fresh->replies()->create([
                'user_id' => $user->id,
                'kind' => ClassMessageReply::NOTICE,
                'body' => $body,
            ]);

            $fresh->forceFill([
                'status' => ClassMessage::PENDING_REVIEW,
                'last_activity_at' => now(),
            ])->save();

            $message->setRawAttributes($fresh->getAttributes(), true);

            return $notice;
        });
    }

    /**
     * Approve or reject the notice under review. $noticeId is the
     * notice the teacher looked at, so a stale page cannot approve a
     * version the teacher never saw.
     */
    public function review(User $user, ClassMessage $message, int $noticeId, bool $approve, ?string $correction = null): ClassMessageReply
    {
        return DB::transaction(function () use ($user, $message, $noticeId, $approve, $correction) {
            $fresh = ClassMessage::whereKey($message->id)->lockForUpdate()->firstOrFail();

            if (!$this->canReview($user, $fresh)) {
                throw ValidationException::withMessages([
                    'review' => $fresh->isCompleted()
                        ? 'This session is already completed.'
                        : 'There is no notice waiting for your review.',
                ]);
            }

            $latestNotice = (int) ClassMessageReply::where('class_message_id', $fresh->id)
                ->where('kind', ClassMessageReply::NOTICE)
                ->max('id');

            if ($latestNotice !== $noticeId) {
                throw ValidationException::withMessages([
                    'review' => 'A newer version of the notice was submitted. Please review that one.',
                ]);
            }

            $correction = $approve ? null : (trim((string) $correction) ?: null);

            $review = $fresh->replies()->create([
                'user_id' => $user->id,
                'kind' => $approve ? ClassMessageReply::APPROVED : ClassMessageReply::REJECTED,
                'notice_reply_id' => $noticeId,
                'body' => $approve ? 'Approved the notice.' : 'Rejected the notice.',
                'correction' => $correction,
            ]);

            $fresh->forceFill([
                'status' => $approve ? ClassMessage::COMPLETED : ClassMessage::CHANGES_REQUESTED,
                'completed_at' => $approve ? now() : null,
                'last_activity_at' => now(),
            ])->save();

            $message->setRawAttributes($fresh->getAttributes(), true);

            return $review;
        });
    }

    /**
     * A ready-to-edit notice in the style CRs post on WhatsApp:
     * *bold* headings, the class details, then the teacher's words.
     */
    public function noticeTemplate(ClassMessage $message, User $rep): string
    {
        $message->loadMissing(['section.batch', 'course', 'teacher', 'timeSlot', 'newTimeSlot']);

        $categories = config('intellisched.message_categories');
        $category = $categories[$message->category] ?? $categories['GENERAL'];

        $slot = fn ($slot) => $slot
            ? 'P' . $slot->slot_number . ' (' . $slot->start_time . '–' . $slot->end_time . ')'
            : null;
        $when = fn ($date, $time) => implode(', ', array_filter([$date?->format('l, d M Y'), $slot($time)]));

        $lines = ['*NOTICE — ' . mb_strtoupper($category['label']) . '*', ''];

        if ($message->course) {
            $lines[] = '*Course:* ' . $message->course->course_code . ' — ' . $message->course->course_name;
        }
        $lines[] = '*Teacher:* ' . $message->teacher?->display_name;
        $lines[] = '*Section:* ' . $message->section?->label;

        $original = $when($message->class_date, $message->timeSlot);
        $new = $when($message->new_date, $message->newTimeSlot);

        if ($original !== '') {
            $lines[] = match ($message->category) {
                'CANCEL' => '*Cancelled class:* ' . $original,
                'RESCHEDULE' => '*Previous time:* ' . $original,
                default => '*Class:* ' . $original,
            };
        }
        if ($new !== '') {
            $lines[] = ($message->category === 'RESCHEDULE' ? '*New time:* ' : '*Time:* ') . $new;
        }

        $lines[] = '';
        $lines[] = trim((string) $message->body);
        $lines[] = '';
        $lines[] = 'Everyone please take note.';
        // Some account names already carry "(CR)"; don't repeat it.
        $role = $rep->class_rep && !str_contains($rep->name, $rep->class_rep) ? ', ' . $rep->class_rep : '';
        $lines[] = '— ' . $rep->name . $role;

        return implode("\n", $lines);
    }

    /**
     * Render WhatsApp markup (*bold*, _italic_) safely as HTML.
     */
    public static function formatNotice(?string $text): HtmlString
    {
        $html = e((string) $text);
        $html = preg_replace('/(?<![\w*])\*(?=\S)([^*\n]+?)(?<=\S)\*(?![\w*])/u', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<![\w_])_(?=\S)([^_\n]+?)(?<=\S)_(?![\w_])/u', '<em>$1</em>', $html);

        return new HtmlString($html);
    }

    /**
     * IDs of visible messages with activity the user has not seen yet.
     */
    public function unreadIds(User $user): array
    {
        if ($teacher = $this->teacherOf($user)) {
            // Replies from the CR/ACR newer than the last one seen.
            return ClassMessage::where('teacher_id', $teacher->id)
                ->whereHas('replies', fn ($query) => $query
                    ->where('user_id', '!=', $user->id)
                    ->whereRaw('class_message_replies.id > COALESCE(class_messages.teacher_seen_reply_id, 0)'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (!$user->isClassRep()) {
            return [];
        }

        $messageIds = ClassMessage::where('section_id', $user->section_id)->pluck('id')->map(fn ($id) => (int) $id);

        $reads = ClassMessageRead::where('user_id', $user->id)
            ->whereIn('class_message_id', $messageIds->all() ?: [0])
            ->get(['class_message_id', 'last_reply_id'])
            ->keyBy(fn ($read) => (int) $read->class_message_id);

        // Newest reply by someone else in each message.
        $latestOther = ClassMessageReply::whereIn('class_message_id', $messageIds->all() ?: [0])
            ->where('user_id', '!=', $user->id)
            ->groupBy('class_message_id')
            ->selectRaw('class_message_id, MAX(id) as latest_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->class_message_id => (int) $row->latest_id]);

        // Unread: never opened, or someone replied since it was last opened.
        return $messageIds
            ->filter(fn ($id) => !$reads->has($id)
                || ($latestOther->get($id, 0) > (int) $reads->get($id)->last_reply_id))
            ->values()
            ->all();
    }

    public function unreadCount(User $user): int
    {
        return count($this->unreadIds($user));
    }

    public function markSeen(User $user, ClassMessage $message): void
    {
        // Not via replies(): its ORDER BY breaks MAX() on Oracle.
        $latestReply = (int) ClassMessageReply::where('class_message_id', $message->id)->max('id');

        if ($this->isSender($user, $message)) {
            $message->forceFill(['teacher_seen_at' => now(), 'teacher_seen_reply_id' => $latestReply])->save();

            return;
        }

        ClassMessageRead::updateOrCreate(
            ['class_message_id' => $message->id, 'user_id' => $user->id],
            ['read_at' => now(), 'last_reply_id' => $latestReply]
        );
    }

    public function teacherOf(User $user): ?Teacher
    {
        if (strtolower((string) $user->role) !== 'teacher') {
            return null;
        }

        return Teacher::where('user_id', $user->id)->first();
    }
}
