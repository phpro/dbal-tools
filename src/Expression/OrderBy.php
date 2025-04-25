<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class OrderBy implements Expression
{
    public const string ASC = 'ASC';
    public const string DESC = 'DESC';

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
     * @param self::ASC | self::DESC | null $direction
     */
    public static function field(
        Expression $expression,
        ?string $direction = null,
    ): Expression {
        return new SqlExpression($expression->toSQL().($direction ? ' '.$direction : ''));
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'ORDER BY %s',
            $this->expressions->join(', ')->toSQL()
        );
    }
}
