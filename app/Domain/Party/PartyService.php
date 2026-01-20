<?php

declare(strict_types=1);

namespace App\Domain\Party;

class PartyService
{
    public function __construct(
        private readonly PartyRepositoryInterface $partyRepository,
        private readonly PartyRelationshipRepositoryInterface $relationshipRepository
    ) {
    }

    /**
     * Deletes a party and cascades the deletion to any associated relationships.
     */
    public function deleteParty(PartyId $partyId): void
    {
        // Check if party exists
        $party = $this->partyRepository->findById($partyId);
        if (!$party) {
            throw new \Exception("Party with ID {$partyId} not found.");
        }

        // 1. Delete all relationships where this party is involved
        // This enforces the rule: "When we delete a party, we should delete its relationships."
        $this->relationshipRepository->deleteByPartyId($partyId);

        // 2. Delete the party itself
        $this->partyRepository->delete($partyId);
    }
}
