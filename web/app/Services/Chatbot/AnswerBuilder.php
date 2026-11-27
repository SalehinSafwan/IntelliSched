<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Collection;

class AnswerBuilder
{
    public function __construct(private ScheduleQueryService $schedule)
    {
    }

    public function answer(QueryPlan $plan): array
    {
        return match ($plan->intent) {
            Grammar::HELP => $this->help(),
            Grammar::DAY_SCHEDULE => $this->daySchedule($plan),
            Grammar::WEEK_SCHEDULE => $this->weekSchedule($plan),
            Grammar::COUNT_CLASSES => $this->countClasses($plan),
            Grammar::CURRENT_CLASS => $this->currentClass($plan),
            Grammar::NEXT_CLASS => $this->nextClass($plan),
            Grammar::FIRST_CLASS => $this->firstOrLast($plan, true),
            Grammar::LAST_CLASS => $this->firstOrLast($plan, false),
            Grammar::FREE_SLOTS => $this->freeSlots($plan),
            Grammar::COURSE_WHEN => $this->courseWhen($plan),
            Grammar::COURSE_TEACHER => $this->courseTeacher($plan),
            Grammar::COURSE_ROOM => $this->courseRoom($plan),
            Grammar::TEACHER_CLASSES => $this->teacherClasses($plan),
            Grammar::TEACHER_COURSES => $this->teacherCourses($plan),
            Grammar::MY_COURSES => $this->myCourses($plan),
            Grammar::MY_TEACHERS => $this->myTeachers($plan),
        };
    }

    // ── Handlers ────────────────────────────────────────────────────

    private function help(): array
    {
        return $this->reply(
            'Hi! I answer fixed questions about your published class routine — your classes for a day, '
            . 'when or where a course meets, who teaches it, your next class and free periods. '
            . 'Tap a sample question or open "All question formats".'
        );
    }

    private function daySchedule(QueryPlan $plan): array
    {
        if (!$plan->isClassDay) {
            return $this->reply($this->dayNoun($plan) . ' is not a class day, so there are no classes.');
        }

        $sessions = $this->sessions($plan, ['day' => $plan->day]);

        if ($sessions->isEmpty()) {
            return $this->reply('You have no classes ' . $plan->dayLabel . '.');
        }

        return $this->reply(
            'You have ' . $this->plural($sessions->count(), 'class', 'classes') . ' ' . $plan->dayLabel . ':',
            $this->sessionItems($plan, $sessions, false)
        );
    }

    private function weekSchedule(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan);

        if ($sessions->isEmpty()) {
            return $this->reply('You have no classes in the published routine.');
        }

        $days = $sessions->pluck('day')->unique()->count();

        return $this->reply(
            'Your weekly routine has ' . $this->plural($sessions->count(), 'class', 'classes')
            . ' over ' . $this->plural($days, 'day', 'days') . ':',
            $this->sessionItems($plan, $sessions)
        );
    }

    private function countClasses(QueryPlan $plan): array
    {
        if ($plan->day !== null) {
            $sessions = $plan->isClassDay ? $this->sessions($plan, ['day' => $plan->day]) : collect();

            return $this->reply(
                'You have ' . $this->plural($sessions->count(), 'class', 'classes') . ' ' . $plan->dayLabel . '.',
                $this->sessionItems($plan, $sessions, false)
            );
        }

        $sessions = $this->sessions($plan);
        $items = $sessions->groupBy('day')->map(fn (Collection $group, string $day) => [
            'title' => $this->dayName($day),
            'subtitle' => $this->plural($group->count(), 'class', 'classes'),
            'meta' => $group->pluck('course_code')->unique()->implode(', '),
        ])->values()->all();

        return $this->reply(
            'You have ' . $this->plural($sessions->count(), 'class', 'classes') . ' a week.',
            $items
        );
    }

    private function currentClass(QueryPlan $plan): array
    {
        $today = strtoupper($plan->now->format('l'));
        $time = $plan->now->format('H:i');
        $clock = $plan->now->format('g:i A') . ', ' . $plan->now->format('l');

        $current = $this->sessions($plan, ['day' => $today])
            ->first(fn ($s) => $this->pad($s['start']) <= $time && $time < $this->pad($s['end']));

        if ($current) {
            return $this->reply(
                "Right now ({$clock}) you have {$this->courseText($current)} in {$current['room']} until "
                . $this->time($current['end']) . '.',
                $this->sessionItems($plan, collect([$current]), false)
            );
        }

        $next = $this->upcoming($plan, $this->sessions($plan));
        $reply = "No class is running right now ({$clock}).";

        if ($next) {
            [$session, $when] = $next;
            $reply .= " Your next class is {$this->courseText($session)} {$when} at " . $this->time($session['start']) . '.';
            return $this->reply($reply, $this->sessionItems($plan, collect([$session])));
        }

        return $this->reply($reply);
    }

    private function nextClass(QueryPlan $plan): array
    {
        $filters = $plan->courseIds ? ['course_ids' => $plan->courseIds] : [];
        $next = $this->upcoming($plan, $this->sessions($plan, $filters));
        $what = $plan->courseLabel ? "{$plan->courseLabel} class" : 'class';

        if (!$next) {
            return $this->reply("I couldn't find an upcoming {$what} in the published routine.");
        }

        [$session, $when] = $next;

        $text = "Your next {$what} is " . ($plan->courseLabel ? '' : $this->courseText($session) . ' ')
            . "{$when} at " . $this->time($session['start']) . " in {$session['room']}";
        $text .= $session['teachers'] ? ' with ' . $this->list($session['teachers']) . '.' : '.';

        return $this->reply($text, $this->sessionItems($plan, collect([$session])));
    }

    private function firstOrLast(QueryPlan $plan, bool $first): array
    {
        $which = $first ? 'first' : 'last';

        if (!$plan->isClassDay) {
            return $this->reply($this->dayNoun($plan) . ' is not a class day, so there are no classes.');
        }

        $sessions = $this->sessions($plan, ['day' => $plan->day]);
        if ($sessions->isEmpty()) {
            return $this->reply('You have no classes ' . $plan->dayLabel . '.');
        }

        $session = $first ? $sessions->first() : $sessions->last();
        $text = $first
            ? "Your first class {$plan->dayLabel} is {$this->courseText($session)} at " . $this->time($session['start'])
            : "Your last class {$plan->dayLabel} is {$this->courseText($session)}, ending at " . $this->time($session['end']);

        return $this->reply("{$text} in {$session['room']}.", $this->sessionItems($plan, collect([$session]), false));
    }

    private function freeSlots(QueryPlan $plan): array
    {
        if (!$plan->isClassDay) {
            return $this->reply($this->dayNoun($plan) . ' is not a class day — you are free all day.');
        }

        $sessions = $this->sessions($plan, ['day' => $plan->day]);
        $free = $this->schedule->teachingSlots($plan->day)->reject(function ($slot) use ($sessions) {
            $start = $this->pad($slot->start_time);
            $end = $this->pad($slot->end_time);

            return $sessions->contains(fn ($s) => $this->pad($s['start']) < $end && $start < $this->pad($s['end']));
        });

        if ($sessions->isEmpty()) {
            return $this->reply('You have no classes ' . $plan->dayLabel . ' — the whole day is free.');
        }

        if ($free->isEmpty()) {
            return $this->reply('You have no free periods ' . $plan->dayLabel . '.');
        }

        return $this->reply(
            'You are free for ' . $this->plural($free->count(), 'period', 'periods') . ' ' . $plan->dayLabel . ':',
            $free->map(fn ($slot) => [
                'title' => $this->time($slot->start_time) . ' – ' . $this->time($slot->end_time),
                'subtitle' => 'Period ' . $slot->slot_number,
                'meta' => 'Free',
            ])->values()->all()
        );
    }

    private function courseWhen(QueryPlan $plan): array
    {
        $all = $this->sessions($plan, ['course_ids' => $plan->courseIds]);

        if ($plan->day !== null) {
            $onDay = $all->where('day', $plan->day)->values();

            if ($onDay->isEmpty()) {
                $days = $all->pluck('day')->unique()->map(fn ($d) => $this->dayName($d))->values()->all();
                return $this->reply(
                    "There is no {$plan->courseLabel} class {$plan->dayLabel}."
                    . ($days ? ' It meets on ' . $this->list($days) . '.' : '')
                );
            }

            return $this->reply(
                "{$plan->courseLabel} {$plan->dayLabel}:",
                $this->sessionItems($plan, $onDay, false)
            );
        }

        return $this->reply(
            "{$plan->courseLabel} meets " . $this->plural($all->count(), 'time', 'times') . ' a week:',
            $this->sessionItems($plan, $all)
        );
    }

    private function courseTeacher(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan, ['course_ids' => $plan->courseIds]);
        $bySection = $sessions->groupBy('section');

        if ($bySection->count() === 1) {
            $teachers = $sessions->pluck('teachers')->flatten()->unique()->values()->all();

            return $this->reply("{$plan->courseLabel} is taught by " . $this->list($teachers) . '.');
        }

        return $this->reply(
            "{$plan->courseLabel} is taught in " . $this->plural($bySection->count(), 'section', 'sections') . ':',
            $bySection->map(fn (Collection $group, string $section) => [
                'title' => $section,
                'subtitle' => $this->list($group->pluck('teachers')->flatten()->unique()->values()->all()),
                'meta' => $this->plural($group->count(), 'class', 'classes') . ' a week',
            ])->values()->all()
        );
    }

    private function courseRoom(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan, array_filter([
            'course_ids' => $plan->courseIds,
            'day' => $plan->day,
        ]));

        if ($sessions->isEmpty()) {
            return $this->reply("There is no {$plan->courseLabel} class {$plan->dayLabel}.");
        }

        $rooms = $sessions->pluck('room')->unique()->values()->all();
        $when = $plan->dayLabel ? ' ' . $plan->dayLabel : '';

        return $this->reply(
            "{$plan->courseLabel}{$when} is held in " . $this->list($rooms) . ':',
            $this->sessionItems($plan, $sessions, $plan->day === null)
        );
    }

    private function teacherClasses(QueryPlan $plan): array
    {
        $filters = array_filter(['teacher_ids' => $plan->tutorIds, 'day' => $plan->day]);
        $sessions = $this->sessions($plan, $filters);
        $self = $plan->selfTeacherId !== null && $plan->tutorIds === [$plan->selfTeacherId];
        $who = $self ? 'You have' : "{$plan->tutorLabel} has";
        $where = $self ? '' : ($plan->isStudent() ? ' with your section' : ' in your sections');
        $when = $plan->dayLabel ? ' ' . $plan->dayLabel : ' a week';

        if ($sessions->isEmpty()) {
            return $this->reply("{$who} no classes{$when}{$where}.");
        }

        return $this->reply(
            "{$who} " . $this->plural($sessions->count(), 'class', 'classes') . "{$when}{$where}:",
            $this->sessionItems($plan, $sessions, $plan->day === null)
        );
    }

    private function teacherCourses(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan, ['teacher_ids' => $plan->tutorIds]);
        $self = $plan->selfTeacherId !== null && $plan->tutorIds === [$plan->selfTeacherId];
        $who = $self ? 'You teach' : "{$plan->tutorLabel} teaches" . ($plan->isStudent() ? ' your section' : '');

        return $this->reply("{$who}:", $this->courseItems($plan, $sessions));
    }

    private function myCourses(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan);
        $count = $sessions->pluck('course_id')->unique()->count();

        if ($count === 0) {
            return $this->reply('You have no courses in the published routine.');
        }

        $verb = $plan->isStudent() ? 'You take' : 'You teach';

        return $this->reply(
            "{$verb} " . $this->plural($count, 'course', 'courses') . ':',
            $this->courseItems($plan, $sessions)
        );
    }

    private function myTeachers(QueryPlan $plan): array
    {
        $sessions = $this->sessions($plan);

        $items = $sessions->groupBy('course_id')->map(function (Collection $group) use ($plan) {
            $first = $group->first();
            $names = [];
            foreach ($group as $s) {
                foreach ($s['teacher_ids'] as $i => $id) {
                    if ($plan->isStudent() || $id !== $plan->selfTeacherId) {
                        $names[$id] = $s['teachers'][$i];
                    }
                }
            }
            $names = collect($names);

            return [
                'title' => $first['course_code'] . ' · ' . $first['course_name'],
                'subtitle' => $names->isEmpty() ? 'Only you' : $this->list($names->unique()->values()->all()),
                'meta' => $plan->isStudent() ? '' : $group->pluck('section')->unique()->implode(', '),
            ];
        })->values()->all();

        if ($items === []) {
            return $this->reply('No teachers found in the published routine.');
        }

        return $this->reply($plan->isStudent() ? 'Your teachers by course:' : 'Your co-teachers by course:', $items);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function sessions(QueryPlan $plan, array $filters = []): Collection
    {
        return $this->schedule->sessions($plan->scope, $filters);
    }

    private function upcoming(QueryPlan $plan, Collection $sessions): ?array
    {
        $time = $plan->now->format('H:i');

        for ($offset = 0; $offset <= 7; $offset++) {
            $date = $plan->now->addDays($offset);
            $day = strtoupper($date->format('l'));

            $match = $sessions->first(fn ($s) => $s['day'] === $day
                && ($offset === 0 ? $this->pad($s['start']) > $time
                    : ($offset < 7 || $this->pad($s['start']) <= $time)));

            if ($match) {
                $when = match ($offset) {
                    0 => 'today',
                    1 => 'tomorrow',
                    default => 'on ' . $date->format('l'),
                };

                return [$match, $when];
            }
        }

        return null;
    }

    private function sessionItems(QueryPlan $plan, Collection $sessions, bool $withDay = true): array
    {
        $showSection = !$plan->isStudent();

        return $sessions->map(fn ($s) => [
            'title' => $s['course_code'] . ' · ' . $s['course_name'] . ($s['session_type'] === 'LAB' ? ' (Lab)' : ''),
            'subtitle' => ($withDay ? $this->dayName($s['day']) . ' · ' : '')
                . $this->time($s['start']) . ' – ' . $this->time($s['end']),
            'meta' => implode(' · ', array_filter([
                $s['room'],
                $this->list($s['teachers']),
                $showSection ? $s['section'] : null,
            ])),
        ])->values()->all();
    }

    private function courseItems(QueryPlan $plan, Collection $sessions): array
    {
        return $sessions->groupBy('course_id')->map(function (Collection $group) use ($plan) {
            $first = $group->first();
            $types = $group->pluck('session_type')->unique()->map(fn ($t) => ucfirst(strtolower($t)))->implode(' + ');

            return [
                'title' => $first['course_code'] . ' · ' . $first['course_name'],
                'subtitle' => $this->plural($group->count(), 'class', 'classes') . ' a week · ' . $types,
                'meta' => $plan->isStudent()
                    ? $this->list($group->pluck('teachers')->flatten()->unique()->values()->all())
                    : $group->pluck('section')->unique()->implode(', '),
            ];
        })->values()->all();
    }

    private function courseText(array $s): string
    {
        return "{$s['course_code']} ({$s['course_name']})";
    }

    private function dayName(string $day): string
    {
        return ucfirst(strtolower($day));
    }

    private function time(string $time): string
    {
        $stamp = strtotime($this->pad($time));

        return $stamp ? date('g:i A', $stamp) : $time;
    }

    private function pad(string $time): string
    {
        return ScheduleQueryService::padTime($time);
    }

    /** "on Friday" → "Friday", "today (Monday)" → "Today (Monday)". */
    private function dayNoun(QueryPlan $plan): string
    {
        return ucfirst(preg_replace('/^on /', '', (string) $plan->dayLabel));
    }

    private function plural(int $n, string $one, string $many): string
    {
        return $n . ' ' . ($n === 1 ? $one : $many);
    }

    private function list(array $names): string
    {
        $names = array_values(array_filter($names));

        if (count($names) <= 1) {
            return $names[0] ?? '';
        }

        return implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
    }

    private function reply(string $answer, array $items = []): array
    {
        return ['answer' => $answer, 'items' => $items];
    }
}
