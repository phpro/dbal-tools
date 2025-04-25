<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class ILike implements Expression
{
    public function __construct(
        private Expression $column,
        private Expression $value,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return $this->column->toSQL().' ILIKE '.$this->value->toSQL();
    }
}
