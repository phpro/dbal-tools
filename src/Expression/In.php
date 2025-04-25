<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class In implements Expression
{
    public function __construct(
        private Expression $subject,
        private Expressions $in,
    ) {
    }

    public function toSQL(): string
    {
        return sprintf(
            '%s IN (%s)',
            $this->subject->toSQL(),
            $this->in->join(', ')->toSQL(),
        );
    }
}
