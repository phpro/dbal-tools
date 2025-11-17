<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Query;

final readonly class CompositeSubQueryOptions
{
    public function __construct(
        public ?bool $materialized = null,
    ) {
    }

    public static function default(): self
    {
        return new self();
    }
}
