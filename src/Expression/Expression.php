<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

interface Expression
{
    /**
     * @return non-empty-string
     */
    public function toSQL(): string;
}
