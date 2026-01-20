<?php

declare(strict_types=1);

namespace App\Domain\Party;

interface PartyRepositoryInterface
{
    public function save(Party $party): void;
    public function delete(PartyId $id): void;
    public function findById(PartyId $id): ?Party;
}
