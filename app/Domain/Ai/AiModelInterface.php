<?php

declare(strict_types=1);

namespace App\Domain\Ai;

interface AiModelInterface
{
    /**
     * Generates content based on the provided prompt.
     *
     * @param Prompt $prompt The system and user prompt.
     * @return AiResponse The generated response.
     */
    public function generate(Prompt $prompt): AiResponse;
}
