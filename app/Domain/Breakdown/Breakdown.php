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
        public ?BreakdownResult $result = null,
        public array $chunkSummaries = [],
        public ?string $partiesYaml = null,
        public ?string $locationsYaml = null,
        public ?string $timelineYaml = null
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
            // chunkSummaries defaults to [], other YAMLs default to null
        );
    }
    
    public static function reconstitute(
        BreakdownId $id,
        SourceId $sourceId,
        string $summary,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?BreakdownResult $result,
        array $chunkSummaries = [],
        ?string $partiesYaml = null,
        ?string $locationsYaml = null,
        ?string $timelineYaml = null
    ): self {
        return new self(
            $id,
            $sourceId,
            $summary,
            $createdAt,
            $updatedAt,
            $result,
            $chunkSummaries,
            $partiesYaml,
            $locationsYaml,
            $timelineYaml
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

    public function addChunkSummary(string $summary): void
    {
        $this->chunkSummaries[] = $summary;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateMasterSummary(string $masterSummary): void
    {
        $this->summary = $masterSummary;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setPartiesYaml(?string $partiesYaml): void
    {
        $this->partiesYaml = $partiesYaml;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setLocationsYaml(?string $locationsYaml): void
    {
        $this->locationsYaml = $locationsYaml;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setTimelineYaml(?string $timelineYaml): void
    {
        $this->timelineYaml = $timelineYaml;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
