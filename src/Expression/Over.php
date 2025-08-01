<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Over implements Expression
{
    public function __construct(
        private ?Expression $expression,
    ) {
    }

    public static function fullWindow(): self
    {
        return new self(null);
    }

    public static function partition(PartitionBy $partitionBy, ?OrderBy $orderBy = null): self
    {
        return new self(
            Expressions::fromNullable($partitionBy, $orderBy)->join(' ')
        );
    }

    public static function orderBy(OrderBy $orderBy): self
    {
        return new self($orderBy);
    }

    public static function aggregation(
        Expression $aggregation,
        self $over,
    ): Expression {
        return new Expressions($aggregation, $over)->join(' ');
    }

    public function toSQL(): string
    {
        return sprintf('OVER(%s)', $this->expression?->toSQL() ?? '');
    }
}
