<?php

declare(strict_types=1);

namespace App\Domain\Source;

final class SourceRelationship
{
    public function __construct(
        public readonly SourceRelationshipId $id,
        public readonly SourceId $sourceId,
        public readonly string $targetEntityId,
        public readonly string $targetEntityType,
        public readonly string $type,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        SourceId $sourceId,
        string $targetEntityId,
        string $targetEntityType,
        string $type
    ): self {
        $now = new \DateTimeImmutable();
        return new self(
            SourceRelationshipId::generate(),
            $sourceId,
            $targetEntityId,
            $targetEntityType,
            $type,
            $now,
            $now
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->id->value,
            'source_id' => $this->sourceId->value,
            'target_entity_id' => $this->targetEntityId,
            'target_entity_type' => $this->targetEntityType,
            'type' => $this->type,
            'created_at' => new \MongoDB\BSON\UTCDateTime($this->createdAt),
            'updated_at' => new \MongoDB\BSON\UTCDateTime($this->updatedAt),
        ];
    }
}
