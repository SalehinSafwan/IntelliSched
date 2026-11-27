<?php

namespace App\Services\Chatbot;

use App\Models\Teacher;
use App\Models\User;
use App\Services\Teacher\TeacherSectionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SemanticAnalyzer
{
    /** Intents about one course. */
    private const COURSE_INTENTS = [
        Grammar::COURSE_WHEN, Grammar::COURSE_TEACHER, Grammar::COURSE_ROOM, Grammar::NEXT_CLASS,
    ];

    /** Intents about one teacher. */
    private const TUTOR_INTENTS = [Grammar::TEACHER_CLASSES, Grammar::TEACHER_COURSES];

    /** Intents that default to today when no day is named. */
    private const TODAY_INTENTS = [Grammar::FIRST_CLASS, Grammar::LAST_CLASS, Grammar::FREE_SLOTS];

    public function __construct(
        private ScheduleQueryService $schedule,
        private TeacherSectionService $sections,
    ) {
    }

    public function analyze(ParseResult $parse, User $user, CarbonImmutable $now): QueryPlan
    {
        $role = strtolower((string) $user->role);

        [$ownScope, $reachScope, $selfTeacherId] = $this->scopes($role, $user, $parse);

        if ($parse->intent !== Grammar::HELP && $this->schedule->publishedRunIds() === []) {
            $this->fail($parse, 'No class routine has been published yet, so there is nothing to look up.');
        }

        // Which day the question refers to.
        [$day, $dayLabel] = $this->resolveDay($parse, $now);
        if ($day === null && in_array($parse->intent, self::TODAY_INTENTS, true)) {
            [$day, $dayLabel] = [strtoupper($now->format('l')), 'today (' . $now->format('l') . ')'];
        }
        $isClassDay = $day === null || in_array($day, $this->schedule->workingDays(), true);

        $scope = $ownScope;
        $courseIds = [];
        $courseLabel = null;
        $tutorIds = [];
        $tutorLabel = null;

        $courseToken = $parse->first(TokenType::COURSE);
        if ($courseToken && in_array($parse->intent, self::COURSE_INTENTS, true)) {
            [$scope, $courseIds] = $this->resolveCourse($parse, $courseToken, $ownScope, $reachScope);
            $courseLabel = $this->courseLabel($courseIds);
        }

        $tutorToken = $parse->first(TokenType::TEACHER);
        if ($tutorToken && in_array($parse->intent, self::TUTOR_INTENTS, true)) {
            [$scope, $tutorIds] = $this->resolveTutor($parse, $tutorToken, $ownScope, $reachScope, $selfTeacherId);
            $tutorLabel = $this->tutorLabel($tutorIds[0]);
        }

        return new QueryPlan(
            intent: $parse->intent,
            role: $role,
            scope: $scope,
            now: $now,
            courseIds: $courseIds,
            courseLabel: $courseLabel,
            tutorIds: $tutorIds,
            tutorLabel: $tutorLabel,
            day: $day,
            dayLabel: $dayLabel,
            isClassDay: $isClassDay,
            selfTeacherId: $selfTeacherId,
        );
    }


    private function scopes(string $role, User $user, ParseResult $parse): array
    {
        if ($role === 'student') {
            if (!$user->section_id) {
                $this->fail($parse, 'Your account is not linked to a section yet. Ask the coordinator to assign your section.');
            }

            $scope = ['sections' => [(int) $user->section_id]];

            return [$scope, $scope, null];
        }

        if ($role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                $this->fail($parse, 'Your account is not linked to a teacher profile yet. Ask an administrator to link it.');
            }

            $reach = array_values(array_unique(array_merge(
                $this->sections->teachingSectionIds($teacher),
                $this->sections->supervisedSectionIds($teacher)
            )));

            return [['teacher' => (int) $teacher->id], ['sections' => $reach ?: [0]], (int) $teacher->id];
        }

        $this->fail($parse, 'The schedule assistant is available to teachers and students.');
    }

    private function resolveDay(ParseResult $parse, CarbonImmutable $now): array
    {
        $token = $parse->first(TokenType::DATE, TokenType::DAY);
        if (!$token) {
            return [null, null];
        }

        if ($token->type === TokenType::DAY) {
            $name = ucfirst(strtolower($token->value));
            return [$token->value, 'on ' . $name];
        }

        $date = $now->addDays((int) $token->value);
        $word = match ((int) $token->value) {
            0 => 'today',
            1 => 'tomorrow',
            -1 => 'yesterday',
            default => 'on ' . $date->format('j M'),
        };

        return [strtoupper($date->format('l')), $word . ' (' . $date->format('l') . ')'];
    }

    private function resolveCourse(ParseResult $parse, Token $token, array $own, array $reach): array
    {
        $ids = array_map('intval', (array) $token->value);

        foreach ([$own, $reach] as $scope) {
            $found = $this->schedule->sessions($scope, ['course_ids' => $ids])
                ->pluck('course_id')->unique()->values()->all();
            if ($found) {
                return [$scope, $found];
            }
        }

        $label = $this->courseLabel($ids) ?? strtoupper($token->lexeme);
        $this->fail($parse, "{$label} is not in your published routine.");
    }

    private function resolveTutor(ParseResult $parse, Token $token, array $own, array $reach, ?int $self): array
    {
        $ids = array_map('intval', (array) $token->value);

        // A teacher asking about themself.
        if ($self !== null && in_array($self, $ids, true)) {
            return [$own, [$self]];
        }

        $found = $this->schedule->sessions($reach, ['teacher_ids' => $ids])
            ->pluck('teacher_ids')->flatten()->intersect($ids)->unique()->values()->all();

        $name = ucwords($token->lexeme);

        if ($found === []) {
            $where = $self === null ? 'your section' : 'the sections you teach or supervise';
            $this->fail($parse, "{$name} has no classes in {$where}.");
        }

        if (count($found) > 1) {
            $this->fail($parse, "More than one teacher matches \"{$name}\". Please use the full name or employee code.");
        }

        return [$reach, $found];
    }

    private function courseLabel(array $ids): ?string
    {
        $course = DB::table('courses')->whereIn('id', $ids ?: [0])->orderBy('course_code')->first();

        return $course ? "{$course->course_code} ({$course->course_name})" : null;
    }

    private function tutorLabel(int $teacherId): string
    {
        $row = DB::table('teachers as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->where('t.id', $teacherId)
            ->first(['u.name', 't.employee_code']);

        return $row?->name ?: ($row?->employee_code ?? 'This teacher');
    }

    private function fail(ParseResult $parse, string $message): never
    {
        throw new ChatbotException(ChatbotException::SEMANTIC, $message, $parse->tokens);
    }
}
