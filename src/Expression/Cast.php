<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Cast implements Expression
{
    public function __construct(
        private string $intoType,
        private Expression $expression,
    ) {
    }

    public static function varChar(Expression $expression): self
    {
        return new self('VARCHAR', $expression);
    }

    public static function decimal(Expression $expression): self
    {
        return new self('DECIMAL', $expression);
    }

    public static function date(Expression $expression): self
    {
        return new self('DATE', $expression);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            '%s::%s',
            $this->expression->toSQL(),
            $this->intoType
        );
    }
}
