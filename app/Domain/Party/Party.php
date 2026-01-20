<?php

declare(strict_types=1);

namespace App\Domain\Party;

final class Party
{
    public function __construct(
        public readonly PartyId $id,
        public string $name,
        public readonly PartyType $type,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(string $name, PartyType $type): self
    {
        return new self(
            PartyId::generate(),
            $name,
            $type,
            new \DateTimeImmutable()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->id->value,
            'name' => $this->name,
            'type' => $this->type->value,
            // We use BSON\UTCDateTime here for direct MongoDB compatibility.
            // In a stricter architecture, this conversion might happen in a Repository.
            'created_at' => new \MongoDB\BSON\UTCDateTime($this->createdAt),
        ];
    }
}
