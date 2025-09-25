<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class JsonbBuildArray implements Expression
{
    /**
     * @param list<Expression> $values
     */
    public function __construct(
        private array $values,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'jsonb_build_array(%s)',
            count($this->values)
                ? new Expressions(...$this->values)->join(', ')->toSQL()
                : '',
        );
    }
}
