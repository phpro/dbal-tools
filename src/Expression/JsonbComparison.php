<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class JsonbComparison implements Expression
{
    /**
     * @param non-empty-string $operator
     */
    private function __construct(
        private Expression $jsonField,
        private string $operator,
        private Expression $right,
    ) {
    }

    public static function containsAnyOfKeys(Expression $jsonField, Expression $keys): self
    {
        return new self($jsonField, SqlExpression::escapePlaceholder('?|')->toSQL(), $keys);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            '%s %s %s',
            $this->jsonField->toSQL(),
            $this->operator,
            $this->right->toSQL()
        );
    }
}
