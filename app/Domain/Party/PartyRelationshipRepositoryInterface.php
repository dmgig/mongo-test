<?php

declare(strict_types=1);

namespace App\Domain\Party;

interface PartyRelationshipRepositoryInterface
{
    public function save(PartyRelationship $relationship): void;
    
    /**
     * Deletes all relationships involving the given Party ID (either as from or to).
     */
    public function deleteByPartyId(PartyId $partyId): void;
}
