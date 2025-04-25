<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class RowNumber implements Expression
{
    /**
     * @no-named-arguments
     */
    public function __construct(
        private ?Expression $expression = null,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function over(
        Expression $expression,
        Expression ...$expressions,
    ): self {
        return new self(new Expressions($expression, ...$expressions)->join(' '));
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'ROW_NUMBER() OVER (%s)',
            $this->expression?->toSQL() ?? '',
        );
    }
}
