<?php

declare(strict_types=1);

namespace App\Domain\Event;

class Event
{
    public readonly EventId $id;

    public function __construct(
        ?EventId $id,
        public readonly string $name,
        public readonly string $description,
        public readonly ?FuzzyDate $startDate,
        public readonly ?FuzzyDate $endDate,
        public ?array $embedding = null
    ) {
        $this->id = $id ?? EventId::generate();
    }
}
