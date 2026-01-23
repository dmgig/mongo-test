<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Breakdown\Breakdown;
use App\Domain\Breakdown\BreakdownId;
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
                'result' => $breakdown->result,
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

        return Breakdown::reconstitute(
            BreakdownId::fromString((string)$data['_id']),
            SourceId::fromString((string)$data['sourceId']),
            $data['summary'],
            \DateTimeImmutable::createFromMutable($data['createdAt']->toDateTime()),
            \DateTimeImmutable::createFromMutable($data['updatedAt']->toDateTime()),
            $data['result']
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
            $breakdowns[] = Breakdown::reconstitute(
                BreakdownId::fromString((string)$data['_id']),
                SourceId::fromString((string)$data['sourceId']),
                $data['summary'],
                \DateTimeImmutable::createFromMutable($data['createdAt']->toDateTime()),
                \DateTimeImmutable::createFromMutable($data['updatedAt']->toDateTime()),
                $data['result']
            );
        }
        return $breakdowns;
    }
}
