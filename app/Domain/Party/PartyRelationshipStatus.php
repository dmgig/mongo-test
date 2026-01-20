<?php

declare(strict_types=1);

namespace App\Domain\Party;

enum PartyRelationshipStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
