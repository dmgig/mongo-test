<?php

declare(strict_types=1);

namespace App\Domain\Ai;

class Prompt
{
    // Define your hardcoded system prompts here as constants
    public const DEFAULT_SYSTEM_PROMPT = 'You are a helpful AI assistant.';
    public const ANALYSIS_PROMPT = 'Analyze the following data and provide insights.';

    public function __construct(
        public readonly string $systemPrompt,
        public readonly string $userInput
    ) {
    }

    public static function create(string $userInput, string $systemPrompt = self::DEFAULT_SYSTEM_PROMPT): self
    {
        return new self($systemPrompt, $userInput);
    }

    public function __toString(): string
    {
        // For simple models, we might just concatenate. 
        // Advanced models might keep them separate (System vs User role).
        // This method is a fallback string representation.
        return "System: {$this->systemPrompt}\n\nUser: {$this->userInput}";
    }
}
