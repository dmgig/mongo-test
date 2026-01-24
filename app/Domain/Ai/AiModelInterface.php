<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use Gemini\Data\Schema;

interface AiModelInterface
{
    /**
     * Generates content based on the provided prompt.
     *
     * @param Prompt $prompt The system and user prompt.
     * @param Schema|null $responseSchema Optional JSON schema for structured output.
     * @return AiResponse The generated response.
     */
    public function generate(Prompt $prompt, ?Schema $responseSchema = null): AiResponse;

    /**
     * Generates an embedding for the given text.
     * 
     * @param string $text
     * @param string $model
     * @return array
     */
    public function embedContent(string $text, string $model = 'text-embedding-004'): array;
}
