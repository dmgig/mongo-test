<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Source\SourceId;
use App\Domain\Source\SourceRelationship;
use App\Domain\Source\SourceRelationshipId;
use App\Domain\Source\SourceRelationshipRepositoryInterface;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

final class MongoSourceRelationshipRepository implements SourceRelationshipRepositoryInterface
{
    private Collection $collection;

    public function __construct(MongoConnector $connector)
    {
        $this->collection = $connector->database()->selectCollection('source_relationships');
    }

    public function save(SourceRelationship $relationship): void
    {
        $data = $relationship->toArray();
        $this->collection->updateOne(
            ['_id' => $relationship->id->value],
            ['$set' => $data],
            ['upsert' => true]
        );
    }

    public function findById(SourceRelationshipId $id): ?SourceRelationship
    {
        $data = $this->collection->findOne(['_id' => $id->value]);
        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findBySourceId(SourceId $sourceId): array
    {
        $cursor = $this->collection->find(['source_id' => $sourceId->value]);
        $relationships = [];
        foreach ($cursor as $data) {
            $relationships[] = $this->mapToEntity($data);
        }
        return $relationships;
    }

    public function findByTargetEntityId(string $entityId): array
    {
        $cursor = $this->collection->find(['target_entity_id' => $entityId]);
        $relationships = [];
        foreach ($cursor as $data) {
            $relationships[] = $this->mapToEntity($data);
        }
        return $relationships;
    }

    public function delete(SourceRelationshipId $id): void
    {
        $this->collection->deleteOne(['_id' => $id->value]);
    }

    public function deleteBySourceId(SourceId $sourceId): void
    {
        $this->collection->deleteMany(['source_id' => $sourceId->value]);
    }

    public function deleteByTargetEntityId(string $entityId): void
    {
        $this->collection->deleteMany(['target_entity_id' => $entityId]);
    }

    private function mapToEntity(array|BSONDocument $data): SourceRelationship
    {
        // Handle BSONDocument or array
        if ($data instanceof BSONDocument) {
            $data = $data->getArrayCopy();
        }

        return new SourceRelationship(
            SourceRelationshipId::fromString($data['_id']),
            SourceId::fromString($data['source_id']),
            $data['target_entity_id'],
            $data['target_entity_type'],
            $data['type'],
            $data['created_at']->toDateTime()->setTimezone(new \DateTimeZone('UTC')),
            $data['updated_at']->toDateTime()->setTimezone(new \DateTimeZone('UTC'))
        );
    }
}
