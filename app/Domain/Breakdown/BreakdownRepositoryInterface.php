<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use App\Domain\Source\SourceId;

interface BreakdownRepositoryInterface
{
    public function save(Breakdown $breakdown): void;
    public function find(BreakdownId $id): ?Breakdown;
    /**
     * @return Breakdown[]
     */
    public function findBySource(SourceId $sourceId): array;
}
