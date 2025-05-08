<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class DatePart implements Expression
{
    public function __construct(
        private DatePartField $field,
        private Expression $source,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf('DATE_PART(%s, %s)', $this->field->toSQL(), $this->source->toSQL());
    }
}
