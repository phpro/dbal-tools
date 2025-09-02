<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class PartitionBy implements Expression
{
    /**
     * @no-named-arguments
     */
    public function __construct(
        private Expression $partitionByExpression,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf('PARTITION BY %s', $this->partitionByExpression->toSQL());
    }
}
