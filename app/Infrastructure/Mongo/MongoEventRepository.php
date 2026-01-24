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
                'embedding' => $event->embedding,
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

    public function findSimilar(array $embedding, float $threshold = 0.8): ?Event
    {
        // This is a naive implementation. A real vector search would be done in the database.
        // For now, we'll iterate and compare.
        $allEvents = $this->findAll();
        foreach ($allEvents as $event) {
            if ($event->embedding) {
                $similarity = $this->cosineSimilarity($embedding, $event->embedding);
                if ($similarity >= $threshold) {
                    return $event;
                }
            }
        }
        return null;
    }

    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        
        $count = count($vec1);
        if ($count !== count($vec2)) {
            return 0.0;
        }

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $normA += $vec1[$i] * $vec1[$i];
            $normB += $vec2[$i] * $vec2[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
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
            $endDate,
            $data['embedding'] ? (array)$data['embedding'] : null
        );
    }
}
