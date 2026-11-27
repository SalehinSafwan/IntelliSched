<?php

namespace App\Services\Chatbot;

use Carbon\CarbonImmutable;

final class QueryPlan
{
    public function __construct(
        public readonly string $intent,
        public readonly string $role,              // student | teacher
        public readonly array $scope,              // ['sections' => [...]] or ['teacher' => id]
        public readonly CarbonImmutable $now,
        public readonly array $courseIds = [],
        public readonly ?string $courseLabel = null,
        public readonly array $tutorIds = [],
        public readonly ?string $tutorLabel = null,
        public readonly ?string $day = null,       // SUNDAY ... when the question names a day
        public readonly ?string $dayLabel = null,  // "today (Monday)"
        public readonly bool $isClassDay = true,
        public readonly ?int $selfTeacherId = null,
    ) {
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function toArray(): array
    {
        return array_filter([
            'intent' => $this->intent,
            'role' => $this->role,
            'scope' => isset($this->scope['teacher']) ? 'own classes' : 'sections ' . implode(', ', $this->scope['sections'] ?? []),
            'course_ids' => $this->courseIds ?: null,
            'teacher_ids' => $this->tutorIds ?: null,
            'day' => $this->day,
        ], fn ($v) => $v !== null);
    }
}
