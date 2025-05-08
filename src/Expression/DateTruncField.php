<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

enum DateTruncField: string implements Expression
{
    case Millennium = 'millennium';
    case Century = 'century';
    case Decade = 'decade';
    case Year = 'year';
    case Quarter = 'quarter';
    case Month = 'month';
    case Week = 'week';
    case Day = 'day';
    case Hour = 'hour';
    case Minute = 'minute';
    case Second = 'second';
    case Milliseconds = 'milliseconds';
    case Microseconds = 'microseconds';

    public function toSQL(): string
    {
        return new LiteralString($this->value)->toSQL();
    }
}
