<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Alias implements Expression
{
    /**
     * @param Expression       $expression
     * @param non-empty-string $asAlias
     */
    public function __construct(
        private Expression $expression,
        private string $asAlias,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            '%s AS %s',
            $this->expression->toSQL(),
            $this->asAlias
        );
    }
}
