<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\DB;

class Lexicon
{
    public const FILLER = 'FILLER';

    private const KEYWORDS = [
        TokenType::WHEN => [
            'when', 'what time', 'which time', 'time', 'timing', 'timings',
            'which day', 'which days', 'what day', 'what days',
        ],
        TokenType::WHO => [
            'who', 'whom', 'which teacher', 'what teacher', 'which faculty',
            'which sir', 'which instructor',
        ],
        TokenType::WHERE => [
            'where', 'which room', 'what room', 'which venue',
        ],
        TokenType::CLASS_KW => [
            'class', 'classes', 'lecture', 'lectures', 'lab', 'labs',
            'period', 'periods', 'routine', 'routines', 'schedule', 'schedules',
            'session', 'sessions', 'slot', 'slots', 'timetable',
        ],
        TokenType::COURSE_KW => ['course', 'courses', 'subject', 'subjects'],
        TokenType::TEACHER_KW => [
            'teacher', 'teachers', 'faculty', 'faculties', 'instructor', 'instructors',
            'co teacher', 'co teachers', 'coteacher', 'coteachers',
        ],
        TokenType::ROOM_KW => ['room', 'rooms', 'venue', 'room number', 'room no', 'classroom'],
        TokenType::TEACHES => [
            'teach', 'teaches', 'teaching', 'take', 'takes', 'taking', 'taken',
            'conduct', 'conducts', 'conducting', 'conducted',
        ],
        TokenType::BY => ['by'],
        TokenType::NEXT => ['next', 'upcoming', 'coming'],
        TokenType::NOW => [
            'now', 'right now', 'current', 'currently', 'ongoing', 'running', 'running now',
            'going on', 'going on now', 'happening', 'happening now', 'at the moment',
        ],
        TokenType::FIRST => ['first', 'earliest', 'start', 'starts', 'begin', 'begins'],
        TokenType::LAST => ['last', 'final', 'end', 'ends', 'finish', 'finishes', 'over'],
        TokenType::FREE => ['free', 'free time', 'vacant', 'empty', 'off', 'leisure', 'gap', 'gaps'],
        TokenType::COUNT => ['how many', 'number of', 'count', 'total'],
        TokenType::WEEK => ['week', 'weekly', 'full', 'whole', 'entire'],
        TokenType::HELP => [
            'help', 'example', 'examples', 'commands', 'menu',
            'what can i ask', 'what can you do', 'what can you answer',
        ],
    ];

    /** Relative dates: phrase => days from today. */
    private const DATES = [
        'today' => 0, 'todays' => 0, 'tonight' => 0,
        'tomorrow' => 1, 'tomorrows' => 1, 'tmrw' => 1, 'tmr' => 1,
        'day after tomorrow' => 2,
        'yesterday' => -1,
    ];

    /** Day names and abbreviations. */
    private const DAYS = [
        'saturday' => 'SATURDAY', 'sat' => 'SATURDAY',
        'sunday' => 'SUNDAY', 'sun' => 'SUNDAY',
        'monday' => 'MONDAY', 'mon' => 'MONDAY',
        'tuesday' => 'TUESDAY', 'tue' => 'TUESDAY', 'tues' => 'TUESDAY',
        'wednesday' => 'WEDNESDAY', 'wed' => 'WEDNESDAY',
        'thursday' => 'THURSDAY', 'thu' => 'THURSDAY', 'thur' => 'THURSDAY', 'thurs' => 'THURSDAY',
        'friday' => 'FRIDAY', 'fri' => 'FRIDAY',
    ];

    /** Words that carry no meaning for the grammar. */
    private const FILLERS = [
        'a', 'an', 'the', 'is', 'are', 'am', 'was', 'were', 'be', 'will', 'shall',
        'do', 'does', 'did', 'have', 'has', 'had', 'can', 'could', 'would',
        'i', 'me', 'my', 'mine', 'we', 'our', 'us', 'you', 'your', 'it', 'its',
        'on', 'at', 'in', 'of', 'for', 'to', 'from', 'with', 'about', 'and', 'or',
        'what', 'whats', 'which', 'show', 'tell', 'list', 'give', 'get', 'see', 'find',
        'there', 'any', 'this', 'that', 'these', 'those', 'all', 'held', 'happen',
        'happens', 'scheduled', 'please', 'pls', 'plz', 'kindly', 'sir', 'madam',
        'mam', 'maam', 'miss', 'hi', 'hello', 'hey', 'assalamualaikum', 'salam',
        'need', 'know', 'want', 'like', 'want to', 'details', 'detail', 'info',
        'information', 's', 'still', 'again', 'one', 'then',
        'day', 'days', 'number', 'no',
    ];

    /** Honorifics dropped when building teacher-name aliases. */
    private const HONORIFICS = ['dr', 'prof', 'professor', 'md', 'mohammad', 'mohammed', 'muhammad', 'mr', 'mrs', 'ms', 'engr', 'sir'];

    /** phrase => [type, value] */
    private array $phrases = [];

    private int $longest = 1;

    /**
     * @param  array  $courses   rows with id, course_code, course_name
     * @param  array  $teachers  rows with id, name, employee_code
     */
    public function __construct(array $courses = [], array $teachers = [])
    {
        foreach (self::FILLERS as $phrase) {
            $this->add($phrase, self::FILLER);
        }

        foreach (self::KEYWORDS as $type => $phrases) {
            foreach ($phrases as $phrase) {
                $this->add($phrase, $type);
            }
        }

        foreach (self::DATES as $phrase => $offset) {
            $this->add($phrase, TokenType::DATE, $offset);
        }

        foreach (self::DAYS as $phrase => $day) {
            $this->add($phrase, TokenType::DAY, $day);
        }

        $this->addCourses($courses);
        $this->addTeachers($teachers);
    }

    /**
     * Lexicon with every active course and teacher in the database.
     */
    public static function fromDatabase(): self
    {
        $courses = DB::table('courses')
            ->get(['id', 'course_code', 'course_name'])
            ->map(fn ($row) => (array) $row)
            ->all();

        $teachers = DB::table('teachers as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->get(['t.id', 'u.name', 't.employee_code'])
            ->map(fn ($row) => (array) $row)
            ->all();

        return new self($courses, $teachers);
    }

    /**
     * Lower-case, drop punctuation and possessives, and split letters
     * from digits so "CSE-3201", "cse3201" and "CSE 3201" all become
     * "cse 3201".
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['’', '‘', '`'], "'", $text);
        $text = preg_replace("/'s\b/u", '', $text);
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);
        $text = preg_replace('/(?<=[a-z])(?=[0-9])|(?<=[0-9])(?=[a-z])/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Entry for a phrase, or null when it is not in the table.
     *
     * @return array{0:string,1:mixed}|null
     */
    public function lookup(string $phrase): ?array
    {
        return $this->phrases[$phrase] ?? null;
    }

    public function longestPhrase(): int
    {
        return $this->longest;
    }

    private function addCourses(array $courses): void
    {
        $byPhrase = [];
        $byNumber = [];

        foreach ($courses as $course) {
            $id = (int) $course['id'];
            $code = self::normalize((string) $course['course_code']);
            $name = self::normalize((string) $course['course_name']);

            $byPhrase[$code][] = $id;
            $byPhrase[str_replace(' ', '', $code)][] = $id;
            if ($name !== '') {
                $byPhrase[$name][] = $id;
            }

            // The bare number ("3201") is accepted when it is unique.
            if (preg_match('/(\d{3,})$/', $code, $m)) {
                $byNumber[$m[1]][] = $id;
            }
        }

        foreach ($byNumber as $number => $ids) {
            if (count($ids) === 1 && !isset($byPhrase[$number])) {
                $byPhrase[$number] = $ids;
            }
        }

        foreach ($byPhrase as $phrase => $ids) {
            $this->addEntity($phrase, TokenType::COURSE, array_values(array_unique($ids)));
        }
    }

    private function addTeachers(array $teachers): void
    {
        $byPhrase = [];
        $byWord = [];

        foreach ($teachers as $teacher) {
            $id = (int) $teacher['id'];
            $name = self::normalize((string) ($teacher['name'] ?? ''));
            $code = self::normalize((string) ($teacher['employee_code'] ?? ''));

            if ($code !== '') {
                $byPhrase[$code][] = $id;
            }

            if ($name === '') {
                continue;
            }

            $byPhrase[$name][] = $id;

            $words = array_values(array_diff(explode(' ', $name), self::HONORIFICS));
            if ($words) {
                $byPhrase[implode(' ', $words)][] = $id;
            }

            // A single name word ("rahman") works when only one teacher has it.
            foreach (array_unique($words) as $word) {
                if (strlen($word) >= 3 && !ctype_digit($word)) {
                    $byWord[$word][] = $id;
                }
            }
        }

        foreach ($byWord as $word => $ids) {
            $ids = array_values(array_unique($ids));
            if (count($ids) === 1 && !isset($byPhrase[$word])) {
                $byPhrase[$word] = $ids;
            }
        }

        foreach ($byPhrase as $phrase => $ids) {
            $this->addEntity($phrase, TokenType::TEACHER, array_values(array_unique($ids)));
        }
    }

    /**
     * Entities never replace a keyword or filler ("lab", "all", "sun").
     * A phrase shared by a course and a teacher stays with the course.
     */
    private function addEntity(string $phrase, string $type, array $ids): void
    {
        if ($phrase === '' || isset($this->phrases[$phrase])) {
            return;
        }

        $this->add($phrase, $type, $ids);
    }

    private function add(string $phrase, string $type, mixed $value = null): void
    {
        $this->phrases[$phrase] = [$type, $value];
        $this->longest = max($this->longest, substr_count($phrase, ' ') + 1);
    }
}
