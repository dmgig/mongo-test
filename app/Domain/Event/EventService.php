<?php

declare(strict_types=1);

namespace App\Domain\Event;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    /**
     * @return Event[]
     */
    public function getAllEvents(): array
    {
        return $this->eventRepository->findAll();
    }
}
