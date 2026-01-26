<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Source\SourceRelationshipRepositoryInterface;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly SourceRelationshipRepositoryInterface $sourceRelationshipRepository
    ) {
    }

    /**
     * @return Event[]
     */
    public function getAllEvents(): array
    {
        return $this->eventRepository->findAll();
    }

    public function deleteEvent(EventId $eventId): void
    {
        // 1. Delete associated source relationships
        $this->sourceRelationshipRepository->deleteByTargetEntityId($eventId->value);

        // 2. Delete the event itself
        // Note: EventRepositoryInterface needs a delete method.
        // Assuming it has one or will be added. If not, I'll need to check or add it.
        $this->eventRepository->delete($eventId);
    }
}
