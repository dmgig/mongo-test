<?php

declare(strict_types=1);

namespace App\Domain\Party;

enum PartyType: string
{
    case INDIVIDUAL = 'individual';
    case ORGANIZATION = 'organization';
}
