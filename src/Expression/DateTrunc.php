<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class DateTrunc implements Expression
{
    public function __construct(
        private DateTruncField $field,
        private Expression $source,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf('DATE_TRUNC(%s, %s)', $this->field->toSQL(), $this->source->toSQL());
    }
}
