<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Query;

final readonly class CompositeQueryOptions
{
    public function __construct(
        public bool $recursive = false,
    ) {
    }

    public static function default(): self
    {
        return new self();
    }
}
