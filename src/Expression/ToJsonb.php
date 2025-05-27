<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class ToJsonb implements Expression
{
    private Expression $expression;

    public function __construct(Expression $expression)
    {
        $this->expression = $expression;
    }

    public function toSQL(): string
    {
        return sprintf('TO_JSONB(%s)', $this->expression->toSQL());
    }
}
