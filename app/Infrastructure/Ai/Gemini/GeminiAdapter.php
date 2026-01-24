<?php

declare(strict_types=1);

namespace App\Infrastructure\Ai\Gemini;

use App\Domain\Ai\AiException;
use App\Domain\Ai\AiModelInterface;
use App\Domain\Ai\AiModels;
use App\Domain\Ai\AiResponse;
use App\Domain\Ai\Prompt;
use Gemini\Client;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\ResponseMimeType;
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

    public function generate(Prompt $prompt, ?Schema $responseSchema = null): AiResponse
    {
        $model = $prompt->model ?? $this->modelName ?? $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?? AiModels::GEMINI_PRO_LATEST;
        $attempt = 0;
        $backoff = self::INITIAL_BACKOFF;

        while (true) {
            try {
                $generativeModel = $this->client->generativeModel($model);

                if ($responseSchema) {
                    $generativeModel = $generativeModel->withGenerationConfig(
                        new GenerationConfig(
                            responseMimeType: ResponseMimeType::APPLICATION_JSON,
                            responseSchema: $responseSchema
                        )
                    );
                }

                $response = $generativeModel->generateContent((string) $prompt);
                
                $inputTokens = 0;
                $outputTokens = 0;
                
                if ($response->usageMetadata) {
                     $inputTokens = $response->usageMetadata->promptTokenCount;
                     $outputTokens = $response->usageMetadata->candidatesTokenCount;
                }

                return new AiResponse($response->text(), $inputTokens, $outputTokens);
            } catch (\Exception $e) {
                $attempt++;
                
                if ($prompt->retry && $attempt <= self::MAX_RETRIES) {
                    sleep($backoff);
                    $backoff *= 2; // Exponential backoff
                    continue;
                }

                throw new AiException("AI Generation failed: " . $e->getMessage(), 0, $e);
            }
        }
    }

    public function embedContent(string $text, string $model = 'text-embedding-004'): array
    {
        try {
            $response = $this->client
                ->embeddingModel($model)
                ->embedContent($text);

            return $response->embedding->values;
        } catch (\Exception $e) {
            throw new AiException("Embedding failed: " . $e->getMessage(), 0, $e);
        }
    }
}
