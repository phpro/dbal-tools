<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

enum DatePartField: string implements Expression
{
    case Century = 'century';
    case Decade = 'decade';
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
    case Hour = 'hour';
    case Minute = 'minute';
    case Second = 'second';
    case Microseconds = 'microseconds';
    case Milliseconds = 'milliseconds';
    case Dow = 'dow';
    case Doy = 'doy';
    case Epoch = 'epoch';
    case Isodow = 'isodow';
    case Isoyear = 'isoyear';
    case Timezone = 'timezone';
    case TimezoneHour = 'timezone_hour';
    case TimezoneMinute = 'timezone_minute';
    case Week = 'week';

    public function toSQL(): string
    {
        return new LiteralString($this->value)->toSQL();
    }
}
