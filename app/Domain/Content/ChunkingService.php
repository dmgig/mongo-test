<?php

declare(strict_types=1);

namespace App\Domain\Content;

class ChunkingService
{
    private const CHUNK_SIZE = 8000; // Characters

    public function chunk(string $text): array
    {
        $chunks = [];
        $currentChunk = '';

        // Split by paragraphs
        $paragraphs = preg_split('/(\r\n|\n|\r){2,}/', $text);

        foreach ($paragraphs as $paragraph) {
            if (strlen($paragraph) > self::CHUNK_SIZE) {
                // If the current chunk is not empty, add it to the list
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                }
                
                // Split the paragraph by sentences
                $sentences = preg_split('/(?<=[.?!])\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($sentences as $sentence) {
                    if (strlen($currentChunk) + strlen($sentence) > self::CHUNK_SIZE) {
                        if (!empty($currentChunk)) {
                            $chunks[] = $currentChunk;
                        }
                        $currentChunk = $sentence;
                    } else {
                        $currentChunk .= ' ' . $sentence;
                    }
                }

            } elseif (strlen($currentChunk) + strlen($paragraph) > self::CHUNK_SIZE) {
                // If the current chunk is not empty, add it to the list
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                }
                // Start a new chunk
                $currentChunk = $paragraph;
            } else {
                $currentChunk .= "\n\n" . $paragraph;
            }
        }

        // Add the last chunk
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }
}
