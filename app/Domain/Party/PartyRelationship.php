<?php

declare(strict_types=1);

namespace App\Domain\Party;

final class PartyRelationship
{
    public function __construct(
        public readonly PartyRelationshipId $id,
        public readonly PartyId $fromPartyId,
        public readonly PartyId $toPartyId,
        public readonly PartyRelationshipType $type,
        public PartyRelationshipStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        PartyId $from,
        PartyId $to,
        PartyRelationshipType $type
    ): self {
        $now = new \DateTimeImmutable();
        return new self(
            PartyRelationshipId::generate(),
            $from,
            $to,
            $type,
            PartyRelationshipStatus::ACTIVE,
            $now,
            $now
        );
    }

    public function deactivate(): void
    {
        $this->status = PartyRelationshipStatus::INACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function activate(): void
    {
        $this->status = PartyRelationshipStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->id->value,
            'from_party_id' => $this->fromPartyId->value,
            'to_party_id' => $this->toPartyId->value,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'created_at' => new \MongoDB\BSON\UTCDateTime($this->createdAt),
            'updated_at' => new \MongoDB\BSON\UTCDateTime($this->updatedAt),
        ];
    }
}
