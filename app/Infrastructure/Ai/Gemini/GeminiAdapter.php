<?php

declare(strict_types=1);

namespace App\Infrastructure\Ai\Gemini;

use App\Domain\Ai\AiException;
use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\AiModels;
use App\Domain\Ai\AiResponse;
use App\Domain\Ai\Prompt;
use Gemini\Client;
use Gemini\Enums\Role;
use Gemini\Resources\Content;
use Gemini\Resources\Parts\TextPart;

class GeminiAdapter implements AiModelInterface
{
    private const MAX_RETRIES = 3;
    private const INITIAL_BACKOFF = 2; // seconds

    public function __construct(
        private readonly Client $client,
        private readonly ?string $modelName = null
    ) {
    }

    public function generate(Prompt $prompt): AiResponse
    {
        $model = $prompt->model ?? $this->modelName ?? $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?? AiModels::GEMINI_PRO_LATEST;
        $attempt = 0;
        $backoff = self::INITIAL_BACKOFF;

        while (true) {
            try {
                // Use the configured model
                $response = $this->client->generativeModel($model)->generateContent((string) $prompt);
                
                $inputTokens = 0;
                $outputTokens = 0;
                
                if ($response->usageMetadata) {
                     $inputTokens = $response->usageMetadata->promptTokenCount;
                     $outputTokens = $response->usageMetadata->candidatesTokenCount;
                }

                return new AiResponse($response->text(), $inputTokens, $outputTokens);
            } catch (\Exception $e) {
                $attempt++;
                
                // Check for rate limit errors (often code 429) or other retryable errors.
                // Google Gemini PHP client might wrap exceptions differently, but typically 429 is rate limit.
                // For robustness, we might retry on any exception if retry is enabled, or be more specific.
                // Let's assume we retry on any exception if requested, as temporary network blips can happen too.
                
                if ($prompt->retry && $attempt <= self::MAX_RETRIES) {
                    // Log or output warning? We are in an adapter, maybe just sleep.
                    // Ideally we'd use a PSR logger here. For now, just sleep.
                    sleep($backoff);
                    $backoff *= 2; // Exponential backoff
                    continue;
                }

                // If not retrying or max retries reached, wrap and throw.
                throw new AiException("AI Generation failed: " . $e->getMessage(), 0, $e);
            }
        }
    }
}
