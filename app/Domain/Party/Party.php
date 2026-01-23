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
        public ?array $aliases = null, // New: for individuals
        public ?string $disambiguationDescription = null // New: for individuals and organizations
    ) {
    }

    public static function create(string $name, PartyType $type): self
    {
        return new self(
            PartyId::generate(),
            $name,
            $type,
            new \DateTimeImmutable(),
            null, // aliases default
            null  // disambiguationDescription default
        );
    }

    public static function reconstitute(
        PartyId $id,
        string $name,
        PartyType $type,
        \DateTimeImmutable $createdAt,
        ?array $aliases,
        ?string $disambiguationDescription
    ): self {
        return new self(
            $id,
            $name,
            $type,
            $createdAt,
            $aliases,
            $disambiguationDescription
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
            'created_at' => new \MongoDB\BSON\UTCDateTime($this->createdAt),
            'aliases' => $this->aliases,
            'disambiguationDescription' => $this->disambiguationDescription,
        ];
    }
}
