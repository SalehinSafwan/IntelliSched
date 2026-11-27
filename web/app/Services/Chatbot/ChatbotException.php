<?php

namespace App\Services\Chatbot;

use RuntimeException;

class ChatbotException extends RuntimeException
{
    public const LEXICAL = 'lexical';
    public const SYNTAX = 'syntax';
    public const SEMANTIC = 'semantic';

    public function __construct(
        public readonly string $stage,
        string $message,
        public readonly array $tokens = [],
    ) {
        parent::__construct($message);
    }
}
