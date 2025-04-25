<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use Phpro\DbalTools\Column\Column;

final readonly class JsonbAggStrict implements Expression
{
    public function __construct(
        private Expression $expression,
        private ?OrderBy $orderBy = null,
    ) {
    }

    public static function onManyLeftJoinedJsonObjects(
        Expression $expression, Column $joinColumn, ?OrderBy $orderBy = null,
    ): self {
        return new self(
            new Cases(
                new Expressions(
                    Cases::when(new IsNull($joinColumn), SqlExpression::null())
                ),
                $expression,
            ),
            $orderBy
        );
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'jsonb_agg_strict(%s)',
            Expressions::fromNullable($this->expression, $this->orderBy)->join(' ')->toSQL(),
        );
    }
}
