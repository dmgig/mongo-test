<?php

declare(strict_types=1);

namespace App\Domain\Party;

enum PartyRelationshipType: string
{
    case EMPLOYMENT = 'employment';
    case MEMBERSHIP = 'membership';
    case ASSOCIATION = 'association';
}
