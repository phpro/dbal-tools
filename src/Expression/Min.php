<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final class Min implements Expression
{
    public function __construct(
        private Expression $expression,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf(
            'MIN(%s)',
            $this->expression->toSQL(),
        );
    }
}
