<?php

namespace Tests\Unit;

use App\Services\Chatbot\ChatbotException;
use App\Services\Chatbot\Grammar;
use App\Services\Chatbot\Lexicon;
use App\Services\Chatbot\QuestionLexer;
use App\Services\Chatbot\QuestionParser;
use App\Services\Chatbot\TokenType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChatbotParserTest extends TestCase
{
    private QuestionLexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lexer = new QuestionLexer(new Lexicon(
            [
                ['id' => 1, 'course_code' => 'CSE 3201', 'course_name' => 'Data Structures'],
                ['id' => 2, 'course_code' => 'CSE-3202', 'course_name' => 'Data Structures Lab'],
                ['id' => 3, 'course_code' => 'MATH3203', 'course_name' => 'Linear Algebra'],
            ],
            [
                ['id' => 7, 'name' => 'Dr. Md. Abdur Rahman', 'employee_code' => 'T001'],
                ['id' => 8, 'name' => 'Nusrat Jahan', 'employee_code' => 'T002'],
            ]
        ));
    }

    public function test_normalizer_unifies_course_code_spellings(): void
    {
        $this->assertSame('cse 3201', Lexicon::normalize('CSE-3201'));
        $this->assertSame('cse 3201', Lexicon::normalize('cse3201'));
        $this->assertSame('today class', Lexicon::normalize("Today's   class?"));
    }

    public function test_lexer_uses_longest_match_and_drops_fillers(): void
    {
        $tokens = $this->lexer->tokenize('Which room is the Data Structures Lab class on Sunday?');

        $this->assertSame(
            [TokenType::WHERE, TokenType::COURSE, TokenType::CLASS_KW, TokenType::DAY],
            array_map(fn ($t) => $t->type, $tokens)
        );
        $this->assertSame([2], $tokens[1]->value);
        $this->assertSame('SUNDAY', $tokens[3]->value);
    }

    public function test_lexer_resolves_teacher_aliases(): void
    {
        foreach (['Rahman', 'abdur rahman', 'Dr. Md. Abdur Rahman', 't001'] as $name) {
            $tokens = $this->lexer->tokenize($name);
            $this->assertCount(1, $tokens, $name);
            $this->assertSame(TokenType::TEACHER, $tokens[0]->type, $name);
            $this->assertSame([7], $tokens[0]->value, $name);
        }
    }

    public function test_lexer_reports_unknown_words(): void
    {
        try {
            $this->lexer->analyze('blah class today');
            $this->fail('Expected a lexical error.');
        } catch (ChatbotException $e) {
            $this->assertSame(ChatbotException::LEXICAL, $e->stage);
            $this->assertStringContainsString('blah', $e->getMessage());
        }
    }

    public static function questions(): array
    {
        return [
            ["Today's class", Grammar::DAY_SCHEDULE],
            ['What classes do I have tomorrow?', Grammar::DAY_SCHEDULE],
            ['Classes on Monday', Grammar::DAY_SCHEDULE],
            ['My weekly routine', Grammar::WEEK_SCHEDULE],
            ['How many classes today?', Grammar::COUNT_CLASSES],
            ['How many classes this week?', Grammar::COUNT_CLASSES],
            ['What is my next class?', Grammar::NEXT_CLASS],
            ['Where is my next class?', Grammar::NEXT_CLASS],
            ['When is the next CSE 3201 class?', Grammar::NEXT_CLASS],
            ['What class is running now?', Grammar::CURRENT_CLASS],
            ['First class tomorrow', Grammar::FIRST_CLASS],
            ['When is my last class today?', Grammar::LAST_CLASS],
            ['Am I free on Monday?', Grammar::FREE_SLOTS],
            ['Free periods today', Grammar::FREE_SLOTS],
            ['When is CSE 3201 class?', Grammar::COURSE_WHEN],
            ['CSE 3201 class when', Grammar::COURSE_WHEN],
            ['Is there CSE3201 class on Monday?', Grammar::COURSE_WHEN],
            ['Who teaches CSE 3201?', Grammar::COURSE_TEACHER],
            ['CSE 3201 class by which teacher', Grammar::COURSE_TEACHER],
            ['Who is the teacher of Data Structures?', Grammar::COURSE_TEACHER],
            ['Where is CSE 3201 class?', Grammar::COURSE_ROOM],
            ['Which room is CSE 3202 on Sunday?', Grammar::COURSE_ROOM],
            ["When is Rahman sir's class?", Grammar::TEACHER_CLASSES],
            ['Classes by T001 on Monday', Grammar::TEACHER_CLASSES],
            ['What does Nusrat Jahan teach?', Grammar::TEACHER_COURSES],
            ['Which courses do I teach?', Grammar::MY_COURSES],
            ['How many courses do I have?', Grammar::MY_COURSES],
            ['Who are my teachers?', Grammar::MY_TEACHERS],
            ['Who are my co-teachers?', Grammar::MY_TEACHERS],
            ['Help', Grammar::HELP],
            ['hello', Grammar::HELP],
        ];
    }

    #[DataProvider('questions')]
    public function test_supported_questions_parse_to_intent(string $question, string $intent): void
    {
        $result = (new QuestionParser())->parse($this->lexer->analyze($question));

        $this->assertSame($intent, $result->intent, $question);
    }

    public static function rejected(): array
    {
        return [
            ['Who teaches', 'supported question'],
            ['CSE 3201 MATH 3203 class', 'one course at a time'],
            ['Classes today tomorrow', 'only one day'],
            ['CSE 3201 next today when', 'CSE 3201'],
        ];
    }

    #[DataProvider('rejected')]
    public function test_unsupported_questions_raise_syntax_errors(string $question, string $hint): void
    {
        try {
            (new QuestionParser())->parse($this->lexer->analyze($question));
            $this->fail("Expected a syntax error for: {$question}");
        } catch (ChatbotException $e) {
            $this->assertSame(ChatbotException::SYNTAX, $e->stage);
            $this->assertStringContainsString($hint, $e->getMessage());
        }
    }
}
