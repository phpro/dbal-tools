<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

/**
 * Unlike jsonb_agg_strict, json_agg keeps NULL values: use filtered() to leave rows out.
 */
final readonly class JsonAgg implements Expression
{
    public function __construct(
        private Expression $expression,
        private ?OrderBy $orderBy = null,
    ) {
    }

    public static function filtered(Expression $expression, Expression $condition, ?OrderBy $orderBy = null): Filter
    {
        return new Filter(new self($expression, $orderBy), $condition);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'json_agg(%s)',
            Expressions::fromNullable($this->expression, $this->orderBy)->join(' ')->toSQL(),
        );
    }
}
