<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use function Psl\Iter\reduce_with_keys;

final readonly class JsonbBuildObject implements Expression
{
    /**
     * @param non-empty-array<non-empty-string, Expression> $shape
     */
    public function __construct(
        private iterable $shape,
    ) {
    }

    /**
     * @param non-empty-array<non-empty-string, Expression> $shape
     */
    public static function nullable(Expression $nullableColumn, iterable $shape): Expression
    {
        return new Cases(
            new Expressions(
                Cases::when(new IsNull($nullableColumn), SqlExpression::null()),
            ),
            new self($shape),
        );
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'jsonb_build_object(%s)',
            new Expressions(...reduce_with_keys(
                $this->shape,
                /**
                 * @param list<Expression> $carry
                 * @param non-empty-string $key
                 *
                 * @return list<Expression>
                 */
                static fn (array $carry, string $key, Expression $expression): array => [
                    ...$carry,
                    new LiteralString($key),
                    $expression,
                ],
                []
            ))->join(', ')->toSQL()
        );
    }
}
