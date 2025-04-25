<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Max implements Expression
{
    public function __construct(
        private Expression $expression,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'MAX(%s)',
            $this->expression->toSQL(),
        );
    }
}
