<?php

namespace App\Services\Chatbot;


class QuestionCatalog
{
    /**
     * @param  array{course:string, teacher:string, day:string}  $examples
     */
    public function formats(string $role, array $examples): array
    {
        $teacher = $role === 'teacher';
        $c = $examples['course'];
        $t = $examples['teacher'];
        $d = $examples['day'];

        $groups = [
            [
                'title' => 'Classes on a day',
                'icon' => 'bi-calendar-day',
                'formats' => [
                    ["Today's class", "Today's class"],
                    ['What classes do I have tomorrow?', 'What classes do I have tomorrow?'],
                    ['Classes on <day>', "Classes on {$d}"],
                    ['How many classes today?', 'How many classes today?'],
                    ['How many classes this week?', 'How many classes this week?'],
                    ['My weekly routine', 'My weekly routine'],
                ],
            ],
            [
                'title' => 'Now, next and free time',
                'icon' => 'bi-clock-history',
                'formats' => [
                    ['What is my next class?', 'What is my next class?'],
                    ['Where is my next class?', 'Where is my next class?'],
                    ['What class is running now?', 'What class is running now?'],
                    ['First class tomorrow', 'First class tomorrow'],
                    ['When is my last class today?', 'When is my last class today?'],
                    ['Am I free on <day>?', "Am I free on {$d}?"],
                    ['Free periods today', 'Free periods today'],
                ],
            ],
            [
                'title' => 'About a course',
                'icon' => 'bi-journal-bookmark',
                'formats' => [
                    ['When is <course> class?', "When is {$c} class?"],
                    ['<course> class when', "{$c} class when"],
                    ['Is there <course> class on <day>?', "Is there {$c} class on {$d}?"],
                    ['Who teaches <course>?', "Who teaches {$c}?"],
                    ['<course> class by which teacher', "{$c} class by which teacher"],
                    ['Where is <course> class?', "Where is {$c} class?"],
                    ['Which room is <course> on <day>?', "Which room is {$c} on {$d}?"],
                    ['When is the next <course> class?', "When is the next {$c} class?"],
                ],
            ],
            [
                'title' => 'About a teacher',
                'icon' => 'bi-person-badge',
                'formats' => [
                    ["When is <teacher>'s class?", "When is {$t}'s class?"],
                    ['What does <teacher> teach?', "What does {$t} teach?"],
                    ['Classes by <teacher> on <day>', "Classes by {$t} on {$d}"],
                ],
            ],
            [
                'title' => $teacher ? 'My courses and co-teachers' : 'My courses and teachers',
                'icon' => 'bi-people',
                'formats' => [
                    $teacher
                        ? ['Which courses do I teach?', 'Which courses do I teach?']
                        : ['Which courses do I take?', 'Which courses do I take?'],
                    $teacher
                        ? ['Who are my co-teachers?', 'Who are my co-teachers?']
                        : ['Who are my teachers?', 'Who are my teachers?'],
                    ['How many courses do I have?', 'How many courses do I have?'],
                    ['Help', 'Help'],
                ],
            ],
        ];

        foreach ($groups as &$group) {
            $group['formats'] = array_map(
                fn ($pair) => ['format' => $pair[0], 'example' => $pair[1]],
                $group['formats']
            );
        }

        return [
            'groups' => $groups,
            'quick' => [
                "Today's class",
                'What is my next class?',
                "When is {$c} class?",
                "{$c} class by which teacher",
                'Free periods today',
            ],
            'placeholders' => [
                '<course>' => 'a course code or name, e.g. ' . $c,
                '<teacher>' => 'a teacher name or employee code, e.g. ' . $t,
                '<day>' => 'today, tomorrow or a weekday, e.g. ' . $d,
            ],
        ];
    }
}
