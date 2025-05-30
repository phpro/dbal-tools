<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final class IsNotNull implements Expression
{
    public function __construct(
        private Expression $expression,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf(
            '%s IS NOT NULL',
            $this->expression->toSQL(),
        );
    }
}
