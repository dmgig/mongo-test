<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use App\Domain\Source\SourceId;

class Breakdown
{
    private function __construct(
        public readonly BreakdownId $id,
        public readonly SourceId $sourceId,
        public string $summary,
        public readonly \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public ?BreakdownResult $result = null
    ) {
    }

    public static function create(SourceId $sourceId): self
    {
        return new self(
            BreakdownId::generate(),
            $sourceId,
            '',
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }
    
    public static function reconstitute(
        BreakdownId $id,
        SourceId $sourceId,
        string $summary,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?BreakdownResult $result
    ): self {
        return new self(
            $id,
            $sourceId,
            $summary,
            $createdAt,
            $updatedAt,
            $result
        );
    }

    public function updateSummary(string $summary): void
    {
        $this->summary = $summary;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setResult(BreakdownResult $result): void
    {
        $this->result = $result;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
