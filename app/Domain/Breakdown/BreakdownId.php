<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use Ramsey\Uuid\Uuid;

final class BreakdownId
{
    private function __construct(
        private readonly string $uuid
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $uuid): self
    {
        if (!Uuid::isValid($uuid)) {
            throw new \InvalidArgumentException("Invalid UUID: $uuid");
        }
        return new self($uuid);
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
