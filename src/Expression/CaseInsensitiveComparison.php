<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class CaseInsensitiveComparison implements Expression
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

    public static function equal(Expression $left, Expression $right): self
    {
        return new self($left, '=', $right);
    }

    public static function notEqual(Expression $left, Expression $right): self
    {
        return new self($left, '!=', $right);
    }

    public static function greaterThan(Expression $left, Expression $right): self
    {
        return new self($left, '>', $right);
    }

    public static function greaterThanOrEqual(Expression $left, Expression $right): self
    {
        return new self($left, '>=', $right);
    }

    public static function lessThan(Expression $left, Expression $right): self
    {
        return new self($left, '<', $right);
    }

    public static function lessThanOrEqual(Expression $left, Expression $right): self
    {
        return new self($left, '<=', $right);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            '%s %s %s',
            new Lower($this->left)->toSQL(),
            $this->operator,
            new Lower($this->right)->toSQL()
        );
    }
}
