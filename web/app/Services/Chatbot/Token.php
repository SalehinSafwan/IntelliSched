<?php

namespace App\Services\Chatbot;

final class Token
{
    public function __construct(
        public readonly string $type,
        public readonly string $lexeme,
        public readonly mixed $value = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'lexeme' => $this->lexeme,
            'value' => $this->value,
        ], fn ($v) => $v !== null);
    }
}
