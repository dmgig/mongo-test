<?php

declare(strict_types=1);

namespace App\Domain\Party;

final readonly class PartyId
{
    private function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('PartyId cannot be empty');
        }
    }

    public static function generate(): self
    {
        // Generate a UUID v4
        // For simplicity and no external dependencies, we use vsprintf/random_bytes
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

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

    public function equals(PartyId $other): bool
    {
        return $this->value === $other->value;
    }
}
