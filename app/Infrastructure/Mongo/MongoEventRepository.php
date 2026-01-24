<?php

declare(strict_types=1);

namespace App\Infrastructure\Mongo;

use App\Domain\Event\Event;
use App\Domain\Event\EventId;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\FuzzyDate;
use App\Domain\Event\DatePrecision;
use MongoDB\Collection;

class MongoEventRepository implements EventRepositoryInterface
{
    private readonly Collection $collection;

    public function __construct(
        private readonly MongoConnector $connector
    ) {
        $this->collection = $this->connector->database()->selectCollection('events');
    }

    public function save(Event $event): void
    {
        $this->collection->updateOne(
            ['_id' => (string)$event->id],
            ['$set' => [
                'name' => $event->name,
                'description' => $event->description,
                'startDate' => $event->startDate ? $this->fuzzyDateToArray($event->startDate) : null,
                'endDate' => $event->endDate ? $this->fuzzyDateToArray($event->endDate) : null,
            ]],
            ['upsert' => true]
        );
    }

    /**
     * @return Event[]
     */
    public function findAll(): array
    {
        $cursor = $this->collection->find([], ['sort' => ['startDate.dateTime' => 1]]);
        $events = [];
        foreach ($cursor as $data) {
            $events[] = $this->reconstituteEvent($data);
        }
        return $events;
    }

    private function fuzzyDateToArray(?FuzzyDate $fuzzyDate): ?array
    {
        if (!$fuzzyDate) {
            return null;
        }
        return [
            'dateTime' => new \MongoDB\BSON\UTCDateTime($fuzzyDate->dateTime->getTimestamp() * 1000),
            'precision' => $fuzzyDate->precision->value,
            'isCirca' => $fuzzyDate->isCirca,
            'humanReadable' => $fuzzyDate->humanReadable,
        ];
    }

    private function reconstituteEvent(\MongoDB\Model\BSONDocument $data): Event
    {
        $startDate = null;
        if (isset($data['startDate'])) {
            $sd = $data['startDate'];
            $startDate = new FuzzyDate(
                \DateTimeImmutable::createFromMutable($sd['dateTime']->toDateTime()),
                DatePrecision::from($sd['precision']),
                $sd['isCirca'] ?? false,
                $sd['humanReadable'] ?? null
            );
        }

        $endDate = null;
        if (isset($data['endDate'])) {
            $ed = $data['endDate'];
            $endDate = new FuzzyDate(
                \DateTimeImmutable::createFromMutable($ed['dateTime']->toDateTime()),
                DatePrecision::from($ed['precision']),
                $ed['isCirca'] ?? false,
                $ed['humanReadable'] ?? null
            );
        }

        return new Event(
            EventId::fromString((string)$data['_id']),
            $data['name'],
            $data['description'],
            $startDate,
            $endDate
        );
    }
}
