<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class GenerateSeries implements Expression
{
    private Expressions $expressions;

    public function __construct(
        Expression $start,
        Expression $stop,
        ?Expression $step = null,
        ?Expression ...$additionalArgs,
    ) {
        $this->expressions = Expressions::fromNullable(
            $start,
            $stop,
            $step,
            ...$additionalArgs
        );
    }

    public function toSQL(): string
    {
        return sprintf('GENERATE_SERIES(%s)', $this->expressions->join(', ')->toSQL());
    }
}
