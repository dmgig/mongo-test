<?php

declare(strict_types=1);

namespace App\Domain\Event;

enum DatePrecision: string
{
    case YEAR = 'year';
    case MONTH = 'month';
    case DAY = 'day';
    case HOUR = 'hour';
    case MINUTE = 'minute';
    case SECOND = 'second';
    case DECADE = 'decade';
    case SEASON = 'season';
}
