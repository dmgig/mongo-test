<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Party\PartyId;
use App\Domain\Party\PartyRelationship;
use App\Domain\Party\PartyRelationshipRepositoryInterface;
use App\Domain\Party\PartyRelationshipType;
use App\Domain\Party\PartyRelationshipStatus;
use App\Domain\Party\PartyRelationshipId;
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

    public function findByPartyId(PartyId $partyId): array
    {
        $collection = $this->db->selectCollection('relationships');
        $cursor = $collection->find([
            '$or' => [
                ['from_party_id' => $partyId->value],
                ['to_party_id' => $partyId->value]
            ]
        ]);

        $relationships = [];
        foreach ($cursor as $data) {
            /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
            $createdAtBson = $data['created_at'];
            /** @var \MongoDB\BSON\UTCDateTime $updatedAtBson */
            $updatedAtBson = $data['updated_at'];
            
            $relationships[] = new PartyRelationship(
                PartyRelationshipId::fromString((string)$data['_id']),
                PartyId::fromString($data['from_party_id']),
                PartyId::fromString($data['to_party_id']),
                PartyRelationshipType::from($data['type']),
                PartyRelationshipStatus::from($data['status']),
                \DateTimeImmutable::createFromMutable($createdAtBson->toDateTime()),
                \DateTimeImmutable::createFromMutable($updatedAtBson->toDateTime())
            );
        }
        
        return $relationships;
    }

    public function findById(PartyRelationshipId $id): ?PartyRelationship
    {
        $collection = $this->db->selectCollection('relationships');
        $data = $collection->findOne(['_id' => $id->value]);
        
        if (!$data) {
            return null;
        }

        /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
        $createdAtBson = $data['created_at'];
        /** @var \MongoDB\BSON\UTCDateTime $updatedAtBson */
        $updatedAtBson = $data['updated_at'];
        
        return new PartyRelationship(
            PartyRelationshipId::fromString((string)$data['_id']),
            PartyId::fromString($data['from_party_id']),
            PartyId::fromString($data['to_party_id']),
            PartyRelationshipType::from($data['type']),
            PartyRelationshipStatus::from($data['status']),
            \DateTimeImmutable::createFromMutable($createdAtBson->toDateTime()),
            \DateTimeImmutable::createFromMutable($updatedAtBson->toDateTime())
        );
    }
}
