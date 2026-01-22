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
        private readonly ?string $modelName = null
    ) {
    }

    public function generate(Prompt $prompt): string
    {
        $model = $this->modelName ?? $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?? 'gemini-2.5-pro';
        
        // Use the configured model
        $response = $this->client->generativeModel($model)->generateContent((string) $prompt);

        return $response->text();
    }
}
