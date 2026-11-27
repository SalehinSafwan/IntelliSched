<?php

namespace App\Services\Chatbot;

use App\Models\Teacher;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotService
{
    public function __construct(
        private SemanticAnalyzer $semantics,
        private AnswerBuilder $answers,
        private ScheduleQueryService $schedule,
        private QuestionCatalog $catalog,
    ) {
    }

    public function ask(User $user, string $question, ?CarbonImmutable $now = null): array
    {
        $now ??= self::now();
        $tokens = [];
        $parse = null;

        try {
            $lexer = new QuestionLexer(Lexicon::fromDatabase());
            $tokens = $lexer->analyze($question);
            $parse = (new QuestionParser())->parse($tokens);
            $plan = $this->semantics->analyze($parse, $user, $now);
            $reply = $this->answers->answer($plan);

            return [
                'ok' => true,
                'stage' => 'answer',
                'intent' => $plan->intent,
                'answer' => $reply['answer'],
                'items' => $reply['items'],
                'show_formats' => $plan->intent === Grammar::HELP,
                'analysis' => $this->analysis($tokens, $parse, $plan),
            ];
        } catch (ChatbotException $e) {
            return [
                'ok' => false,
                'stage' => $e->stage,
                'intent' => $parse?->intent,
                'answer' => $e->getMessage(),
                'items' => [],
                'show_formats' => $e->stage !== ChatbotException::SEMANTIC,
                'analysis' => $this->analysis($e->tokens ?: $tokens, $parse, null),
            ];
        } catch (Throwable $e) {
            Log::error('Schedule assistant failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'stage' => 'error',
                'intent' => $parse?->intent,
                'answer' => 'Something went wrong while looking up the routine. Please try again.',
                'items' => [],
                'show_formats' => false,
                'analysis' => null,
            ];
        }
    }

    public function catalog(User $user): array
    {
        $role = strtolower((string) $user->role);

        return $this->catalog->formats($role, $this->examples($user, $role));
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('intellisched.chatbot.timezone', config('app.timezone')));
    }

    private function examples(User $user, string $role): array
    {
        $examples = ['course' => 'CSE 3201', 'teacher' => 'Rahman', 'day' => 'Monday'];

        try {
            $days = $this->schedule->workingDays();
            if ($days && !in_array('MONDAY', $days, true)) {
                $examples['day'] = ucfirst(strtolower($days[0]));
            }

            $selfId = null;
            if ($role === 'teacher') {
                $selfId = Teacher::where('user_id', $user->id)->value('id');
                $scope = $selfId ? ['teacher' => (int) $selfId] : null;
            } else {
                $scope = $user->section_id ? ['sections' => [(int) $user->section_id]] : null;
            }

            if ($scope) {
                $sessions = $this->schedule->sessions($scope);
                if ($first = $sessions->first()) {
                    $examples['course'] = $first['course_code'];
                }

                foreach ($sessions as $s) {
                    foreach ($s['teacher_ids'] as $i => $id) {
                        if ($id !== (int) $selfId && $s['teachers'][$i]) {
                            $examples['teacher'] = $s['teachers'][$i];
                            break 2;
                        }
                    }
                }
            }
        } catch (Throwable) {
            // Examples are cosmetic; keep the defaults.
        }

        return $examples;
    }

    private function analysis(array $tokens, ?ParseResult $parse, ?QueryPlan $plan): array
    {
        return [
            'tokens' => array_map(fn (Token $t) => $t->toArray(), $tokens),
            'rule' => $parse ? $parse->intent . ' → ' . $parse->rule : null,
            'plan' => $plan?->toArray(),
        ];
    }
}
