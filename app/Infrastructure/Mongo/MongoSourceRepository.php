<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Source\Source;
use App\Domain\Source\SourceId;
use App\Domain\Source\SourceRepositoryInterface;
use MongoDB\Database;

class MongoSourceRepository implements SourceRepositoryInterface
{
    private Database $db;

    public function __construct(MongoConnector $connector)
    {
        $this->db = $connector->database();
    }

    public function save(Source $source): void
    {
        $collection = $this->db->selectCollection('sources');
        $collection->updateOne(
            ['_id' => $source->id->value],
            ['$set' => $source->toArray()],
            ['upsert' => true]
        );
    }

    public function findById(SourceId $id): ?Source
    {
        $collection = $this->db->selectCollection('sources');
        $data = $collection->findOne(['_id' => $id->value]);
        
        if (!$data) {
            return null;
        }

        /** @var \MongoDB\BSON\UTCDateTime $accessedAtBson */
        $accessedAtBson = $data['accessed_at'];
        
        return new Source(
            SourceId::fromString($data['_id']),
            $data['url'],
            $data['content'],
            $data['http_code'],
            \DateTimeImmutable::createFromMutable($accessedAtBson->toDateTime())
        );
    }

    public function delete(SourceId $id): void
    {
        $collection = $this->db->selectCollection('sources');
        $collection->deleteOne(['_id' => $id->value]);
    }

    public function findAll(): array
    {
        $collection = $this->db->selectCollection('sources');
        $cursor = $collection->find([], [
            'sort' => ['accessed_at' => -1]
        ]);
        
        $sources = [];
        foreach ($cursor as $data) {
            /** @var \MongoDB\BSON\UTCDateTime $accessedAtBson */
            $accessedAtBson = $data['accessed_at'];
            
            $sources[] = new Source(
                SourceId::fromString($data['_id']),
                $data['url'],
                $data['content'],
                $data['http_code'],
                \DateTimeImmutable::createFromMutable($accessedAtBson->toDateTime())
            );
        }
        
        return $sources;
    }
}
