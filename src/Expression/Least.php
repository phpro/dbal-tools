<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final class Least implements Expression
{
    private Expressions $expressions;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Expression $a,
        Expression $b,
        Expression ...$rest,
    ) {
        $this->expressions = new Expressions($a, $b, ...$rest);
    }

    public function toSQL(): string
    {
        return sprintf(
            'LEAST(%s)',
            $this->expressions->join(', ')->toSQL(),
        );
    }
}
