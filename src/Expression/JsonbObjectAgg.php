<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

/**
 * PostgreSQL raises an error when a key evaluates to null, so the key must be NOT NULL.
 * With duplicate keys, the last value in aggregation order wins.
 */
final readonly class JsonbObjectAgg implements Expression
{
    public function __construct(
        private Expression $key,
        private Expression $value,
        private ?OrderBy $orderBy = null,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'jsonb_object_agg(%s, %s)',
            $this->key->toSQL(),
            Expressions::fromNullable($this->value, $this->orderBy)->join(' ')->toSQL(),
        );
    }
}
