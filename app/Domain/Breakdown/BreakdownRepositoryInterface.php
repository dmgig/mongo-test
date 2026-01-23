<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

interface BreakdownRepositoryInterface
{
    public function save(Breakdown $breakdown): void;
    public function find(BreakdownId $id): ?Breakdown;
}
