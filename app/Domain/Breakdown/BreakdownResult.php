<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use App\Domain\Event\Event;
use App\Domain\Event\FuzzyDate;
use App\Domain\Event\DatePrecision;
use App\Domain\Party\Party;
use App\Domain\Party\PartyType;
use App\Domain\Party\PartyId;
use Symfony\Component\Yaml\Yaml;

class BreakdownResult
{
    /**
     * @param Party[] $parties
     * @param array $locations
     * @param Event[] $timeline
     */
    public function __construct(
        public readonly array $parties,
        public readonly array $locations,
        public readonly array $timeline
    ) {
    }

    public static function fromYaml(string $partiesYaml, string $locationsYaml, string $timelineYaml): self
    {
        $partiesData = Yaml::parse($partiesYaml);
        $locationsData = Yaml::parse($locationsYaml);
        $timelineData = Yaml::parse($timelineYaml);

        $parties = [];
        if (isset($partiesData["people"])) {
            foreach ($partiesData["people"] as $p) {
                // For now, we\"ll just create a Party object. Ideally, we\"d have a more robust way to handle descriptions.
                $parties[] = Party::create(
                    $p["name"],
                    PartyType::INDIVIDUAL,
                    $p["aliases"] ?? null,
                    $p["disambiguation_description"] ?? null
                );
            }
        }
        if (isset($partiesData["organizations"])) {
            foreach ($partiesData["organizations"] as $o) {
                $parties[] = Party::create(
                    $o["official_name"],
                    PartyType::ORGANIZATION,
                    $o["alternate_names"] ?? null,
                    $o["description"] ?? null
                );
            }
        }

        $locations = $locationsData["locations"] ?? [];

        $timeline = [];
        if (isset($timelineData["events"])) {
            foreach ($timelineData["events"] as $e) {
                $startDate = null;
                if (isset($e["start_date"])) {
                    $startDate = new FuzzyDate(
                        new \DateTimeImmutable($e["start_date"]),
                        DatePrecision::from(strtolower($e["start_precision"] ?? "year")),
                        $e["is_circa"] ?? false,
                        $e["human_readable_date"] ?? null
                    );
                }

                $endDate = null;
                if (isset($e["end_date"])) {
                    $endDate = new FuzzyDate(
                        new \DateTimeImmutable($e["end_date"]),
                        DatePrecision::from(strtolower($e["end_precision"] ?? "year")),
                        $e["is_circa"] ?? false,
                        null 
                    );
                }

                $timeline[] = new Event(
                    $e["name"],
                    $e["description"],
                    $startDate,
                    $endDate
                );
            }
        }

        return new self($parties, $locations, $timeline);
    }
    
    public function toArray(): array {
        return [
            // NOTE: Using `name` and `disambiguationDescription` as the primary description fields for display
            // This avoids passing the entire Party object to the view and simplifies display logic.
            // If a full object rehydration is needed, the `fromArray` method will handle it.
            'parties' => array_map(fn(Party $p) => $p->toArray(), $this->parties),
            'locations' => $this->locations,
            'timeline' => array_map(fn(Event $e) => [ // We need a toArray on Event/FuzzyDate ideally, but doing inline for now
                'name' => $e->name,
                'description' => $e->description,
                'startDate' => $e->startDate ? [
                    'dateTime' => $e->startDate->dateTime->format(\DateTimeInterface::ATOM),
                    'precision' => $e->startDate->precision->value,
                    'isCirca' => $e->startDate->isCirca,
                    'humanReadable' => $e->startDate->humanReadable
                ] : null,
                'endDate' => $e->endDate ? [
                    'dateTime' => $e->endDate->dateTime->format(\DateTimeInterface::ATOM),
                    'precision' => $e->endDate->precision->value,
                    'isCirca' => $e->endDate->isCirca,
                    'humanReadable' => $e->endDate->humanReadable
                ] : null,
            ], $this->timeline)
        ];
    }
    
    public static function fromArray(array $data): self {
        $parties = [];
        foreach (($data["parties"] ?? []) as $pData) {
            // MongoDB stores createdAt as BSON\UTCDateTime, so convert it back to DateTimeImmutable
            $createdAt = \DateTimeImmutable::createFromMutable($pData["created_at"]->toDateTime());

            $parties[] = Party::reconstitute(
                PartyId::fromString($pData["_id"]),
                $pData["name"],
                PartyType::from($pData["type"]),
                $createdAt,
                $pData["aliases"] ?? null, // New: Pass aliases
                $pData["disambiguationDescription"] ?? null // New: Pass disambiguationDescription
            );
        }

        $locations = (array)($data["locations"] ?? []);

        $timeline = [];
        foreach (($data["timeline"] ?? []) as $eData) {
            $startDate = null;
            if (isset($eData["startDate"])) {
                $sd = $eData["startDate"];
                $startDate = new FuzzyDate(
                    new \DateTimeImmutable($sd["dateTime"]),
                    DatePrecision::from($sd["precision"]),
                    $sd["isCirca"] ?? false,
                    $sd["humanReadable"] ?? null
                );
            }

            $endDate = null;
            if (isset($eData["endDate"])) {
                $ed = $eData["endDate"];
                $endDate = new FuzzyDate(
                    new \DateTimeImmutable($ed["dateTime"]),
                    DatePrecision::from($ed["precision"]),
                    $ed["isCirca"] ?? false,
                    $ed["humanReadable"] ?? null
                );
            };

            $timeline[] = new Event(
                $eData["name"],
                $eData["description"],
                $startDate,
                $endDate
            );
        }

        return new self($parties, $locations, $timeline); 
    }
}
