<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Breakdown\Breakdown;
use App\Domain\Breakdown\BreakdownId;
use App\Domain\Breakdown\BreakdownResult;
use App\Domain\Breakdown\BreakdownRepositoryInterface;
use App\Domain\Source\SourceId;
use MongoDB\Collection;

class MongoBreakdownRepository implements BreakdownRepositoryInterface
{
    private readonly Collection $collection;

    public function __construct(
        private readonly MongoConnector $connector
    ) {
        $this->collection = $this->connector->database()->selectCollection('breakdowns');
    }

    public function save(Breakdown $breakdown): void
    {
        $this->collection->updateOne(
            ['_id' => (string)$breakdown->id],
            ['$set' => [
                'sourceId' => (string)$breakdown->sourceId,
                'summary' => $breakdown->summary,
                'result' => $breakdown->result ? $breakdown->result->toArray() : null,
                'createdAt' => new \MongoDB\BSON\UTCDateTime($breakdown->createdAt->getTimestamp() * 1000),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime($breakdown->updatedAt->getTimestamp() * 1000),
            ]],
            ['upsert' => true]
        );
    }

    public function find(BreakdownId $id): ?Breakdown
    {
        $data = $this->collection->findOne(['_id' => (string)$id]);

        if (!$data) {
            return null;
        }

        $result = null;
        if (isset($data['result']) && is_array($data['result']) || is_object($data['result'])) {
             // Handle BSONDocument or Array. MongoDB driver returns BSONDocument which acts like an array but is an object.
             // We can cast to array to be safe if it's a BSONDocument
             $resultData = (array)$data['result'];
             // BreakdownResult::fromArray is not fully implemented yet, but we need something here.
             // Since we're serializing via toArray(), we should be able to just pass the array if we implement fromArray properly.
             // But wait, BreakdownResult::fromYaml is the main way we construct it currently.
             // Let's implement a basic reconstruction.
             
             // Actually, for now, to support reading the existing string-based results (if any), we might need checks.
             // But we are changing the schema. Old string results won't match.
             // Let's assume we are starting fresh or migrating. The user asked for this change.
             // Wait, I missed implementing BreakdownResult::fromArray properly.
             // I'll do a quick implementation here by reconstructing manually or I should update BreakdownResult first.
             // Let's update this file to use BreakdownResult::fromArray and I will make sure BreakdownResult has it.
             
             // Check if it's the old string format
             if (is_string($data['result'])) {
                 // It's the old format. We can't easily convert it to the new structured format without re-parsing.
                 // For now, let's set it to null or handle it gracefully?
                 // Let's set it to null to avoid errors, or maybe we can't support old records.
                 $result = null; 
             } else {
                 $result = BreakdownResult::fromArray($resultData);
             }
        }

        return Breakdown::reconstitute(
            BreakdownId::fromString((string)$data['_id']),
            SourceId::fromString((string)$data['sourceId']),
            $data['summary'],
            \DateTimeImmutable::createFromMutable($data['createdAt']->toDateTime()),
            \DateTimeImmutable::createFromMutable($data['updatedAt']->toDateTime()),
            $result
        );
    }

    public function findBySource(SourceId $sourceId): array
    {
        $cursor = $this->collection->find(
            ['sourceId' => (string)$sourceId],
            ['sort' => ['createdAt' => -1]]
        );
        $breakdowns = [];
        foreach ($cursor as $data) {
            $result = null;
            if (isset($data['result'])) {
                 if (is_string($data['result'])) {
                     $result = null;
                 } else {
                     $result = BreakdownResult::fromArray((array)$data['result']);
                 }
            }

            $breakdowns[] = Breakdown::reconstitute(
                BreakdownId::fromString((string)$data['_id']),
                SourceId::fromString((string)$data['sourceId']),
                $data['summary'],
                \DateTimeImmutable::createFromMutable($data['createdAt']->toDateTime()),
                \DateTimeImmutable::createFromMutable($data['updatedAt']->toDateTime()),
                $result
            );
        }
        return $breakdowns;
    }
}
