<?php

declare(strict_types=1);

namespace App\Domain\Party;

final readonly class PartyRelationshipId
{
    private function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('PartyRelationshipId cannot be empty');
        }
    }

    public static function generate(): self
    {
        // UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(PartyRelationshipId $other): bool
    {
        return $this->value === $other->value;
    }
}
