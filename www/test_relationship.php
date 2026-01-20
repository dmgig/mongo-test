<?php
require_once dirname(__DIR__) . '/settings.php';

use App\Domain\Party\PartyId;
use App\Domain\Party\PartyRelationship;
use App\Domain\Party\PartyRelationshipType;

header('Content-Type: text/plain');

try {
    $p1 = PartyId::generate();
    $p2 = PartyId::generate();
    
    $rel = PartyRelationship::create($p1, $p2, PartyRelationshipType::EMPLOYMENT);
    
    echo "Created Relationship: " . $rel->id . "\n";
    echo "Type: " . $rel->type->value . "\n";
    echo "Status: " . $rel->status->value . "\n";
    
    $rel->deactivate();
    echo "Deactivated Status: " . $rel->status->value . "\n";
    
    print_r($rel->toArray());
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
