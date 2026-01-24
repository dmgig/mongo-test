<?php

declare(strict_types=1);

namespace App\Domain\Breakdown;

use App\Domain\Event\Event;
use App\Domain\Event\FuzzyDate;
use App\Domain\Event\DatePrecision;
use App\Domain\Party\Party;
use App\Domain\Party\PartyType;
use App\Domain\Party\PartyId;

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
    
    public function toArray(): array {
        return [
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
        // Handle input from JSON decoding which might not match our internal serialization exactly
        // We need to support both structured output format (from AI) and our internal format (from DB)
        
        $peopleData = $data['parties']['people'] ?? [];
        $orgsData = $data['parties']['organizations'] ?? [];
        
        // If data comes from our internal DB serialization, it might be flat under 'parties'
        // Let's check the structure.
        // If $data['parties'] is indexed array, it's likely from DB or flattened list.
        // If it has 'people' keys, it's from AI JSON.
        
        if (isset($data['parties']) && !isset($data['parties']['people']) && !isset($data['parties']['organizations'])) {
             // Likely DB rehydration
             foreach ($data['parties'] as $pData) {
                // MongoDB stores createdAt as BSON\UTCDateTime, so convert it back to DateTimeImmutable
                $createdAt = isset($pData["created_at"]) && $pData["created_at"] instanceof \MongoDB\BSON\UTCDateTime 
                    ? \DateTimeImmutable::createFromMutable($pData["created_at"]->toDateTime())
                    : new \DateTimeImmutable(); // Fallback

                $parties[] = Party::reconstitute(
                    PartyId::fromString($pData["_id"]),
                    $pData["name"],
                    PartyType::from($pData["type"]),
                    $createdAt,
                    $pData["aliases"] ?? null,
                    $pData["disambiguationDescription"] ?? null
                );
             }
        } else {
            // Likely AI JSON
            foreach ($peopleData as $p) {
                $parties[] = Party::create(
                    $p['name'],
                    PartyType::INDIVIDUAL,
                    $p['aliases'] ?? null,
                    $p['disambiguation_description'] ?? null
                );
            }
            foreach ($orgsData as $o) {
                $parties[] = Party::create(
                    $o['official_name'],
                    PartyType::ORGANIZATION,
                    $o['alternate_names'] ?? null,
                    $o['description'] ?? null
                );
            }
        }

        $locations = [];
        if (isset($data['locations']['locations'])) {
             // From AI JSON
             $locations = $data['locations']['locations'];
        } elseif (isset($data['locations'])) {
             // From DB or flat
             $locations = $data['locations'];
        }

        $timeline = [];
        $eventsData = $data['timeline']['events'] ?? $data['timeline'] ?? [];
        
        foreach ($eventsData as $eData) {
            $startDate = null;
            // Check for AI JSON format keys
            if (isset($eData['start_date'])) {
                 $startDate = new FuzzyDate(
                    new \DateTimeImmutable($eData['start_date']),
                    DatePrecision::from(strtolower($eData['start_precision'] ?? 'year')),
                    $eData['is_circa'] ?? false,
                    $eData['human_readable_date'] ?? null
                 );
            } 
            // Check for DB rehydration format keys
            elseif (isset($eData['startDate'])) {
                $sd = $eData['startDate'];
                $startDate = new FuzzyDate(
                    new \DateTimeImmutable($sd['dateTime']),
                    DatePrecision::from($sd['precision']),
                    $sd['isCirca'] ?? false,
                    $sd['humanReadable'] ?? null
                );
            }

            $endDate = null;
            if (isset($eData['end_date'])) {
                 $endDate = new FuzzyDate(
                    new \DateTimeImmutable($eData['end_date']),
                    DatePrecision::from(strtolower($eData['end_precision'] ?? 'year')),
                    $eData['is_circa'] ?? false,
                    $eData['human_readable_date'] ?? null
                 );
            } elseif (isset($eData['endDate'])) {
                $ed = $eData['endDate'];
                $endDate = new FuzzyDate(
                    new \DateTimeImmutable($ed['dateTime']),
                    DatePrecision::from($ed['precision']),
                    $ed['isCirca'] ?? false,
                    $ed['humanReadable'] ?? null
                );
            }

            $timeline[] = new Event(
                null,
                $eData['name'],
                $eData['description'],
                $startDate,
                $endDate
            );
        }

        return new self($parties, $locations, $timeline); 
    }
}
