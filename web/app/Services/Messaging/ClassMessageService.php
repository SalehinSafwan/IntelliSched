<?php

namespace App\Services\Messaging;

use App\Models\ClassMessage;
use App\Models\ClassMessageRead;
use App\Models\ClassMessageReply;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who may read which teacher → CR/ACR message.
 *
 * A message belongs to one teacher and one section. Only the teacher
 * who sent it and the active CR and ACR of that section can open it;
 * nobody else, not even other students of the section.
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
