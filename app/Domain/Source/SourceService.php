<?php

declare(strict_types=1);

namespace App\Domain\Source;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SourceService
{
    public function __construct(
        private readonly SourceRepositoryInterface $sourceRepository,
        private readonly SourceRelationshipRepositoryInterface $relationshipRepository
    ) {
    }

    public function createSource(string $url): Source
    {
        $client = new Client();
        $content = '';
        $statusCode = 0;

        try {
            $response = $client->request('GET', $url, [
                'timeout' => 10,
                'http_errors' => false, // Don't throw exception on 4xx/5xx, we want to capture it
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);
            
            $content = (string)$response->getBody();
            $statusCode = $response->getStatusCode();
            
        } catch (GuzzleException $e) {
            // In case of network error (DNS, timeout), status remains 0
            // We might want to store the error message in content
            $content = "Error accessing URL: " . $e->getMessage();
            $statusCode = 500; // or 0
        }

        $source = Source::create($url, $content, $statusCode);
        $this->sourceRepository->save($source);

        return $source;
    }

    public function getSource(SourceId $id): Source
    {
        $source = $this->sourceRepository->findById($id);
        if (!$source) {
            throw new \Exception("Source not found");
        }
        return $source;
    }

    public function deleteSource(SourceId $id): void
    {
        // 1. Delete all relationships for this source
        $this->relationshipRepository->deleteBySourceId($id);

        // 2. Delete the source itself
        $this->sourceRepository->delete($id);
    }

    public function linkToEntity(SourceId $sourceId, string $targetEntityId, string $targetEntityType, string $relationshipType): SourceRelationship
    {
        // Verify source exists
        $source = $this->sourceRepository->findById($sourceId);
        if (!$source) {
            throw new \Exception("Source with ID {$sourceId->value} not found.");
        }

        $relationship = SourceRelationship::create(
            $sourceId,
            $targetEntityId,
            $targetEntityType,
            $relationshipType
        );

        $this->relationshipRepository->save($relationship);
        return $relationship;
    }

    /**
     * @return SourceRelationship[]
     */
    public function getRelationships(SourceId $sourceId): array
    {
        return $this->relationshipRepository->findBySourceId($sourceId);
    }
}
