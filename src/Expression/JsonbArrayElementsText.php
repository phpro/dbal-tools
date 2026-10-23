<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class JsonbArrayElementsText implements Expression
{
    public function __construct(
        private Expression $json,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf('jsonb_array_elements_text(%s)', $this->json->toSQL());
    }
}
