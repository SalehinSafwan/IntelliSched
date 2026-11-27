<?php

namespace App\Services\Chatbot;


class QuestionLexer
{
    public function __construct(private Lexicon $lexicon)
    {
    }

    /**
     * @return Token[]
     */
    public function tokenize(string $question): array
    {
        $normalized = Lexicon::normalize($question);
        $words = $normalized === '' ? [] : explode(' ', $normalized);
        $tokens = [];
        $i = 0;
        $count = count($words);

        while ($i < $count) {
            $matched = false;

            for ($length = min($this->lexicon->longestPhrase(), $count - $i); $length >= 1; $length--) {
                $phrase = implode(' ', array_slice($words, $i, $length));
                $entry = $this->lexicon->lookup($phrase);

                if ($entry === null) {
                    continue;
                }

                [$type, $value] = $entry;
                if ($type !== Lexicon::FILLER) {
                    $tokens[] = new Token($type, $phrase, $value);
                }

                $i += $length;
                $matched = true;
                break;
            }

            if (!$matched) {
                $tokens[] = new Token(TokenType::UNKNOWN, $words[$i]);
                $i++;
            }
        }

        return $tokens;
    }

    /**
     * Tokens, or a lexical error naming the words that were not understood.
     *
     * @return Token[]
     */
    public function analyze(string $question): array
    {
        $tokens = $this->tokenize($question);

        $unknown = array_values(array_map(
            fn (Token $t) => $t->lexeme,
            array_filter($tokens, fn (Token $t) => $t->type === TokenType::UNKNOWN)
        ));

        if ($unknown) {
            $words = implode('", "', array_unique($unknown));
            throw new ChatbotException(
                ChatbotException::LEXICAL,
                "I don't recognise \"{$words}\". Check the course code or teacher name, or pick one of the sample questions.",
                $tokens
            );
        }

        return $tokens;
    }
}
