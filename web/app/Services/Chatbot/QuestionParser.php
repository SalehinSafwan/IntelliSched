<?php

namespace App\Services\Chatbot;

/**
 * Stage 2 — syntax analysis.
 *
 * A small recursive-descent parser with backtracking. Each grammar
 * rule is tried in order; a rule matches only when it consumes the
 * whole token stream, so extra or missing words are rejected.
 */
class QuestionParser
{
    /** @var Token[] */
    private array $tokens = [];

    /**
     * @param  Token[]  $tokens
     */
    public function parse(array $tokens): ParseResult
    {
        // A greeting or an empty message: show what can be asked.
        if ($tokens === []) {
            return new ParseResult(Grammar::HELP, '(empty)', []);
        }

        $this->tokens = array_values($tokens);
        $end = count($this->tokens);

        foreach (Grammar::RULES as [$intent, $production]) {
            foreach ($this->matchSequence($this->symbols($production), 0) as $position) {
                if ($position === $end) {
                    return new ParseResult($intent, $production, $this->tokens);
                }
            }
        }

        throw new ChatbotException(ChatbotException::SYNTAX, $this->explain(), $this->tokens);
    }

    /**
     * Every position reachable after matching the symbol list from $pos.
     *
     * @return int[]
     */
    private function matchSequence(array $symbols, int $pos): array
    {
        if ($symbols === []) {
            return [$pos];
        }

        $symbol = array_shift($symbols);
        $ends = [];

        foreach ($this->matchSymbol($symbol, $pos) as $next) {
            foreach ($this->matchSequence($symbols, $next) as $end) {
                $ends[$end] = $end;
            }
        }

        return array_values($ends);
    }

    /**
     * Positions after one symbol with its ?, * modifier.
     *
     * @return int[]
     */
    private function matchSymbol(string $symbol, int $pos): array
    {
        $modifier = substr($symbol, -1);
        $name = in_array($modifier, ['?', '*'], true) ? substr($symbol, 0, -1) : $symbol;
        $alternatives = explode('|', $name);

        $once = fn (int $at) => array_merge(...array_map(
            fn (string $alt) => $this->matchAtom($alt, $at),
            $alternatives
        ));

        if ($modifier === '?') {
            return array_values(array_unique(array_merge($once($pos), [$pos])));
        }

        if ($modifier === '*') {
            $seen = [$pos => true];
            $frontier = [$pos];
            while ($frontier) {
                $nextFrontier = [];
                foreach ($frontier as $at) {
                    foreach ($once($at) as $next) {
                        if (!isset($seen[$next])) {
                            $seen[$next] = true;
                            $nextFrontier[] = $next;
                        }
                    }
                }
                $frontier = $nextFrontier;
            }

            return array_keys($seen);
        }

        return $once($pos);
    }

    /**
     * A terminal matches one token; a non-terminal expands to its
     * productions.
     *
     * @return int[]
     */
    private function matchAtom(string $atom, int $pos): array
    {
        if (isset(Grammar::NONTERMINALS[$atom])) {
            $ends = [];
            foreach (Grammar::NONTERMINALS[$atom] as $production) {
                foreach ($this->matchSequence($this->symbols($production), $pos) as $end) {
                    $ends[$end] = $end;
                }
            }

            return array_values($ends);
        }

        return isset($this->tokens[$pos]) && $this->tokens[$pos]->type === $atom
            ? [$pos + 1]
            : [];
    }

    private function symbols(string $production): array
    {
        return preg_split('/\s+/', trim($production));
    }

    /**
     * A syntax error that points the user at a supported form.
     */
    private function explain(): string
    {
        $types = array_map(fn (Token $t) => $t->type, $this->tokens);
        $count = fn (string $type) => count(array_keys($types, $type, true));

        if ($count(TokenType::COURSE) > 1) {
            return 'Please ask about one course at a time.';
        }

        if ($count(TokenType::TEACHER) > 1) {
            return 'Please ask about one teacher at a time.';
        }

        if ($count(TokenType::DATE) + $count(TokenType::DAY) > 1) {
            return 'Please mention only one day in the question.';
        }

        $course = $this->firstLexeme(TokenType::COURSE);
        if ($course !== null) {
            $code = strtoupper($course);
            return "I found the course \"{$code}\" but not what you want to know. Try: \"When is {$code} class?\", \"Who teaches {$code}?\" or \"Where is {$code} class?\"";
        }

        $teacher = $this->firstLexeme(TokenType::TEACHER);
        if ($teacher !== null) {
            $name = ucwords($teacher);
            return "I found the teacher \"{$name}\" but not the question. Try: \"When is {$name}'s class?\" or \"What does {$name} teach?\"";
        }

        return "I couldn't match that to a supported question. Open \"All question formats\" to see what I can answer.";
    }

    private function firstLexeme(string $type): ?string
    {
        foreach ($this->tokens as $token) {
            if ($token->type === $type) {
                return $token->lexeme;
            }
        }

        return null;
    }
}
