<?php

declare(strict_types=1);

namespace App\Domain\Ai;

class AiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0
    ) {
    }
}
