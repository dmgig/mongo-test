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

    /**
     * Finds all relationships involving the given Party ID.
     * @return PartyRelationship[]
     */
    public function findByPartyId(PartyId $partyId): array;

    public function findById(PartyRelationshipId $id): ?PartyRelationship;
}
