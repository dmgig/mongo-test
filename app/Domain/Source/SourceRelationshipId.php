<?php

declare(strict_types=1);

namespace App\Domain\Source;

use Ramsey\Uuid\Uuid;

final class SourceRelationshipId
{
    private function __construct(public readonly string $value)
    {
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException("Invalid SourceRelationshipId: $id");
        }
        return new self($id);
    }

    public function equals(SourceRelationshipId $other): bool
    {
        return $this->value === $other->value;
    }
}
