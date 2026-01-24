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
        $collection = $this->db->selectCollection("parties");

        $updateDocument = [];
        $updateDocument['$set'] = [
            "name" => $party->name,
            "type" => $party->type->value,
            "aliases" => $party->aliases ?? [],
            "disambiguationDescription" => $party->disambiguationDescription ?? "",
            "updated_at" => new \MongoDB\BSON\UTCDateTime((new \DateTimeImmutable())->getTimestamp() * 1000)
        ];
        
        $updateDocument['$setOnInsert'] = [
            "created_at" => new \MongoDB\BSON\UTCDateTime($party->createdAt->getTimestamp() * 1000)
        ];
        
        $collection->updateOne(
            ["_id" => $party->id->value],
            $updateDocument,
            ["upsert" => true]
        );
    }

    public function delete(PartyId $id): void
    {
        $collection = $this->db->selectCollection("parties");
        $collection->deleteOne(["_id" => $id->value]);
    }

    public function findById(PartyId $id): ?Party
    {
        $collection = $this->db->selectCollection("parties");
        $data = $collection->findOne(["_id" => $id->value]);
        
        if (!$data) {
            return null;
        }

        /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
        $createdAtBson = $data["created_at"];
        $createdAt = $createdAtBson->toDateTime();
        
        return Party::reconstitute(
            PartyId::fromString($data["_id"]),
            $data["name"],
            PartyType::from($data["type"]),
            \DateTimeImmutable::createFromMutable($createdAt),
            isset($data["aliases"]) ? (array)$data["aliases"] : null,
            $data["disambiguationDescription"] ?? null
        );
    }

    public function findByIds(array $ids): array
    {
        $collection = $this->db->selectCollection("parties");
        $stringIds = array_map(fn(PartyId $id) => $id->value, $ids);
        
        $cursor = $collection->find(["_id" => ["$in" => $stringIds]]);
        
        $parties = [];
        foreach ($cursor as $data) {
            /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
            $createdAtBson = $data["created_at"];
            $createdAt = $createdAtBson->toDateTime();
            
            $parties[] = Party::reconstitute(
                PartyId::fromString((string)$data["_id"]),
                $data["name"],
                PartyType::from($data["type"]),
                \DateTimeImmutable::createFromMutable($createdAt),
                isset($data["aliases"]) ? (array)$data["aliases"] : null,
                $data["disambiguationDescription"] ?? null
            );
        }
        
        return $parties;
    }

    public function findAll(): array
    {
        $collection = $this->db->selectCollection("parties");
        $cursor = $collection->find([], [
            "sort" => ["created_at" => -1]
        ]);
        
        $parties = [];
        foreach ($cursor as $data) {
            /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
            $createdAtBson = $data["created_at"];
            $createdAt = $createdAtBson->toDateTime();
            
            $parties[] = Party::reconstitute(
                PartyId::fromString((string)$data["_id"]),
                $data["name"],
                PartyType::from($data["type"]),
                \DateTimeImmutable::createFromMutable($createdAt),
                isset($data["aliases"]) ? (array)$data["aliases"] : null,
                $data["disambiguationDescription"] ?? null
            );
        }
        
        return $parties;
    }

    public function findByNameAndType(string $name, PartyType $type): ?Party
    {
        $collection = $this->db->selectCollection("parties");
        $data = $collection->findOne(["name" => $name, "type" => $type->value]);

        if (!$data) {
            return null;
        }
        
        /** @var \MongoDB\BSON\UTCDateTime $createdAtBson */
        $createdAtBson = $data["created_at"];
        $createdAt = $createdAtBson->toDateTime();

        return Party::reconstitute(
            PartyId::fromString($data["_id"]),
            $data["name"],
            PartyType::from($data["type"]),
            \DateTimeImmutable::createFromMutable($createdAt),
            isset($data["aliases"]) ? (array)$data["aliases"] : null,
            $data["disambiguationDescription"] ?? null
        );
    }
}
