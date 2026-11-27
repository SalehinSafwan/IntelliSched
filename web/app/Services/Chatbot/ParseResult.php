<?php

namespace App\Services\Chatbot;

final class ParseResult
{
    /**
     * @param  Token[]  $tokens
     */
    public function __construct(
        public readonly string $intent,
        public readonly string $rule,
        public readonly array $tokens,
    ) {
    }

    /** @return Token[] */
    public function entities(string $type): array
    {
        return array_values(array_filter($this->tokens, fn (Token $t) => $t->type === $type));
    }

    public function first(string ...$types): ?Token
    {
        foreach ($this->tokens as $token) {
            if (in_array($token->type, $types, true)) {
                return $token;
            }
        }

        return null;
    }
}
