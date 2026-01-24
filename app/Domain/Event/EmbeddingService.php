<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Ai\AiModelInterface;

class EmbeddingService
{
    public function __construct(
        private readonly AiModelInterface $ai
    ) {
    }

    public function generateEmbedding(string $text): array
    {
        return $this->ai->embedContent($text);
    }

    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        
        $count = count($vec1);
        if ($count !== count($vec2)) {
            return 0.0; // Or throw an exception
        }

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $normA += $vec1[$i] * $vec1[$i];
            $normB += $vec2[$i] * $vec2[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
