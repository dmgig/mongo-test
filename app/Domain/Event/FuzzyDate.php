<?php

declare(strict_types=1);

namespace App\Domain\Event;

class FuzzyDate
{
    public function __construct(
        public readonly \DateTimeImmutable $dateTime,
        public readonly DatePrecision $precision,
        public readonly bool $isCirca = false,
        public readonly ?string $humanReadable = null
    ) {
    }
}
