<?php

declare(strict_types=1);

namespace App\Infrastructure\Ai\Gemini;

use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\Prompt;
use Gemini\Client;
use Gemini\Enums\Role;
use Gemini\Resources\Content;
use Gemini\Resources\Parts\TextPart;

class GeminiAdapter implements AiModelInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly string $modelName = 'gemini-pro'
    ) {
    }

    public function generate(Prompt $prompt): string
    {
        // Construct the prompt with system instruction if possible, or concatenated
        // Gemini Pro supports system instructions in newer versions, but simple concatenation 
        // is robust for basic use cases.
        // We will send the system prompt as the first message from 'user' or just combine them.
        
        // Strategy: Combine System + User input into a single prompt for simplicity/compatibility
        // or leverage the chat history structure. Let's start with a direct generateContent call.
        
        $fullPrompt = (string) $prompt;

        $response = $this->client->geminiPro()->generateContent($fullPrompt);

        return $response->text();
    }
}
