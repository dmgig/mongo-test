<?php

declare(strict_types=1);

namespace App\Domain\Party;

use App\Domain\Source\SourceRelationshipRepositoryInterface;

class PartyService
{
    public function __construct(
        private readonly PartyRepositoryInterface $partyRepository,
        private readonly PartyRelationshipRepositoryInterface $relationshipRepository,
        private readonly SourceRelationshipRepositoryInterface $sourceRelationshipRepository
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

        // 2. Delete all source relationships associated with this party
        $this->sourceRelationshipRepository->deleteByTargetEntityId($partyId->value);

        // 3. Delete the party itself
        $this->partyRepository->delete($partyId);
    }

    /**
     * @return array{party: Party, relationships: array<PartyRelationship>, relatedParties: array<string, Party>}
     */
    public function getPartyWithRelationships(PartyId $partyId): array
    {
        $party = $this->partyRepository->findById($partyId);
        if (!$party) {
            throw new \Exception("Party with ID {$partyId} not found.");
        }

        $relationships = $this->relationshipRepository->findByPartyId($partyId);

        // Collect all related party IDs
        $relatedPartyIds = [];
        foreach ($relationships as $rel) {
            if (!$rel->fromPartyId->equals($partyId)) {
                $relatedPartyIds[] = $rel->fromPartyId;
            }
            if (!$rel->toPartyId->equals($partyId)) {
                $relatedPartyIds[] = $rel->toPartyId;
            }
        }

        // Fetch related parties
        $relatedPartiesList = $this->partyRepository->findByIds($relatedPartyIds);
        
        // Key by ID for easy lookup
        $relatedParties = [];
        foreach ($relatedPartiesList as $p) {
            $relatedParties[$p->id->value] = $p;
        }

        return [
            'party' => $party,
            'relationships' => $relationships,
            'relatedParties' => $relatedParties,
        ];
    }

    public function saveOrUpdateParty(Party $newParty): Party
    {
        // Attempt to find an existing party with the same name and type
        $existingParty = $this->partyRepository->findByNameAndType($newParty->name, $newParty->type);

        if ($existingParty) {
            // Update existing party with new information (e.g., aliases, disambiguationDescription)
            // For simplicity, we'll merge non-null new data into existing.
            if ($newParty->aliases !== null) {
                if ($existingParty->aliases === null) {
                    $existingParty->aliases = [];
                }
                $existingParty->aliases = array_unique(array_merge($existingParty->aliases, $newParty->aliases));
            }
            if ($newParty->disambiguationDescription !== null && $existingParty->disambiguationDescription === null) {
                $existingParty->disambiguationDescription = $newParty->disambiguationDescription;
            }
            $this->partyRepository->save($existingParty);
            return $existingParty;
        } else {
            // If no existing party, save the new one
            $this->partyRepository->save($newParty);
            return $newParty;
        }
    }
}
