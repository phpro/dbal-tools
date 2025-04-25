<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class ConcatWs implements Expression
{
    private Expressions $expressions;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Expression $separator,
        Expression $expression,
        Expression ...$rest,
    ) {
        $this->expressions = new Expressions($separator, $expression, ...$rest);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf('CONCAT_WS(%s)', $this->expressions->join(', ')->toSQL());
    }
}
