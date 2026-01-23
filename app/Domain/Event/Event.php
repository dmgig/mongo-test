<?php

declare(strict_types=1);

namespace App\Domain\Event;

class Event
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly ?FuzzyDate $startDate,
        public readonly ?FuzzyDate $endDate
    ) {
    }
}
