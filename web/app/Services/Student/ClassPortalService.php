<?php

namespace App\Services\Student;

use App\Models\ClassMessage;
use App\Models\ClassMessageReply;
use App\Models\User;
use App\Services\Chatbot\ScheduleQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The student / CR portal: a section's daily routine (with ready-to-
 * share WhatsApp text) and the CR's board of approved notices.
 */
class ClassPortalService
{
    public function __construct(private ScheduleQueryService $schedule)
    {
    }

    /**
     * Everything the routine page shows for one weekday.
     */
    public function dayPlan(User $student, ?string $day, CarbonImmutable $now): array
    {
        $days = $this->schedule->workingDays();
        $today = strtoupper($now->format('l'));
        $week = $this->weekSessions($student);

        // Default: today if it is a class day, else the next class day.
        $day = strtoupper((string) $day);
        if (!in_array($day, $days, true)) {
            $day = in_array($today, $days, true) ? $today : ($this->nextClassDay($days, $now) ?? ($days[0] ?? $today));
        }

        $date = $this->nextDate($day, $now);
        $isToday = $date->isSameDay($now);
        $sessions = $week->where('day', $day)->values();

        // Live state for today's classes: done / now / next / later.
        $nextMarked = false;
        $sessions = $sessions->map(function (array $s) use ($isToday, $now, &$nextMarked) {
            $state = 'later';
            if ($isToday) {
                $clock = $now->format('H:i');
                if (ScheduleQueryService::padTime($s['end']) <= $clock) {
                    $state = 'done';
                } elseif (ScheduleQueryService::padTime($s['start']) <= $clock) {
                    $state = 'now';
                } elseif (!$nextMarked) {
                    $state = 'next';
                    $nextMarked = true;
                }
            }

            return $s + ['state' => $state];
        });

        $changes = $this->changesOn($student, $date);

        return [
            'days' => collect($days)->map(fn ($d) => [
                'day' => $d,
                'label' => ucfirst(strtolower($d)),
                'short' => ucfirst(strtolower(substr($d, 0, 3))),
                'count' => $week->where('day', $d)->count(),
                'is_today' => $d === $today,
            ])->all(),
            'day' => $day,
            'date' => $date,
            'is_today' => $isToday,
            'sessions' => $sessions,
            'changes' => $changes,
            'week' => $week,
            'day_text' => $this->dayText($student, $date, $sessions, $changes),
            'week_text' => $this->weekText($student, $week, $days),
        ];
    }

    /**
     * "*CSE 47 · Section A — Routine*" etc., ready for the class group.
     */
    public function dayText(User $student, CarbonImmutable $date, Collection $sessions, Collection $changes): string
    {
        $lines = ['*' . $this->sectionLabel($student) . ' — Class Routine*', '*' . $date->format('l, d M Y') . '*', ''];

        if ($sessions->isEmpty()) {
            $lines[] = 'No classes on this day.';
        }

        foreach ($sessions->values() as $i => $s) {
            $lines[] = ($i + 1) . '. ' . $this->time($s['start']) . '–' . $this->time($s['end'])
                . ' · *' . $s['course_code'] . '*' . ($s['session_type'] === 'LAB' ? ' (Lab)' : '') . ' — ' . $s['course_name'];
            $lines[] = '    Room ' . $s['room'] . ($s['teachers'] ? ' · ' . implode(', ', $s['teachers']) : '');
        }

        if ($changes->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Changes for this day:*';
            foreach ($changes as $change) {
                $lines[] = '• ' . $change['text'];
            }
        }

        $lines[] = '';
        $lines[] = $this->signature($student);

        return implode("\n", $lines);
    }

    public function weekText(User $student, Collection $week, array $days): string
    {
        $lines = ['*' . $this->sectionLabel($student) . ' — Weekly Routine*', ''];

        foreach ($days as $day) {
            $sessions = $week->where('day', $day)->values();
            if ($sessions->isEmpty()) {
                continue;
            }

            $lines[] = '*' . ucfirst(strtolower($day)) . '*';
            foreach ($sessions as $s) {
                $lines[] = '• ' . $this->time($s['start']) . '–' . $this->time($s['end']) . ' · ' . $s['course_code']
                    . ($s['session_type'] === 'LAB' ? ' (Lab)' : '') . ' · ' . $s['room'];
            }
            $lines[] = '';
        }

        if (count($lines) === 2) {
            $lines[] = 'No published routine yet.';
            $lines[] = '';
        }

        $lines[] = $this->signature($student);

        return implode("\n", $lines);
    }

    /**
     * The CR's notice board, grouped by what the CR has to do.
     */
    public function noticeBoard(User $rep): array
    {
        $messages = ClassMessage::with(['course', 'teacher.user', 'notices'])
            ->where('section_id', $rep->section_id)
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->get();

        $completed = $messages->filter->isCompleted();

        $ready = $completed->whereNull('posted_at')
            ->map(fn (ClassMessage $m) => $this->boardItem($m))
            ->filter(fn ($item) => $item['notice'] !== null)
            ->values();

        $posted = $completed->whereNotNull('posted_at')
            ->sortByDesc('posted_at')
            ->take(20)
            ->map(fn (ClassMessage $m) => $this->boardItem($m))
            ->values();

        return [
            'ready' => $ready,
            'posted' => $posted,
            'todo' => $messages->filter->awaitsNotice()->values(),
            'waiting' => $messages->filter->awaitsReview()->values(),
        ];
    }

    /**
     * Notices ready to post plus sessions waiting on the CR, for the
     * menu badge.
     */
    public function pendingCount(User $rep): int
    {
        if (!$rep->isClassRep()) {
            return 0;
        }

        return ClassMessage::where('section_id', $rep->section_id)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', ClassMessage::COMPLETED)->whereNull('posted_at'))
                ->orWhereIn('status', [ClassMessage::AWAITING_NOTICE, ClassMessage::CHANGES_REQUESTED]))
            ->count();
    }

    public function whatsappUrl(string $text): string
    {
        return 'https://wa.me/?text=' . rawurlencode($text);
    }

    private function boardItem(ClassMessage $message): array
    {
        $notice = $message->notices->last();

        return [
            'message' => $message,
            'notice' => $notice,
            'text' => $notice?->body,
            'whatsapp' => $notice ? $this->whatsappUrl($notice->body) : null,
        ];
    }

    private function weekSessions(User $student): Collection
    {
        if (!$student->section_id) {
            return collect();
        }

        return $this->schedule->sessions(['sections' => [(int) $student->section_id]]);
    }

    /**
     * Approved class changes for this section on a date: cancelled,
     * moved away, moved here, or an extra class.
     */
    private function changesOn(User $student, CarbonImmutable $date): Collection
    {
        if (!$student->section_id) {
            return collect();
        }

        $day = $date->toDateString();

        return ClassMessage::with(['course', 'timeSlot', 'newTimeSlot'])
            ->where('section_id', $student->section_id)
            ->where('status', ClassMessage::COMPLETED)
            ->where(fn ($q) => $q->whereDate('class_date', $day)->orWhereDate('new_date', $day))
            ->orderBy('id')
            ->get()
            ->map(function (ClassMessage $m) use ($day) {
                $code = $m->course?->course_code;
                $class = $code ? "{$code} class" : 'A class';
                $slot = fn ($s) => $s ? ' (' . $this->time($s->start_time) . '–' . $this->time($s->end_time) . ')' : '';
                $isOriginal = $m->class_date?->toDateString() === $day;

                $text = match (true) {
                    $m->category === 'CANCEL' => "{$class}{$slot($m->timeSlot)} is *cancelled*.",
                    $m->category === 'RESCHEDULE' && $isOriginal => "{$class}{$slot($m->timeSlot)} moved to "
                        . ($m->new_date?->format('D, d M') ?? 'another day') . $slot($m->newTimeSlot) . '.',
                    $m->category === 'RESCHEDULE' => "Rescheduled {$class} on this day{$slot($m->newTimeSlot)}.",
                    $m->category === 'EXTRA_CLASS' => 'Extra ' . ($code ? "{$code} " : '') . "class{$slot($m->newTimeSlot)}.",
                    in_array($m->category, ['ROOM_CHANGE', 'ASSESSMENT'], true) => ($code ? "{$code}: " : '') . "{$m->subject}.",
                    default => $m->subject . '.',
                };

                return ['text' => $text, 'message' => $m, 'tone' => $m->category === 'CANCEL' ? 'danger' : 'warning'];
            });
    }

    private function nextClassDay(array $days, CarbonImmutable $now): ?string
    {
        for ($i = 1; $i <= 7; $i++) {
            $candidate = strtoupper($now->addDays($i)->format('l'));
            if (in_array($candidate, $days, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The coming date (today included) that falls on this weekday.
     */
    private function nextDate(string $day, CarbonImmutable $now): CarbonImmutable
    {
        for ($i = 0; $i < 7; $i++) {
            $date = $now->addDays($i);
            if (strtoupper($date->format('l')) === $day) {
                return $date->startOfDay();
            }
        }

        return $now->startOfDay();
    }

    private function sectionLabel(User $student): string
    {
        $student->loadMissing('section.batch');

        return $student->section?->label ?? 'My Section';
    }

    private function signature(User $student): string
    {
        $role = $student->class_rep && !str_contains($student->name, $student->class_rep) ? ', ' . $student->class_rep : '';

        return '— ' . $student->name . $role;
    }

    /** "13:20" → "1:20 PM". */
    private function time(?string $time): string
    {
        if (!$time) {
            return '';
        }

        return CarbonImmutable::createFromFormat('H:i', substr(ScheduleQueryService::padTime($time), 0, 5))->format('g:i A');
    }
}
