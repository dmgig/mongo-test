<?php

declare(strict_types=1);

namespace App\Domain\Event;

interface EventRepositoryInterface
{
    public function save(Event $event): void;

    /**
     * @return Event[]
     */
    public function findAll(): array;

    public function delete(EventId $id): void;
}
