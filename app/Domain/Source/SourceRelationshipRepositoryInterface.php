<?php

declare(strict_types=1);

namespace App\Domain\Source;

interface SourceRelationshipRepositoryInterface
{
    public function save(SourceRelationship $relationship): void;
    
    public function findById(SourceRelationshipId $id): ?SourceRelationship;
    
    /**
     * @return SourceRelationship[]
     */
    public function findBySourceId(SourceId $sourceId): array;
    
    /**
     * @return SourceRelationship[]
     */
    public function findByTargetEntityId(string $entityId): array;

    public function delete(SourceRelationshipId $id): void;
    
    public function deleteBySourceId(SourceId $sourceId): void;
    
    public function deleteByTargetEntityId(string $entityId): void;
}
