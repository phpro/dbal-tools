<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Where implements Expression
{
    private Expressions $expressions;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Expression $expression,
        Expression ...$expressions,
    ) {
        $this->expressions = new Expressions($expression, ...$expressions);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'WHERE %s',
            $this->expressions->join(', ')->toSQL(),
        );
    }
}
