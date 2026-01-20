<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Party\PartyId;
use App\Domain\Party\PartyRelationship;
use App\Domain\Party\PartyRelationshipRepositoryInterface;
use MongoDB\Database;

class MongoPartyRelationshipRepository implements PartyRelationshipRepositoryInterface
{
    private Database $db;

    public function __construct(MongoConnector $connector)
    {
        $this->db = $connector->database();
    }

    public function save(PartyRelationship $relationship): void
    {
        $collection = $this->db->selectCollection('relationships');
        $collection->updateOne(
            ['_id' => $relationship->id->value],
            ['$set' => $relationship->toArray()],
            ['upsert' => true]
        );
    }

    public function deleteByPartyId(PartyId $partyId): void
    {
        $collection = $this->db->selectCollection('relationships');
        $collection->deleteMany([
            '$or' => [
                ['from_party_id' => $partyId->value],
                ['to_party_id' => $partyId->value]
            ]
        ]);
    }
}
