<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Party\Party;
use App\Domain\Party\PartyId;
use App\Domain\Party\PartyRepositoryInterface;
use App\Domain\Party\PartyType;
use MongoDB\Database;

class MongoPartyRepository implements PartyRepositoryInterface
{
    private Database $db;

    public function __construct(MongoConnector $connector)
    {
        $this->db = $connector->database();
    }

    public function save(Party $party): void
    {
        $collection = $this->db->selectCollection('submissions');
        $collection->updateOne(
            ['_id' => $party->id->value],
            ['$set' => $party->toArray()],
            ['upsert' => true]
        );
    }

    public function delete(PartyId $id): void
    {
        $collection = $this->db->selectCollection('submissions');
        $collection->deleteOne(['_id' => $id->value]);
    }

    public function findById(PartyId $id): ?Party
    {
        $collection = $this->db->selectCollection('submissions');
        $data = $collection->findOne(['_id' => $id->value]);
        
        if (!$data) {
            return null;
        }

        /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
        $createdAtBson = $data['created_at'];
        $createdAt = $createdAtBson->toDateTime();
        
        return new Party(
            PartyId::fromString($data['_id']),
            $data['name'],
            PartyType::from($data['type']),
            \DateTimeImmutable::createFromMutable($createdAt)
        );
    }
}
