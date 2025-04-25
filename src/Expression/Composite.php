<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Composite implements Expression
{
    private const OPERATOR_AND = 'AND';
    private const OPERATOR_OR = 'OR';

    /**
     * @param self::OPERATOR_* $operator
     */
    private function __construct(
        private string $operator,
        private Expressions $expressions,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function and(Expression $expression, Expression ...$expressions): self
    {
        return new self(self::OPERATOR_AND, new Expressions($expression, ...$expressions));
    }

    /**
     * @no-named-arguments
     */
    public static function or(Expression $expression, Expression ...$expressions): self
    {
        return new self(self::OPERATOR_OR, new Expressions($expression, ...$expressions));
    }

    public function toSQL(): string
    {
        if (1 === count($this->expressions)) {
            return $this->expressions->join('')->toSQL();
        }

        return SqlExpression::parenthesized(
            $this->expressions->join(sprintf(' %s ', $this->operator))->toSQL()
        )->toSQL();
    }
}
