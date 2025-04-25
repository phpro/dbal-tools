<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Math implements Expression
{
    /**
     * @param non-empty-string $operator
     */
    private function __construct(
        private Expression $left,
        private string $operator,
        private Expression $right,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf(
            '(%s %s %s)',
            $this->left->toSQL(),
            $this->operator,
            $this->right->toSQL()
        );
    }

    public static function add(Expression $left, Expression $right): self
    {
        return new self($left, '+', $right);
    }

    public static function subtract(Expression $left, Expression $right): self
    {
        return new self($left, '-', $right);
    }

    public static function multiply(Expression $left, Expression $right): self
    {
        return new self($left, '*', $right);
    }

    public static function divide(Expression $left, Expression $right): self
    {
        return new self($left, '/', $right);
    }
}
