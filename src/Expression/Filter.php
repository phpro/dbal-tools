<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Filter implements Expression
{
    public function __construct(
        private Expression $aggregate,
        private Expression $condition,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf('%s FILTER (WHERE %s)', $this->aggregate->toSQL(), $this->condition->toSQL());
    }
}
