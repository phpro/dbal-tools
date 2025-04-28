<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Vector implements Expression
{
    private ?Expressions $items;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Expression ...$items,
    ) {
        $this->items = count($items) ? new Expressions(...$items) : null;
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'array [%s]',
            $this->items?->join(', ')->toSQL()
        );
    }
}
