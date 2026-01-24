<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Ramsey\Uuid\Uuid;

final class EventId
{
    private function __construct(
        public readonly string $value
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException("Invalid Event ID: {$id}");
        }
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
