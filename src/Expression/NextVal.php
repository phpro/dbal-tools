<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class NextVal implements Expression
{
    public function __construct(
        private string $sequenceName,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'nextval(%s)',
            (new LiteralString($this->sequenceName))->toSQL(),
        );
    }
}
