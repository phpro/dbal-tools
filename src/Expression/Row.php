<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Row implements Expression
{
    private Expressions $expressions;

    public function __construct(
        Expression $expression,
        Expression ...$rest,
    ) {
        $this->expressions = new Expressions($expression, ...$rest);
    }

    public function toSQL(): string
    {
        return sprintf('ROW(%s)', $this->expressions->join(', ')->toSQL());
    }
}
