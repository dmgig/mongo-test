<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use App\Domain\Source\SourceId;

class BreakdownService
{
    public function __construct(
        private readonly BreakdownRepositoryInterface $breakdownRepo
    ) {
    }

    /**
     * @return Breakdown[]
     */
    public function getBreakdownsForSource(SourceId $sourceId): array
    {
        return $this->breakdownRepo->findBySource($sourceId);
    }
}
