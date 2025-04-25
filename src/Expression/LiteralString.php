<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use function Psl\Str\replace;

final class LiteralString implements Expression
{
    public function __construct(
        private string $value,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf("'%s'", replace($this->value, "'", "''"));
    }
}
