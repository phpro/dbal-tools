<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

/**
 * Unlike jsonb_agg_strict, json_agg keeps NULL values: pass a filter to leave rows out.
 */
final readonly class JsonAgg implements Expression
{
    public function __construct(
        private Expression $expression,
        private ?OrderBy $orderBy = null,
        private ?Expression $filter = null,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'json_agg(%s)%s',
            Expressions::fromNullable($this->expression, $this->orderBy)->join(' ')->toSQL(),
            null === $this->filter ? '' : sprintf(' FILTER (WHERE %s)', $this->filter->toSQL()),
        );
    }
}
